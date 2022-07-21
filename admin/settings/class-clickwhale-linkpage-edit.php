<?php

class Clickwhale_Linkpage_Edit {
	function __construct() {

	}

	public function init() {
		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );
		var_dump( $this );
	}

	/**
	 * Default values for new link
	 * Could be hooked by filter "link_defaults"
	 * @return array
	 */
	public function get_defaults() {
		$fields = [];

		return array(
			'id'          => 0,
			'created_at'  => '',
			'title'       => '',
			'slug'        => '',
			'description' => '',
			'links'       => '',
		);
	}

	/**
	 * Filter function
	 * return number of links available on linkpage
	 * @return mixed|void
	 */
	private function get_linkpages_links_limit() {
		return apply_filters( 'clickwhale_linkpages_links_limit', 5 );
	}

	public function get_item( $request ) {
		global $wpdb;

		$notice   = '';
		$defaults = apply_filters( 'linkpage_defaults', $this->get_defaults() );

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
		$item            = array_intersect_key( $_POST, $this->get_defaults() );
		$item['links']   = isset( $item['links'] ) ? maybe_serialize( $item['links'] ) : '';

		$result = $wpdb->update(
			$linkpages_table,
			$item,
			array( 'id' => $item['id'] )
		);

		if ( false === $result || $result < 1 ) {
			$wpdb->insert(
				$linkpages_table,
				$item,
			);
			$item['id'] = $wpdb->insert_id;
		}


		$url = 'admin.php?page=clickwhale-edit-linkpage&id=' . $item['id'];
		wp_redirect( admin_url( $url ) );
		die;
	}

	public function admin_scripts() {
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'clickwhale-edit-linkpage' ) {
			?>
            <script type='text/javascript'>
                jQuery(document).ready(function () {
                    var wrap = jQuery('.linkpage-wrap'),
                        limit = <?php echo $this->get_linkpages_links_limit() ?>;

                    if (jQuery('.linkpage-row').length >= limit) {
                        jQuery('#add-pagelink-link').prop('disabled', true);
                    }

                    jQuery(wrap).sortable({
                        placeholder: "ui-state-highlight"
                    });
                    wrap.disableSelection();

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

                    jQuery(document).on('click', '.linkpage-row--remove', function () {
                        jQuery(this).parent().remove();
                        if (jQuery('.linkpage-row').length < limit) {
                            jQuery('#add-pagelink-link').prop('disabled', false);
                        }
                    });
                });
            </script>
			<?php
		}
	}
}