<?php

class Clickwhale_Linkpage_Edit {
	public function init() {
		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
	}

	/**
	 * Default values for new link
	 * Could be hooked by filter "clickwhale_link_defaults"
	 * @return array
	 */
	public function get_defaults() {
		return array(
			'id'          => 0,
			'created_at'  => '',
			'title'       => '',
			'slug'        => '',
			'description' => '',
			'links'       => '',
			'logo'        => '',
		);
	}

	/**
	 * Filter function
	 * return number of links available on the linkpage
	 * @return mixed|void
	 */
	private function get_linkpages_links_limit() {
		return apply_filters( 'clickwhale_linkpage_links_limit', 5 );
	}

	public function get_item( $request ) {
		global $wpdb;

		$defaults = apply_filters( 'clickwhale_linkpage_defaults', $this->get_defaults() );

		if ( isset( $request['id'] ) ) {
			$item = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_linkpages WHERE id = %d", intval( $request['id'] ) ), ARRAY_A );
			if ( ! $item ) {
				$item   = $defaults;
				$notice = __( 'Item not found', 'clickwhale' );
			}
		} else {
			$item = $defaults;
		}

		return $item;
	}

	public function get_link( $id ) {
		global $wpdb;

		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}clickwhale_links WHERE id=%d", $id ),
			ARRAY_A
		);
	}

	public function get_links() {
		global $wpdb;

		return $wpdb->get_results(
			"SELECT id,title from {$wpdb->prefix}clickwhale_links",
			ARRAY_A
		);
	}

	function save_update_linkpage() {
		global $wpdb;
		$linkpages_table = $wpdb->prefix . 'clickwhale_linkpages';
		$defaults        = apply_filters( 'clickwhale_linkpage_defaults', $this->get_defaults() );
		$item            = array_intersect_key( $_POST, $defaults );
		$item['links']   = isset( $item['links'] ) ? maybe_serialize( $item['links'] ) : '';

		$item = apply_filters( 'clickwhale_linkpage_data_before_save', $item );

		// Try to insert new data into DB
		$result = $wpdb->insert(
			$linkpages_table,
			$item,
		);

		if ( false === $result || $result < 1 ) {
			// if insert fails (error or is exists)
			$wpdb->update(
				$linkpages_table,
				$item,
				array( 'id' => $item['id'] )
			);
		} else {
			// if insert success
			$item['id'] = $wpdb->insert_id;
		}

		// redirect to new record
		$url = 'admin.php?page=clickwhale-edit-linkpage&id=' . $item['id'];
		wp_redirect( admin_url( $url ) );
		die;
	}

	public function admin_scripts() {
		$nonce       = wp_create_nonce( 'linkpage_slug' );
		$nonce_reset = wp_create_nonce( 'linkpage_slug' );
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {

                jQuery('#clickwhale-tabs').tabs();

				<?php if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-edit-linkpage' && isset( $_GET['id'] ) ) { ?>

                var page_id = '<?php echo sanitize_text_field( intval( $_GET['id'] ) ); ?>';

                if (localStorage.getItem('tab-' + page_id)) {
                    jQuery('#clickwhale-tabs').tabs({active: localStorage.getItem('tab-' + page_id)});
                }

                jQuery('#clickwhale-tabs li').click(function () {
                    localStorage.setItem('tab-' + page_id, jQuery(this).index());
                });

				<?php } ?>

                var wrap = jQuery('.linkpage-wrap'),
                    limit = <?php echo $this->get_linkpages_links_limit() ?>;

                if (jQuery('.linkpage-row').length >= limit) {
                    jQuery('#add-pagelink-link').prop('disabled', true);
                }

                jQuery(wrap).sortable({
                    placeholder: "ui-state-highlight"
                });
                wrap.disableSelection();

                // Add link row
                jQuery('#add-pagelink-link').click(function (e) {
                    e.preventDefault();

                    var links_count = jQuery('.linkpage-row').length,
                        links = jQuery('#add-pagelink-select'),
                        link_text = links.find('option:selected').text(),
                        link_id = links.find('option:selected').val(),
                        link_title_ph = "<?php _e( 'Link Title', 'clickwhale' ); ?>",
                        template = '<div class="linkpage-row"><input type="hidden" name="links[' + link_id + '][id]" value="' + link_id + '"><div class="linkpage-row--drag"></div><div class="linkpage-link">' + link_text + '</div><div class="linkpage-link--title"><input type="text" name="links[' + link_id + '][title]" placeholder="' + link_title_ph + '"></div><div class="linkpage-link--image"></div><div class="linkpage-row--remove"></div></div>';

                    if (links_count < limit) {
                        wrap.append(template);
                    }
                    if ((links_count + 1) === limit) {
                        jQuery('#add-pagelink-link').prop('disabled', true);
                    }

                });

                // Remove added link row
                jQuery(document)
                    .on('click', '.linkpage-row--remove', function () {
                        jQuery(this).parent().remove();
                        if (jQuery('.linkpage-row').length < limit) {
                            jQuery('#add-pagelink-link').prop('disabled', false);
                        }
                    })
                    .on('click', '.linkpage-logo-upload', function (e) {

                        e.preventDefault();

                        var button = jQuery(this),
                            custom_uploader = wp.media({
                                title: 'Insert image',
                                library: {
                                    // uploadedTo : wp.media.view.settings.post.id, // attach to the current post?
                                    type: 'image'
                                },
                                button: {
                                    text: 'Use this image' // button label text
                                },
                                multiple: false
                            }).on('select', function () { // it also has "open" and "close" events
                                var attachment = custom_uploader.state().get('selection').first().toJSON();
                                button.html('<img src="' + attachment.url + '">').next().show().next().val(attachment.id);
                            }).open();

                    })
                    .on('click', '.linkpage-logo-remove', function (e) {

                        e.preventDefault();

                        var button = jQuery(this);
                        button.next().val(''); // emptying the hidden field
                        button.hide().prev().html('Upload image');
                    });

                /**
                 * Check slug
                 */
                jQuery('#form_edit_linkpage').on('blur', '#slug', function (e) {

                    var slug = jQuery(this),
                        linkpageSubmit = jQuery('#form_edit_linkpage').find('[type="submit"]');

                    linkpageSubmit.prop('disabled', true);

                    jQuery.post(ajaxurl, {
                        'security': '<?php echo $nonce ?>',
                        'action': 'clickwhale/admin/check_linkpage_slug',
                        'slug': slug.val()
                    }, function (response) {
                        // slug exists
                        if (response.data === true) {
                            slug.addClass('error');
                            jQuery('#slug-description').text('<?php _e( 'This slug is already in use! Please enter another slug', 'clickwhale' ) ?>');
                        }
                        // slug doesn't exists
                        if (response.data === false) {
                            slug.removeClass('error');
                            jQuery('#slug-description').text('');
                            linkpageSubmit.prop('disabled', false);
                        }
                        // slug empty or error
                        if (response.data === 'error') {
                            slug.addClass('error');
                            jQuery('#slug-description').text('<?php _e( 'Please enter slug', 'clickwhale' ) ?>');
                        }
                    })
                });

            });
        </script>
		<?php
	}
}