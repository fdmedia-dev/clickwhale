<?php
do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap">
	<?php
	echo ClickwhaleHepler::render_heading(
		array(
			'name'         => __( 'Tracking Code', $this->plugin_name ),
			'is_edit'      => isset( $item['id'] ) && $item['id'] !== 0,
			'link_to_list' => 'clickwhale-tracking-codes',
			'link_to_edit' => 'clickwhale-edit-tracking-code',
		)
	);
	?>

    <form id="form_edit_linkpage" method="POST" action="<?php echo esc_attr( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="save_update_linkpage">
        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce( basename( __FILE__ ) ) ?>"/>
        <input type="hidden" name="id" value="<?php echo esc_attr( $item['id'] ) ?>"/>

        <div id="post-body-content">


        </div>
    </form>

</div>
