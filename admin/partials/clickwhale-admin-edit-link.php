<?php
if( !isset( $_GET['linkid'] ) ) return;

global $wpdb;
$linkID = $_GET['linkid']; 
$linksTable = $wpdb->prefix.'clickwhale_links';
$linkArray = $wpdb->get_results ( "SELECT * FROM $linksTable WHERE id=$linkID");
//var_dump($linkArray);
?>

<div class="wrap">
    <h1 class="wp-heading-inline">Edit link</h1>

    <form action="?page=clickwhale-edit-link&linkid=<?php echo $linkID ?>" method="POST">
        
        <?php 
        settings_fields( $this->get_plugin() . '_options' );

        do_settings_sections( $this->get_plugin() );

        submit_button( 'Save Settings' );
        ?>

    </form>
</div>