<?php
/**
    * Custom_Table_Example_List_Table class that will display our custom table
    * records in nice table
    */
    class Clickwhale_links_List_Table extends WP_List_Table{

        function __construct()
        {
            global $status, $page;
            parent::__construct(
                array(
                    'singular' => 'link',
                    'plural' => 'links',
                )
            );
        }
    
        /**
            * [REQUIRED] this is a default column renderer
            *
            * @param $item - row (key, value array)
            * @param $column_name - string (key)
            * @return HTML
            */
        function column_default($item, $column_name)
        {
            return $item[$column_name];
        }

        /**
         * Render columns
         * method name must be like this: "column_[column_name]"
         * 
         * @param $item - row (key, value array)
         * @return HTML
         */

        /**
         * This is example, how to render column with actions,
         * when you hover row "Edit | Delete" links showed
         *
         * @param $item - row (key, value array)
         * @return HTML
         */
        function column_title($item) {
            // links going to /admin.php?page=[your_plugin_page][&other_params]
            // notice how we used $_REQUEST['page'], so action will be done on curren page
            // also notice how we use $this->_args['singular'] so in this example it will
            // be something like &link=2
            $actions = array(
                'edit' => sprintf('<a href="?page=clickwhale-edit-link&id=%s">%s</a>', $item['id'], __('Edit', 'clickwhale')),
                'delete' => sprintf('<a href="?page=%s&action=delete&id=%s">%s</a>', $_REQUEST['page'], $item['id'], __('Delete', 'clickwhale')),
            );
    
            return sprintf('%s %s',
                $item['title'],
                $this->row_actions($actions)
            );
        }
        function column_url($item) {
            return $item['url'];
        }
        function column_slug($item) {
            return '<div class="slug-input--wrap"><input class="slug-input" type="text" value="link/' . $item['slug'] . '" readonly><a href="#" class="slug-input--btn" title="'. __('Copy Link', 'clickwhale' ) .'"></a></div>';
        }
        function column_redirection($item) {
            return $item['redirection'];
        }
        function column_description($item) {
            return $item['description'];
        }
        function column_categories($item) {
            $categories = unserialize($item['categories']);
            $current_categories = '';

            if($categories){
                global $wpdb;
                $categories_table = $wpdb->prefix . 'clickwhale_categories';
                $lastElement = end($categories);
                foreach($categories as $k => $v){
                    $result = $wpdb->get_results( "SELECT * FROM $categories_table WHERE id=$v");
                    if(!empty($result)) {
                        $current_categories .= $result[0]->title;
                        if($v != $lastElement) {
                            $current_categories .= ', ';
                        }
                    }
                }
            }

            return $current_categories;
        }
    
    
        /**
         * [REQUIRED] this is how checkbox column renders
         *
         * @param $item - row (key, value array)
         * @return HTML
         */
        function column_cb($item)
        {
            return sprintf(
                '<input type="checkbox" name="id[]" value="%s" />',
                $item['id']
            );
        }
    
        /**
            * [REQUIRED] This method return columns to display in table
            * you can skip columns that you do not want to show
            * like content, or description
            *
            * @return array
            */
        function get_columns() {
            $columns = array(
                'cb'           => '<input type="checkbox" />', //Render a checkbox instead of text
                'title'        => __('Title', 'clickwhale'),
                'slug'         => __('Link', 'clickwhale'),
                'url'          => __('Target URL', 'clickwhale'),
                //'redirection'  => __('Redirection Type', 'clickwhale'),
                //'description'  => __('Description', 'clickwhale'),
                'categories'   => __('Categories', 'clickwhale'),
            );
            return $columns;
        }
    
        /**
            * [OPTIONAL] This method return columns that may be used to sort table
            * all strings in array - is column names
            * notice that true on name column means that its default sort
            *
            * @return array
            */
        function get_sortable_columns() {
            $sortable_columns = array(
                'title'        => __('Title', 'clickwhale'),
                'url'          => __('Target URL', 'clickwhale'),
                'slug'         => __('Slug', 'clickwhale'),
                'redirection'  => __('Redirection Type', 'clickwhale'),
            );
            return $sortable_columns;
        }
    
        /**
            * Return array of bult actions if has any
            *
            * @return array
            */
        function get_bulk_actions()
        {
            $actions = array(
                'delete' => 'Delete'
            );
            return $actions;
        }
    
        /**
            * This method processes bulk actions
            * it can be outside of class
            * it can not use wp_redirect coz there is output already
            * in this example we are processing delete action
            * message about successful deletion will be shown on page in next part
            */
        function process_bulk_action()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . 'clickwhale_links';
    
            if ('delete' === $this->current_action()) {
                $ids = isset($_REQUEST['id']) ? $_REQUEST['id'] : array();
                if (is_array($ids)) $ids = implode(',', $ids);
    
                if (!empty($ids)) {
                    $wpdb->query("DELETE FROM $table_name WHERE id IN($ids)");
                }
            }
        }
    
        /**
            * This is the most important method
            *
            * It will get rows from database and prepare them to be showed in table
            */
        function prepare_items()
        {
            global $wpdb;
            $table_name = $wpdb->prefix . 'clickwhale_links'; // do not forget about tables prefix
    
            $per_page = 20; // constant, how much records will be shown per page
    
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
    
            // here we configure table headers, defined in our methods
            $this->_column_headers = array($columns, $hidden, $sortable);
    
            //  process bulk action if any
            $this->process_bulk_action();
    
            // will be used in pagination settings
            $total_items = $wpdb->get_var("SELECT COUNT(id) FROM $table_name");
    
            // prepare query params, as usual current page, order by and order direction
            $paged = isset($_REQUEST['paged']) ? ($per_page * max(0, intval($_REQUEST['paged']) - 1)) : 0;
            $orderby = (isset($_REQUEST['orderby']) && in_array($_REQUEST['orderby'], array_keys($this->get_sortable_columns()))) ? $_REQUEST['orderby'] : 'id';
            $order = (isset($_REQUEST['order']) && in_array($_REQUEST['order'], array('asc', 'desc'))) ? $_REQUEST['order'] : 'asc';
    
            // [REQUIRED] define $items array
            // notice that last argument is ARRAY_A, so we will retrieve array
            $this->items = $wpdb->get_results($wpdb->prepare("SELECT * FROM $table_name ORDER BY $orderby $order LIMIT %d OFFSET %d", $per_page, $paged), ARRAY_A);
    
            // [REQUIRED] configure pagination
            $this->set_pagination_args(array(
                'total_items' => $total_items, // total items defined above
                'per_page' => $per_page, // per page constant defined at top of method
                'total_pages' => ceil($total_items / $per_page) // calculate pages count
            ));
        }
    }