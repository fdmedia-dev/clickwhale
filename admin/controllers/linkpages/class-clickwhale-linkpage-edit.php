<?php

class Clickwhale_Linkpage_Edit {
	private static $instance;

	public function init() {
		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
	}

	public static function getInstance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
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
			'styles'      => array(
				'bg_color'            => '#fdd231',
				'text_color'          => '#1a1c1d',
				'link_bg_color'       => '#fee06f',
				'link_color'          => '#1a1c1d',
				'link_bg_color_hover' => '#ffffff',
				'link_color_hover'    => '#397eff',
			),
		);
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
			"SELECT id,title,url from {$wpdb->prefix}clickwhale_links",
			ARRAY_A
		);
	}

	/**
	 * Check if Link page slug already exists
	 *
	 * @param string $slug
	 *
	 * @return bool
	 */
	public static function check_slug( $slug ) {
		global $wpdb;
		if ( $wpdb->get_row( "SELECT post_name FROM {$wpdb->prefix}posts WHERE post_name='$slug'", 'ARRAY_A' ) ) {
			return true;
		} else {
			return false;
		}
	}


	public function save_update_linkpage() {
		global $wpdb;
		$linkpages_table = $wpdb->prefix . 'clickwhale_linkpages';
		$defaults        = apply_filters( 'clickwhale_linkpage_defaults', $this->get_defaults() );
		$item            = array_intersect_key( $_POST, $defaults );
		$item['slug']    = sanitize_title( $item['slug'] );
		$item['links']   = isset( $item['links'] ) ? maybe_serialize( $item['links'] ) : '';
		$item['styles']  = isset( $item['styles'] ) ? maybe_serialize( $item['styles'] ) : '';

		$item = apply_filters( 'clickwhale_linkpage_data_before_save', $item );

		// Check if linkpage exists and then update or insert
		// in some cases default check (not false and < 0) goes wrong
		$linkpage = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$wpdb->prefix}clickwhale_linkpages WHERE id=%d", $item['id'] ) );

		if ( $linkpage ) {
			$wpdb->update(
				$linkpages_table,
				$item,
				array( 'id' => $item['id'] )
			);
		} else {
			$wpdb->insert(
				$linkpages_table,
				$item
			);
			$item['id'] = $wpdb->insert_id;
		}

		// redirect to new record
		$url = 'admin.php?page=clickwhale-edit-linkpage&id=' . $item['id'];
		wp_redirect( admin_url( $url ) );
		die;
	}

	public function set_edit_linkpage_page_title( $admin_title, $title ) {
		return 'Edit Link Page' . $admin_title;
	}

	public function set_add_linkpage_page_title( $admin_title, $title ) {
		return 'Add Link Page' . $admin_title;
	}

	public function admin_scripts() {
		$nonce = wp_create_nonce( 'check_slug' );
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {

                jQuery('input[name^="styles"]').wpColorPicker();

                jQuery('#clickwhale-tabs').tabs({
                    activate: function (event, ui) {
                        if (jQuery(ui.newPanel[0]).attr('id') === 'lp-tab-colors') {
                            jQuery('#reset-colors').show();
                        } else {
                            jQuery('#reset-colors').hide();
                        }
                    }
                });

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
                    limit = <?php echo ClickwhaleLinkpagesHelper::get_links_limit() ?>;

                if (jQuery('.linkpage-row').length >= limit) {
                    jQuery('#add-pagelink-link').prop('disabled', true);
                }

                jQuery(wrap).sortable({
                    placeholder: "ui-state-highlight"
                });
                wrap.disableSelection();

                jQuery(document)
                    // Add link row
                    .on('click', '#add-pagelink-link', function (e) {
                        e.preventDefault();

                        var links_count = jQuery('.linkpage-row').length,
                            links = jQuery('#add-pagelink-select'),
                            link_text = links.find('option:selected').text(),
                            link_url = links.find('option:selected').data('url'),
                            link_id = links.find('option:selected').val(),
                            link_title_ph = "<?php _e( 'Link Title', 'clickwhale' ); ?>",
                            template = '<div class="linkpage-row"><input type="hidden" name="links[' + link_id + '][id]" value="' + link_id + '"><div class="linkpage-row--drag"></div><div class="linkpage-link">' + link_text + ' <span>' + link_url + '</span></div><div class="linkpage-link--title"><input type="text" name="links[' + link_id + '][title]" placeholder="' + link_title_ph + '"></div><div class="linkpage-link--image"></div><div class="linkpage-row--remove"></div></div>';

                        if (links_count < limit) {
                            wrap.append(template);
                        }
                        if ((links_count + 1) === limit) {
                            jQuery('#add-pagelink-link').prop('disabled', true);
                            jQuery('<div class="links-info"><?php printf( 'Currently, a maximum of %d links can be added', ClickwhaleLinkpagesHelper::get_links_limit() ); ?></div>').insertAfter('.linkpage-wrap');
                        }
                    })
                    // Remove added link row
                    .on('click', '.linkpage-row--remove', function () {
                        jQuery(this).parent().remove();
                        if (jQuery('.linkpage-row').length < limit) {
                            jQuery('.links-info').remove();
                            jQuery('#add-pagelink-link').prop('disabled', false);
                        }
                    })
                    // Logo upload
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
                    // logo remove
                    .on('click', '.linkpage-logo-remove', function (e) {
                        e.preventDefault();

                        var button = jQuery(this);

                        button.next().val(''); // emptying the hidden field
                        button.hide().prev().html('Upload image');
                    });

                /**
                 * Check slug
                 */
                jQuery('#form_edit_linkpage').on('blur', '#cw-slug', function (e) {

                    var slug = jQuery(this),
                        linkpageSubmit = jQuery('#form_edit_linkpage').find('[type="submit"]');

                    linkpageSubmit.prop('disabled', true);

                    jQuery.post(ajaxurl, {
                        'security': '<?php echo $nonce ?>',
                        'action': 'clickwhale/admin/check_slug',
                        'type': 'linkpage',
                        'slug': slug.val()
                    }, function (response) {
                        // slug exists
                        if (response.data === true) {
                            slug.addClass('error');
                            jQuery('#cw-slug--description').text('<?php _e( 'This slug is already in use! Please enter another slug', 'clickwhale' ) ?>');
                        }
                        // slug doesn't exists
                        if (response.data === false) {
                            slug.removeClass('error');
                            jQuery('#cw-slug--description').text('');
                            linkpageSubmit.prop('disabled', false);
                        }
                        // slug empty or error
                        if (response.data === 'error') {
                            slug.addClass('error');
                            jQuery('#cw-slug--description').text('<?php _e( 'Please enter slug', 'clickwhale' ) ?>');
                        }
                    })
                });

                jQuery('#reset-colors').click(function (e) {
                    e.preventDefault();
                    if (window.confirm('<?php _e( 'Are you sure? This action will set colors to default. This process cannot be undone!', 'clickwhale' ) ?>')) {
                        $defaults = <?php echo json_encode( $this->get_defaults() ) ?>;

                        jQuery.each($defaults.styles, function (key, val) {
                            jQuery('[name="styles[' + key + ']"').wpColorPicker('color', val);
                        });
                    }
                });

            });
        </script>
		<?php
	}
}