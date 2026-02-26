<?php

namespace clickwhale\includes\admin;

use clickwhale\includes\helpers\{
    Helper,
    Categories_Helper,
    Linkpages_Helper,
    Links_Helper,
    Tracking_Codes_Helper
};
use clickwhale\includes\content_templates\Clickwhale_Linkpage_Content_Templates;
use WP_Error;
if ( !defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Ajax functionality of the plugin.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Clickwhale
 * @subpackage Clickwhale/admin
 */
class Clickwhale_Ajax {
    /**
     * @var Clickwhale_Ajax
     */
    private static Clickwhale_Ajax $instance;

    /**
     * @return Clickwhale_Ajax
     */
    public static function get_instance() : Clickwhale_Ajax {
        if ( empty( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function migration_notice_hide() {
        $type = ( isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '' );
        $plugin = ( isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '' );
        check_ajax_referer( 'clickwhale_' . $plugin . '_admin_nonce', 'security' );
        if ( $type === 'migrate' ) {
            $options_migrate = get_option( 'clickwhale_hide_notice_migrate' );
            $options_migrate[$plugin] = true;
            update_option( 'clickwhale_hide_notice_migrate', $options_migrate );
        } elseif ( $type === 'deactive' ) {
            $options_deactive = get_option( 'clickwhale_hide_notice_deactive' );
            $options_deactive[$plugin] = true;
            update_option( 'clickwhale_hide_notice_deactive', $options_deactive );
        }
        wp_die();
    }

    public function migration_deactive() {
        $plugin = ( isset( $_POST['plugin'] ) ? sanitize_text_field( wp_unslash( $_POST['plugin'] ) ) : '' );
        $target = ( isset( $_POST['target'] ) ? sanitize_text_field( wp_unslash( $_POST['target'] ) ) : '' );
        check_ajax_referer( 'clickwhale_' . $plugin . '_admin_nonce', 'security' );
        if ( !current_user_can( 'activate_plugins' ) ) {
            wp_send_json_error();
        }
        deactivate_plugins( $target );
        wp_send_json_success();
    }

    public function save_migration_option() {
        check_ajax_referer( 'migration_to_clickwhale', 'security' );
        if ( isset( $_POST['name'] ) && isset( $_POST['value'] ) ) {
            $options = get_option( 'clickwhale_tools_migration_options' );
            $option = sanitize_text_field( wp_unslash( $_POST['name'] ) );
            $value = boolval( sanitize_text_field( wp_unslash( $_POST['value'] ) ) );
            $options[$option] = $value;
            update_option( 'clickwhale_tools_migration_options', $options );
            wp_send_json_success();
        }
    }

    public function migration_to_clickwhale() {
        check_ajax_referer( 'migration_to_clickwhale', 'security' );
        $available = clickwhale()->tools->migration->available_migrations();
        $options = get_option( 'clickwhale_tools_migration_options' );
        $migrant = ( isset( $_POST['migrant'] ) ? sanitize_text_field( wp_unslash( $_POST['migrant'] ) ) : '' );
        $item = $available[$migrant];
        $result = array();
        if ( !$item ) {
            wp_send_json_error();
        }
        if ( clickwhale()->tools->migration->check_active( $item['path'] ) ) {
            $result['title'] = $item['name'];
            if ( isset( $options[$item['slug'] . '_categories'] ) && $options[$item['slug'] . '_categories'] !== false || isset( $options[$item['slug'] . '_links'] ) && $options[$item['slug'] . '_links'] !== false ) {
                //$migrator = new $item['class']();
                $migratorClass = '\\clickwhale\\includes\\admin\\migration\\' . $item['class'];
                $migrator = new $migratorClass();
                $result['data'] = $migrator->run_migration( $options[$item['slug'] . '_categories'], $options[$item['slug'] . '_links'] );
            } else {
                $result['data'] = __( 'Nothing to migrate', 'clickwhale' );
            }
        }
        $options_migrate = get_option( 'clickwhale_hide_notice_migrate' );
        $options_migrate[$migrant] = true;
        update_option( 'clickwhale_hide_notice_migrate', $options_migrate );
        $options_deactive = get_option( 'clickwhale_hide_notice_deactive' );
        $options_deactive[$migrant] = false;
        update_option( 'clickwhale_hide_notice_deactive', $options_deactive );
        wp_send_json_success( $result );
    }

    public function migration_reset() {
        check_ajax_referer( 'migration_reset', 'security' );
        $migration_options = array();
        $notice_migrate_options = array();
        $notice_deactive_options = array();
        $last_migration_options = array();
        foreach ( clickwhale()->tools->migration->available_migrations() as $item ) {
            $migration_options[$item['slug'] . '_categories'] = false;
            $migration_options[$item['slug'] . '_links'] = false;
            $notice_migrate_options[$item['slug']] = false;
            $notice_deactive_options[$item['slug']] = true;
            $last_migration_options[$item['slug'] . '_last_migration'] = '';
        }
        update_option( 'clickwhale_tools_migration_options', $migration_options );
        update_option( 'clickwhale_tools_last_migration_options', $last_migration_options );
        update_option( 'clickwhale_hide_notice_migrate', $notice_migrate_options );
        update_option( 'clickwhale_hide_notice_deactive', $notice_deactive_options );
        $result = __( 'Successfully deleted! Page will be reloaded...', 'clickwhale' );
        wp_send_json_success( $result );
    }

    public function clickwhale_reset() {
        check_ajax_referer( 'clickwhale_reset', 'security' );
        global $wpdb;
        $result = array();
        $text = '';
        $reset_raw = filter_input( INPUT_POST, 'reset', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $reset = ( $reset_raw ? sanitize_key( $reset_raw ) : '' );
        if ( empty( $reset ) ) {
            wp_send_json_error();
        }
        switch ( $reset ) {
            case 'stats':
                $text = __( 'All statistic has been reset', 'clickwhale' );
                $tables = array('track', 'visitors');
                $tables_full = Helper::get_db_table_names( $tables );
                $tables_escaped = array_map( static function ( $t ) use($wpdb) {
                    return '`' . esc_sql( $t ) . '`';
                }, $tables_full );
                $tables_sql = implode( ', ', $tables_escaped );
                // Drop `stats` plugin tables
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
                $result['status'] = $wpdb->query( "DROP TABLE IF EXISTS {$tables_sql}" );
                break;
            case 'db':
                $text = __( 'All plugin tables has been reset', 'clickwhale' );
                $tables = array(
                    'categories',
                    'linkpages',
                    'links',
                    'meta',
                    'track',
                    'tracking_codes',
                    'visitors'
                );
                $tables_full = Helper::get_db_table_names( $tables );
                // Allow 3rd-parties to filter the list of tables before building SQL
                $tables_full = apply_filters( 'clickwhale_reset_tables', $tables_full );
                if ( !is_array( $tables_full ) ) {
                    $tables_full = array();
                }
                $tables_escaped = array_map( static function ( $t ) use($wpdb) {
                    return '`' . esc_sql( $t ) . '`';
                }, $tables_full );
                $tables_sql = implode( ', ', $tables_escaped );
                // Drop all plugin tables
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
                $result['status'] = $wpdb->query( "DROP TABLE IF EXISTS {$tables_sql}" );
                break;
            case 'settings':
                $text = __( 'All plugin settings has been restored', 'clickwhale' );
                $current_general_options = get_option( 'clickwhale_general_options' );
                $current_access_level = $current_general_options['access_level'] ?? array();
                // Delete plugin options
                $option_names = array_keys( clickwhale()->settings->default_options() );
                foreach ( $option_names as $name ) {
                    delete_option( 'clickwhale_' . $name . '_options' );
                }
                // Initiate default settings
                clickwhale()->settings->add_default_options();
                // Restore previously saved 'access_level' value to keep access
                // for non-admin roles after clicking `Tools -> Restore default settings`
                if ( !in_array( 'administrator', clickwhale()->user->get_current_user_roles() ) ) {
                    $general_options = get_option( 'clickwhale_general_options' );
                    $default_access_level = $general_options['access_level'] ?? array();
                    // Compare arrays
                    if ( $current_access_level !== $default_access_level ) {
                        $general_options['access_level'] = $current_access_level;
                        update_option( 'clickwhale_general_options', $general_options );
                    }
                }
                $result['status'] = true;
                break;
        }
        $result['text'] = $text;
        clickwhale_uninstall_cleanup();
        clickwhale_activate();
        wp_send_json_success( $result );
    }

    public function sanitize_slug() {
        check_ajax_referer( 'sanitize_slug', 'security' );
        $type = ( isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '' );
        if ( empty( $type ) ) {
            wp_send_json_error();
        }
        $post_slug = ( isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '' );
        if ( empty( $post_slug ) ) {
            wp_send_json_error();
        }
        $slug = '';
        switch ( $type ) {
            case 'link':
                $slug = Links_Helper::sanitize_slug( $post_slug );
                break;
            case 'linkpage':
            case 'category':
                $slug = sanitize_title( $post_slug );
                break;
            default:
                wp_send_json_error();
        }
        wp_send_json_success( $slug );
    }

    public function slug_exists() {
        check_ajax_referer( 'slug_exists', 'security' );
        $type = ( isset( $_POST['type'] ) ? sanitize_key( $_POST['type'] ) : '' );
        if ( empty( $type ) ) {
            wp_send_json_error();
        }
        $post_slug = ( isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '' );
        if ( empty( $post_slug ) ) {
            wp_send_json_error();
        }
        // Slashes are allowed inside of `Link` slug so we use `Links_Helper::sanitize_slug()` for it.
        // For other entities (`Link Page`, `Category`) we use `sanitize_title()`
        $slug = ( 'link' === $type ? Links_Helper::sanitize_slug( $post_slug ) : sanitize_title( $post_slug ) );
        if ( '' === $slug ) {
            wp_send_json_error();
        }
        $id = ( isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0 );
        $result = array();
        switch ( $type ) {
            case 'link':
            case 'linkpage':
                // Search in CW `links` table
                $link = Links_Helper::get_by_slug( $slug );
                if ( !empty( $link['id'] ) ) {
                    if ( 'link' !== $type || (int) $link['id'] !== $id ) {
                        $result = array(
                            'id'    => (int) $link['id'],
                            'title' => esc_html( wp_unslash( $link['title'] ) ),
                            'type'  => 'CW link',
                        );
                    }
                    break;
                }
                // Search in CW `linkpages` table
                $linkpage = Linkpages_Helper::get_by_slug( $slug );
                if ( !empty( $linkpage['id'] ) ) {
                    if ( 'linkpage' !== $type || (int) $linkpage['id'] !== $id ) {
                        $result = array(
                            'id'    => (int) $linkpage['id'],
                            'title' => esc_html( wp_unslash( $linkpage['title'] ) ),
                            'type'  => 'CW link page',
                        );
                    }
                    break;
                }
                // Search in WP `posts` table
                $post = Helper::get_post_by_slug( $slug );
                if ( !empty( $post['id'] ) ) {
                    $result = array(
                        'id'    => (int) $post['id'],
                        'title' => esc_html( wp_unslash( $post['title'] ) ),
                        'type'  => esc_html( $post['type'] ),
                    );
                    break;
                }
                // Search in WP taxonomies
                $taxonomy = Helper::get_taxonomy_by_slug( $slug );
                if ( !empty( $taxonomy['id'] ) ) {
                    $result = array(
                        'id'    => (int) $taxonomy['id'],
                        'title' => esc_html( wp_unslash( $taxonomy['title'] ) ),
                        'type'  => esc_html( $taxonomy['type'] ),
                    );
                    break;
                }
                // HTTP probe to URL: detect if slug is handled by custom endpoints, rewrite rules, .htaccess rules, etc.
                // Strategy:
                // - Use HEAD (fallback to GET) with redirection disabled to observe raw status and Location.
                // - 200 → occupied (custom endpoint)
                // - 3xx → compare Location with control redirect of a random non-existing path
                //        if equal → catch-all (free); if different → specific redirect (occupied)
                // - 404/410 → free
                // - 401/403/405/406/429 or 5xx → conservative: occupied
                $probe_url = home_url( ltrim( $slug, '/' ) );
                $args = array(
                    'timeout'     => 2,
                    'redirection' => 0,
                );
                $response = wp_remote_head( $probe_url, $args );
                if ( is_wp_error( $response ) ) {
                    // retry with GET once; if still fails — conservative: occupied
                    $response = wp_remote_get( $probe_url, $args );
                    if ( is_wp_error( $response ) ) {
                        $result = array(
                            'id'    => 0,
                            'title' => esc_html( $slug ),
                            'type'  => 'custom endpoint',
                        );
                        break;
                    }
                }
                $code = (int) wp_remote_retrieve_response_code( $response );
                // If HEAD not allowed, some servers return 405. Fallback to GET once.
                if ( 405 === $code ) {
                    $response = wp_remote_get( $probe_url, $args );
                    if ( is_wp_error( $response ) ) {
                        $result = array(
                            'id'    => 0,
                            'title' => esc_html( $slug ),
                            'type'  => 'custom endpoint',
                        );
                        break;
                    }
                    $code = (int) wp_remote_retrieve_response_code( $response );
                }
                if ( 200 === $code ) {
                    // 200 without redirect — definite custom endpoint
                    $result = array(
                        'id'    => 0,
                        'title' => esc_html( $slug ),
                        'type'  => 'custom endpoint',
                    );
                    break;
                }
                if ( $code >= 300 && $code < 400 ) {
                    $loc = wp_remote_retrieve_header( $response, 'location' );
                    if ( is_array( $loc ) ) {
                        $loc = reset( $loc );
                    }
                    // 3xx without Location → specific handler
                    if ( empty( $loc ) ) {
                        $result = array(
                            'id'    => 0,
                            'title' => esc_html( $slug ),
                            'type'  => 'custom endpoint',
                        );
                        break;
                    }
                    // Control probe to detect catch-all redirects used for 404s
                    $ctrl_url = home_url( '__cw_probe__' . wp_rand( 100000, 999999 ) . '__' );
                    $ctrl_resp = wp_remote_head( $ctrl_url, $args );
                    if ( is_wp_error( $ctrl_resp ) ) {
                        // try GET once before concluding occupied
                        $ctrl_resp = wp_remote_get( $ctrl_url, $args );
                        if ( is_wp_error( $ctrl_resp ) ) {
                            $result = array(
                                'id'    => 0,
                                'title' => esc_html( $slug ),
                                'type'  => 'custom endpoint',
                            );
                            break;
                        }
                    }
                    $ctrl_code = (int) wp_remote_retrieve_response_code( $ctrl_resp );
                    // HEAD may be 405 on some hosts for the control path as well
                    if ( 405 === $ctrl_code ) {
                        $ctrl_resp = wp_remote_get( $ctrl_url, $args );
                        if ( is_wp_error( $ctrl_resp ) ) {
                            $result = array(
                                'id'    => 0,
                                'title' => esc_html( $slug ),
                                'type'  => 'custom endpoint',
                            );
                            break;
                        }
                        $ctrl_code = (int) wp_remote_retrieve_response_code( $ctrl_resp );
                    }
                    $ctrl_loc = wp_remote_retrieve_header( $ctrl_resp, 'location' );
                    if ( is_array( $ctrl_loc ) ) {
                        $ctrl_loc = reset( $ctrl_loc );
                    }
                    // Same redirect as random 404 → catch-all → free
                    if ( $ctrl_code >= 300 && $ctrl_code < 400 && !empty( $ctrl_loc ) && Helper::urls_effectively_equal( $loc, $ctrl_loc ) ) {
                        break;
                    }
                    // Otherwise → specific redirect → occupied
                    $result = array(
                        'id'    => 0,
                        'title' => esc_html( $slug ),
                        'type'  => 'custom endpoint',
                    );
                    break;
                }
                if ( in_array( $code, array(
                    401,
                    403,
                    406,
                    429
                ), true ) || $code >= 500 ) {
                    $result = array(
                        'id'    => 0,
                        'title' => esc_html( $slug ),
                        'type'  => 'custom endpoint',
                    );
                    break;
                }
                // 404/410 or other non-success, non-redirect codes → free
                break;
            case 'category':
                $category = Categories_Helper::get_by_slug( $slug );
                if ( !empty( $category ) ) {
                    if ( (int) $category['id'] !== $id ) {
                        $result = array(
                            'id'    => (int) $category['id'],
                            'title' => esc_html( wp_unslash( $category['title'] ) ),
                            'type'  => 'CW category',
                        );
                    }
                }
                break;
            default:
                wp_send_json_error();
        }
        wp_send_json_success( $result );
    }

    public function scan_links() {
        check_ajax_referer( 'clickwhale_link_scanner', 'security' );
        $post_slug = ( isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '' );
        $slug = Links_Helper::sanitize_slug( $post_slug );
        if ( '' === $slug ) {
            wp_send_json_error();
        }
        $id = ( isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0 );
        if ( 0 === $id ) {
            wp_send_json_error();
        }
        delete_transient( 'clickwhale_scanned_link_' . $id );
        global $wpdb;
        $target_url = home_url( $slug );
        $post_types = array('post', 'page');
        $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
        $params = array_merge( $post_types, array('%' . $wpdb->esc_like( $target_url ) . '%') );
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $posts = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_type, post_title, post_content\n                FROM {$wpdb->prefix}posts\n                WHERE post_status = 'publish'\n                AND post_type IN ({$placeholders})\n                AND post_content LIKE %s\n                ORDER BY ID ASC, post_type ASC, post_title ASC", ...$params ) );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $result = array();
        if ( $posts ) {
            // 1. Match slug URL as `href` value in <a> element
            // 2. Collect link titles
            $pattern = '/<a\\s[^>]*href=(["\'])' . preg_quote( $target_url, '/' ) . '\\/?\\1[^>]*>(.*?)<\\/a>/is';
            $rows = array();
            foreach ( $posts as $post ) {
                if ( !preg_match_all( $pattern, $post->post_content, $matches ) ) {
                    continue;
                }
                // Count number of link occurrences that start with `$target_url`
                $total = count( $matches[0] );
                $titles = $matches[2];
                $rows[] = array(
                    'ID'         => $post->ID,
                    'post_type'  => $post->post_type,
                    'post_title' => $post->post_title,
                    'total'      => $total,
                    'titles'     => $titles,
                );
            }
            if ( $rows ) {
                $html = Links_Helper::render_link_rows( $rows );
                $timestamp = time();
                $data = array(
                    'html'      => $html,
                    'timestamp' => $timestamp,
                );
                set_transient( 'clickwhale_scanned_link_' . $id, $data, DAY_IN_SECONDS );
                $result = array(
                    'html'      => $html,
                    'last_time' => wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ),
                );
            }
        }
        wp_send_json_success( $result );
    }

    /**
     * @return void
     * @since 1.1.0
     */
    public function get_posts_by_post_type() {
        check_ajax_referer( 'get_posts_by_post_type', 'security' );
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        if ( !isset( $_POST['post_type'] ) || !sanitize_key( wp_unslash( $_POST['post_type'] ) ) ) {
            wp_send_json_error( 'Post Type Error!' );
        }
        $result = array();
        $args = array(
            'numberposts' => -1,
            'post_type'   => ( isset( $_POST['post_type'] ) ? sanitize_key( wp_unslash( $_POST['post_type'] ) ) : 'post' ),
            'orderby'     => 'title',
            'order'       => 'ASC',
            'post_status' => 'publish',
        );
        $posts = get_posts( $args );
        if ( $posts ) {
            foreach ( $posts as $post ) {
                $result['posts'][] = array(
                    'id'    => $post->ID,
                    'title' => $post->post_title,
                    'url'   => get_permalink( $post->ID ),
                );
            }
        } else {
            $result['posts'] = false;
        }
        wp_send_json_success( $result );
    }

    /**
     * @return void
     * @since 1.1.0
     */
    public function get_cw_links() {
        check_ajax_referer( 'get_cw_links', 'security' );
        $links = Links_Helper::get_all( 'id', 'asc', 'ARRAY_A' );
        if ( !$links ) {
            wp_send_json_error( 'ClickWhale Links Not Found!' );
        }
        wp_send_json_success( array(
            'links' => $links,
        ) );
    }

    /**
     * @return void
     * @since 1.2.0
     */
    public function tracking_code_toggle_active() {
        global $wpdb;
        check_ajax_referer( 'clickwhale_toggle_tracking_code', 'security' );
        $result = array();
        $table = Helper::get_db_table_name( 'tracking_codes' );
        $data = array(
            'is_active' => ( isset( $_POST['status'] ) ? sanitize_text_field( wp_unslash( $_POST['status'] ) ) : 0 ),
        );
        $where = array(
            'id' => ( isset( $_POST['id'] ) ? intval( wp_unslash( $_POST['id'] ) ) : 0 ),
        );
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->update( $table, $data, $where );
        $result['action_disable_all'] = Tracking_Codes_Helper::is_active_limit();
        wp_send_json_success( $result );
    }

    /**
     * @return void
     * @since 1.3.0
     */
    public function add_link_to_linkpage() {
        check_ajax_referer( 'clickwhale_add_link_to_linkpage', 'security' );
        $template = new Clickwhale_Linkpage_Content_Templates();
        $type = ( isset( $_POST['type'] ) ? sanitize_text_field( wp_unslash( $_POST['type'] ) ) : '' );
        $result['template'] = $template->get_template( $type, false, false );
        wp_send_json_success( $result );
    }

    public function upload_csv() {
        check_ajax_referer( 'upload_csv', 'security' );
        if ( !function_exists( 'wp_handle_upload' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( !isset( $_FILES['file'] ) ) {
            wp_send_json_error( __( 'Please, select .csv file', 'clickwhale' ) );
        }
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $file = $_FILES['file'];
        // Validate upload and ensure it's a CSV
        if ( !$file || empty( $file['tmp_name'] ) || !is_uploaded_file( $file['tmp_name'] ) ) {
            wp_send_json_error( new WP_Error('001', __( 'Invalid upload.', 'clickwhale' )) );
        }
        $filetype = wp_check_filetype_and_ext( $file['tmp_name'], ( isset( $file['name'] ) ? sanitize_file_name( $file['name'] ) : '' ) );
        $is_csv = isset( $filetype['ext'] ) && 'csv' === strtolower( (string) $filetype['ext'] );
        if ( !$is_csv ) {
            wp_send_json_error( new WP_Error('001', __( 'Please, select .csv file', 'clickwhale' )) );
        }
        $col_delimiter = ",";
        $delimiters = [";", "\t", "|"];
        $html = '';
        $default_columns = Helper::get_import_default_columns();
        if ( !function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        global $wp_filesystem;
        $file_contents = $wp_filesystem->get_contents( $file['tmp_name'] );
        if ( false === $file_contents || '' === $file_contents ) {
            wp_send_json_error( 'Error opening file!' );
        }
        // Split content into lines and parse CSV headers using str_getcsv
        $lines = preg_split( "/\r\n|\n|\r/", $file_contents );
        $line0 = ( isset( $lines[0] ) ? $lines[0] : '' );
        $line1 = ( isset( $lines[1] ) ? $lines[1] : '' );
        // find col head and delimiter
        // by default delimiter is comma ','
        $headings = str_getcsv( $line0, $col_delimiter );
        // if comma is not a delimiter than $columns returns
        // array{ [0]=> 'col1?col2?col3...'}
        // '?' is unknown delimiter
        if ( count( $headings ) <= 1 ) {
            foreach ( $delimiters as $delimiter ) {
                // try to find delimiter
                $headings_tmp = explode( $delimiter, $headings[0] );
                // if delimiter is correct explode() will return array with more than 1 item
                if ( $headings_tmp > 1 ) {
                    $col_delimiter = $delimiter;
                    // stop search
                    break;
                }
            }
            $headings = $headings_tmp;
        }
        $first_line = str_getcsv( $line1, $col_delimiter );
        // clean headings
        foreach ( $headings as $k => $v ) {
            $v = preg_replace( '/[\\x00-\\x1F\\x80-\\xFF]/', '', $v );
            $v = strtolower( $v );
            $headings[$k] = $v;
        }
        $html .= '<table class="wp-list-table widefat striped table-view-list"><thead><tr>';
        $html .= '<th>' . __( 'Column name', 'clickwhale' ) . '</th>';
        $html .= '<th>' . __( 'Map to field', 'clickwhale' ) . '</th>';
        $html .= '</tr></thead><tbody>';
        $i = 0;
        foreach ( $headings as $heading ) {
            $select = '<select>';
            $select .= '<option value="0">' . __( 'Do not import', 'clickwhale' ) . '</option>';
            $select .= '<option value="" disabled>---------------------</option>';
            foreach ( $default_columns as $option ) {
                $selected = ( in_array( $option, $headings ) && $option === $headings[$i] ? 'selected="selected"' : '' );
                $select .= '<option value="' . strtolower( esc_attr( $option ) ) . '" ' . $selected . '>' . ucfirst( esc_html( $option ) ) . '</option>';
            }
            $select .= '</select>';
            $html .= '<tr><td><strong>' . esc_html( $heading ) . '</strong><br>';
            $html .= '<small>' . __( 'Example:', 'clickwhale' ) . ' ' . esc_html( $first_line[$i] ) . '</small></td>';
            $html .= '<td>' . $select . '</td></tr>';
            $i++;
        }
        $html .= '</tbody></table>';
        $result = array();
        $result['delimiter'] = $col_delimiter;
        $result['table'] = $html;
        wp_send_json_success( $result );
    }

    public function map_csv() {
        check_ajax_referer( 'map_csv', 'security' );
        if ( !isset( $_FILES['file'] ) ) {
            $error = new WP_Error('001', __( 'Please, select .csv file', 'clickwhale' ));
            wp_send_json_error( $error );
        }
        $file_name = ( isset( $_FILES['file']['name'] ) ? sanitize_file_name( wp_unslash( $_FILES['file']['name'] ) ) : '' );
        $file_type = wp_check_filetype( $file_name );
        if ( 'csv' !== $file_type['ext'] ) {
            $error = new WP_Error('001', __( 'Please, select .csv file', 'clickwhale' ), $file_type['type']);
            wp_send_json_error( $error );
        }
        $html = '';
        $delimiter = ( isset( $_POST['delimiter'] ) ? sanitize_text_field( wp_unslash( $_POST['delimiter'] ) ) : ',' );
        $redirections = Links_Helper::get_redirections();
        $link_targets = array_merge( array(
            '' => __( 'Default', 'clickwhale' ),
        ), Links_Helper::get_link_targets() );
        $default_columns = Helper::get_import_default_columns();
        $mapped_columns = ( !empty( $_POST['mapped'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['mapped'] ) ) ) : array() );
        $excluded_columns = ( !empty( $_POST['excluded'] ) ? explode( ',', sanitize_text_field( wp_unslash( $_POST['excluded'] ) ) ) : array() );
        $filtered = array();
        if ( !function_exists( 'WP_Filesystem' ) ) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        WP_Filesystem();
        global $wp_filesystem;
        $tmp_name = ( isset( $_FILES['file']['tmp_name'] ) ? sanitize_text_field( wp_unslash( $_FILES['file']['tmp_name'] ) ) : '' );
        $file_contents = $wp_filesystem->get_contents( $tmp_name );
        if ( false === $file_contents || '' === $file_contents ) {
            $error = new WP_Error('002', __( 'Error opening file!', 'clickwhale' ));
            wp_send_json_error( $error );
        }
        // Split CSV into lines and parse
        $lines = preg_split( "/\r\n|\n|\r/", $file_contents );
        // get headings
        $first_line = ( isset( $lines[0] ) ? $lines[0] : '' );
        $file_headings = str_getcsv( $first_line, $delimiter );
        // get array of unique values from mapped and default columns
        foreach ( $default_columns as $default_column ) {
            if ( !in_array( $default_column, $mapped_columns ) ) {
                $mapped_columns[] = $default_column;
            }
        }
        // filter csv rows and exclude not mapped columns
        $headings_num = count( $file_headings );
        $total_lines = count( $lines );
        for ($ln = 1; $ln < $total_lines; $ln++) {
            if ( '' === trim( (string) $lines[$ln] ) ) {
                continue;
            }
            $data = str_getcsv( $lines[$ln], $delimiter );
            $row = array();
            for ($c = 0; $c < $headings_num; $c++) {
                if ( in_array( $c, $excluded_columns, true ) ) {
                    continue;
                }
                $row[] = $data[$c] ?? '';
            }
            if ( !empty( $row ) ) {
                $filtered[] = $row;
            }
        }
        // start html
        $html .= '<table class="wp-list-table widefat striped table-view-list"><thead><tr>';
        foreach ( $mapped_columns as $heading ) {
            $html .= '<th>' . $heading . '</th>';
        }
        $html .= '<th></th>';
        $html .= '</tr></thead><tbody>';
        // add rows
        $mapped_num = count( $mapped_columns );
        foreach ( $filtered as $data ) {
            $html .= '<tr>';
            for ($c = 0; $c < $mapped_num; $c++) {
                switch ( $mapped_columns[$c] ) {
                    case 'title':
                        $value = ( isset( $data[$c] ) ? esc_attr( $data[$c] ) : '' );
                        $input = '<input name="' . $mapped_columns[$c] . '" type="text" value="' . $value . '" required>';
                        break;
                    case 'url':
                        $value = ( isset( $data[$c] ) ? esc_url( $data[$c] ) : '' );
                        $input = '<input name="' . $mapped_columns[$c] . '" type="url" value="' . $value . '" required>';
                        break;
                    case 'slug':
                        $value = ( isset( $data[$c] ) ? Links_Helper::sanitize_slug( $data[$c] ) : '' );
                        $input = '<input name="' . $mapped_columns[$c] . '" type="text" value="' . esc_attr( $value ) . '" required>';
                        break;
                    case 'redirection':
                        $options = '';
                        $value = ( isset( $data[$c] ) ? intval( $data[$c] ) : 301 );
                        foreach ( $redirections as $k => $v ) {
                            $selected = selected( $k, $value, false );
                            $options .= '<option value="' . $k . '"' . $selected . '>' . $v . '</option>';
                        }
                        $input = '<select name="' . $mapped_columns[$c] . '">' . $options . '</select>';
                        break;
                    case 'link_target':
                        $options = '';
                        $value = ( isset( $data[$c] ) ? esc_attr( $data[$c] ) : 'blank' );
                        foreach ( $link_targets as $k => $v ) {
                            $selected = selected( $k, $value, false );
                            $options .= '<option value="' . $k . '"' . $selected . '>' . esc_html( $v ) . '</option>';
                        }
                        $input = '<select name="' . $mapped_columns[$c] . '">' . $options . '</select>';
                        break;
                    case 'nofollow':
                    case 'sponsored':
                        $value = ( isset( $data[$c] ) ? intval( $data[$c] ) : 0 );
                        $checked = checked( '1', $value, false );
                        $input = '<label><input name="' . $mapped_columns[$c] . '" type="checkbox" value="1"' . $checked . '>' . $mapped_columns[$c] . '</label>';
                        break;
                    default:
                        $input = '<input type="text" value="">';
                }
                $html .= '<td class="for_import ' . $mapped_columns[$c] . '">' . $input . '</td>';
            }
            $html .= '<td><button type="button" class="remove"><svg class="feather"><use href="' . CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#trash-2"></use></svg></button></td>';
            $html .= '</tr>';
        }
        $html .= '</tbody></table>';
        wp_send_json_success( $html );
    }

    public function check_slug_for_import() {
        check_ajax_referer( 'check_slug', 'security' );
        global $wpdb;
        $links_table = $wpdb->prefix . 'clickwhale_links';
        $table = '`' . esc_sql( $links_table ) . '`';
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $result = $wpdb->get_results( "SELECT slug FROM {$table}", ARRAY_A );
        wp_send_json_success( $result );
    }

    public function import_csv() {
        check_ajax_referer( 'import_csv', 'security' );
        global $wpdb;
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $data = ( isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : null );
        if ( empty( $data ) || !is_array( $data ) ) {
            $error = new WP_Error('004', __( 'Nothing to import!', 'clickwhale' ));
            wp_send_json_error( $error );
        }
        $links_table = $wpdb->prefix . 'clickwhale_links';
        $result = array();
        foreach ( $data as $v ) {
            if ( !is_array( $v ) ) {
                continue;
            }
            $v['title'] = ( isset( $v['title'] ) ? sanitize_text_field( $v['title'] ) : '' );
            $v['slug'] = ( isset( $v['slug'] ) ? Links_Helper::sanitize_slug( $v['slug'] ) : '' );
            $v['url'] = ( isset( $v['url'] ) ? esc_url_raw( $v['url'] ) : '' );
            $v['description'] = '';
            $v['author'] = get_current_user_id();
            $v['created_at'] = gmdate( 'Y-m-d H:i:s' );
            $v['updated_at'] = gmdate( 'Y-m-d H:i:s' );
            if ( isset( $v['undefined'] ) ) {
                unset($v['undefined']);
            }
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $insert = $wpdb->insert( $links_table, $v );
            if ( $insert ) {
                $message = sprintf( 
                    /* translators: %s: link title */
                    __( 'Link <strong>&quot;%s&quot;</strong> successfully imported!', 'clickwhale' ),
                    esc_html( $v['title'] )
                 );
            } else {
                $message = sprintf( 
                    /* translators: %s: link title */
                    __( '<strong>Error!</strong> Link <strong>&quot;%s&quot;</strong> not imported!', 'clickwhale' ),
                    esc_html( $v['title'] )
                 );
            }
            $result[] = wp_kses( $message, array(
                'strong' => array(),
            ) );
        }
        wp_send_json_success( $result );
    }

    public function export_csv() {
        check_ajax_referer( 'export_csv', 'security' );
        if ( empty( $_POST['categories'] ) || empty( $_POST['columns'] ) ) {
            $error = new WP_Error('003', __( 'Bad request', 'clickwhale' ));
            wp_send_json_error( $error );
        }
        global $wpdb;
        // Disable caching
        $now = gmdate( "D, d M Y H:i:s" );
        $date = gmdate( "Y-m-d" );
        header( "Expires: Tue, 03 Jul 2001 06:00:00 GMT" );
        header( "Cache-Control: max-age=0, no-cache, must-revalidate, proxy-revalidate" );
        header( "Last-Modified: {$now} GMT" );
        // force download
        header( "Content-Type: application/force-download" );
        header( "Content-Type: application/octet-stream" );
        header( "Content-Type: application/download" );
        // Disposition / encoding on response body
        header( "Content-Disposition: attachment;filename=clickwhale-links-export-{$date}.csv" );
        header( "Content-Transfer-Encoding: binary" );
        $allowed_columns = Helper::get_import_default_columns();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $post_columns = ( isset( $_POST['columns'] ) ? wp_unslash( $_POST['columns'] ) : '' );
        if ( $post_columns === 'all' ) {
            $headers = $allowed_columns;
        } else {
            $post_columns = (array) $post_columns;
            $headers = array_values( array_intersect( $post_columns, $allowed_columns ) );
        }
        if ( empty( $headers ) ) {
            wp_send_json_error( new WP_Error('005', __( 'No valid columns selected', 'clickwhale' )) );
        }
        $cats = '';
        $prepared_categories = array();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        $post_categories = ( isset( $_POST['categories'] ) ? wp_unslash( $_POST['categories'] ) : '' );
        if ( $post_categories !== 'all' ) {
            $post_categories = (array) $post_categories;
            $prepared_categories = array_map( function ( $cat ) use($wpdb) {
                $sanitized = sanitize_text_field( $cat );
                return '%' . $wpdb->esc_like( $sanitized ) . '%';
            }, $post_categories );
            $conditions = array_fill( 0, count( $prepared_categories ), "categories LIKE %s" );
            $cats = ' WHERE ' . implode( ' OR ', $conditions );
        }
        $links_table = $wpdb->prefix . 'clickwhale_links';
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        $sql = "SELECT " . implode( ',', $headers ) . " FROM {$links_table}";
        if ( !empty( $prepared_categories ) ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query = $wpdb->prepare( $sql . $cats, ...$prepared_categories );
        } else {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $query = $sql;
        }
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter
        $rows = $wpdb->get_results( $query, ARRAY_A );
        if ( !$rows ) {
            $error = new WP_Error('004', __( 'Nothing to export', 'clickwhale' ));
            wp_send_json_error( $error );
        }
        $merged = array_merge( array($headers), $rows );
        if ( empty( $merged ) ) {
            $error = new WP_Error('004', __( 'Nothing to export', 'clickwhale' ));
            wp_send_json_error( $error );
        }
        // Build CSV string without direct file operations
        $delimiter = ',';
        $enclosure = '"';
        $is_assoc = static function ( $arr ) : bool {
            if ( !is_array( $arr ) ) {
                return false;
            }
            return array_keys( $arr ) !== range( 0, count( $arr ) - 1 );
        };
        $csv_escape = static function ( $fields ) use($delimiter, $enclosure) : string {
            $line_parts = array();
            foreach ( $fields as $field ) {
                $field = (string) $field;
                $escaped = str_replace( $enclosure, $enclosure . $enclosure, $field );
                // Always enclose for consistency
                $line_parts[] = $enclosure . $escaped . $enclosure;
            }
            return implode( $delimiter, $line_parts ) . "\r\n";
        };
        $csv_output = '';
        foreach ( $merged as $idx => $row ) {
            if ( 0 === $idx ) {
                // Header row is already a numeric array
                $csv_output .= $csv_escape( $row );
                continue;
            }
            if ( $is_assoc( $row ) ) {
                $ordered = array();
                foreach ( $headers as $h ) {
                    $ordered[] = $row[$h] ?? '';
                }
                $csv_output .= $csv_escape( $ordered );
            } else {
                $csv_output .= $csv_escape( $row );
            }
        }
        $result['file'] = $csv_output;
        $result['filename'] = "clickwhale-links-export-{$date}.csv";
        wp_send_json_success( $result );
    }

}
