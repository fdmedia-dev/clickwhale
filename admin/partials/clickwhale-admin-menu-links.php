<?php
global $wpdb;
$linksTable = $wpdb->prefix.'clickwhale_links';
$linksArray = $wpdb->get_results ( "SELECT * FROM $linksTable ");
//var_dump($linksArray);
?>
<div class="wrap">
    <h1 class="wp-heading-inline"><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <a href="#" class="page-title-action">Add New</a>

    <form method="get">
        <table class="wp-list-table widefat fixed striped clickwhale clickwhale-links">
            <thead>
                <tr>
                    <td id="cb" class="manage-column column-cb check-column">
                        <label class="screen-reader-text" for="cb-select-all-1">Select All</label>
                        <input id="cb-select-all-1" type="checkbox">
                    </td>
                    <th scope="col" class="manage-column column-name column-primary">Title</th>    
                    <th scope="col" class="manage-column column-name column-primary">Target URL</th>    
                    <th scope="col" class="manage-column column-name column-primary">Slug</th>    
                    <th scope="col" class="manage-column column-name column-primary">Redirection Type</th>    
                    <th scope="col" class="manage-column column-name column-primary">Description</th>    
                    <th scope="col" class="manage-column column-name column-primary">Categories</th>    
                </tr>
            </thead>
            <tbody id="the-list">
                <?php 
                if($linksArray) {
                    foreach($linksArray as $link) {
                    ?>
                        <tr id="link-<?php echo $link->id ?>">
                            <td><input type="checkbox"></td>
                            <td><a href="admin.php?page=clickwhale-edit-link&linkid=<?php echo $link->id ?>"><?php echo $link->link_title ?></a></td>
                            <td><?php echo $link->link_url ?></td>
                            <td><?php echo $link->link_slug ?></td>
                            <td><?php echo $link->link_redirection ?></td>
                            <td><?php echo $link->link_description ?></td>
                            <td><?php echo $link->link_categories ?></td>
                        </tr> 
                    <?php
                    }
                } else { ?>
                    <tr>
                        <th colspan="7">No links found</th>
                    </tr>
                <?php } ?>
            </tbody>
            <tfoot>
            <tr>
                <td class="manage-column column-cb check-column">
                    <label class="screen-reader-text" for="cb-select-all-2">Select All</label>
                    <input id="cb-select-all-2" type="checkbox">
                </td>
                <th scope="col" class="manage-column column-name column-primary">Title</th>    
                <th scope="col" class="manage-column column-name column-primary">Target URL</th>    
                <th scope="col" class="manage-column column-name column-primary">Slug</th>    
                <th scope="col" class="manage-column column-name column-primary">Redirection Type</th>    
                <th scope="col" class="manage-column column-name column-primary">Description</th>    
                <th scope="col" class="manage-column column-name column-primary">Categories</th>
            </tr>
            </tfoot>
        </table>
    </form>

</div>