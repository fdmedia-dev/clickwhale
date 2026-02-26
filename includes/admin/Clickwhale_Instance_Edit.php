<?php
namespace clickwhale\includes\admin;

use clickwhale\includes\helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Clickwhale_Instance_Edit {

    /**
     * Instance type as plural e.g. "links", "linkpages", "tracking_codes"
     *
     * @var string
     * @since 1.6.0
     */
    public string $instance_plural;

    /**
     * Instance type as single e.g. "link", "linkpage", "tracking_code"
     *
     * @var string
     * @since 1.6.0
     */
    public string $instance_single;

    /**
     * Helper class name
     *
     * @var class-string
     */
    protected string $instance_helper;

    /**
     * @var string
     */
    private string $page;

    public function __construct() {
        $this->page = CLICKWHALE_SLUG . '-edit-' . str_replace( '_', '-', $this->instance_single );

        add_action( "admin_post_save_update_clickwhale_{$this->instance_single}", array( $this, 'save_update' ) );

        $get_page_raw = filter_input( INPUT_GET, 'page', FILTER_SANITIZE_FULL_SPECIAL_CHARS );
        $get_page = $get_page_raw ? sanitize_key( $get_page_raw ) : '';
        if ( $get_page === $this->page ) {
            add_action( 'admin_print_footer_scripts', array( $this, 'admin_scripts' ) );
            add_filter( 'admin_title', array( $this, 'set_edit_page_title' ), 10, 2 );
        }
    }

    /**
     * Localized human readable label for instance title
     *
     * @return string
     */
    abstract protected function get_title_i18n(): string;

    /**
     * Default values for the instance
     *
     * @return array
     * @since 1.6.0
     */
    abstract public function get_defaults(): array;

    /**
     * JS for current page
     *
     * @return void
     * @since 1.6.0
     */
    abstract public function admin_scripts(): void;

    /**
     * Allows child classes to adjust loaded item data before returning it
     *
     * @param array $item
     * @return array
     */
    protected function normalize_loaded_item( array $item ): array {
        return $item;
    }

    /**
     * Get current instance
     *
     * @param $request
     * @return array
     * @since 1.6.0
     */
    public function get_item( $request ): array {
        if ( ! is_numeric( $request['id'] ) ) {
            $this->no_item();
        }

        if ( empty( $request['id'] ) ) {
            return apply_filters( "clickwhale_{$this->instance_single}_defaults", $this->get_defaults() );
        }

        $item = $this->instance_helper::get_by_id( intval( $request['id'] ) );

        if ( ! $item ) {
            $this->no_item();
        }

        return $this->normalize_loaded_item( $item );
    }

    /**
     * Message if id wasn't found
     *
     * @return void
     * @since 1.6.0
     */
    private function no_item(): void {
        wp_die(
            esc_html__( 'You attempted to edit an item that does not exist. Perhaps it was deleted?', 'clickwhale' ),
            esc_html__( 'Error', 'clickwhale' ),
            array(
                'link_text' => esc_html__( 'Back', 'clickwhale' )
            )
        );
    }

    /**
     * Set transient for saved/updated item for the message
     *
     * @param string $id
     * @param string $value
     *
     * @return void
     * @since 1.6.0
     */
    protected function set_transient( string $id, string $value ): void {
        set_transient( sanitize_key( $this->instance_single . '-' . $id ), esc_html( $value ), 15 ); // 15 seconds
    }

    /**
     * Show message after save or update item
     *
     * @param string $id
     *
     * @return void
     * @since 1.6.0
     */
    public function show_message( string $id ): void {
        if ( empty( $id ) ) {
            return;
        }

        $transient = get_transient( "$this->instance_single-$id" );

        if ( empty( $transient ) ) {
            return;
        }

        if ( $transient === 'added' ) {
            echo '<div class="updated"><p>';
            printf(
                /* translators: %s: item title */
                esc_html__( '%s was successfully saved', 'clickwhale' ),
                esc_html( $this->get_title_i18n() )
            );
            echo '</p></div>';

        } elseif ( $transient === 'updated' ) {
            echo '<div class="updated"><p>';
            printf(
                /* translators: %s: item title */
                esc_html__( '%s was successfully updated', 'clickwhale' ),
                esc_html( $this->get_title_i18n() )
            );
            echo '</p></div>';
        }

        delete_transient( "$this->instance_single-$id" );
    }

    /**
     * Set admin page title (Edit instance)
     *
     * @param string $admin_title
     * @param string $title
     *
     * @return string
     * @since 1.6.0
     */
    public function set_edit_page_title( string $admin_title, string $title ): string {
        $id_raw = filter_input( INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT );
        $id = $id_raw ? intval( $id_raw ) : 0;
        if ( empty( $id ) ) {
            return $admin_title;
        }

        $item = $this->get_item( array( 'id' => $id ) );

        return sprintf(
            /* translators: %1$s: item title, %2$s: instance title (e.g. Link Page, Category) */
            esc_html__( 'Edit "%1$s" %2$s', 'clickwhale' ),
            esc_html( wp_unslash( $item['title'] ) ),
            esc_html( $this->get_title_i18n() )
        ) . str_replace( $title, '', $admin_title );
    }

    protected function filter_defaults( array $defaults ): array {
        return $defaults;
    }

    /**
     * Allows child classes to adjust item data before saving
     *
     * @param array $item
     * @return array
     */
    protected function process_item_before_save( array $item ): array {
        return $item;
    }

    /**
     * Hook for child classes to execute logic after saving the item
     *
     * @param int   $id
     * @param array $raw
     * @return void
     */
    protected function after_save( int $id, array $raw ): void {}

    /**
     * Save or update instance
     *
     * @return void
     */
    public final function save_update(): void {
        check_admin_referer( basename( $this->instance_single . '/edit.php' ), 'nonce' );

        global $wpdb;
        $table = Helper::get_db_table_name( $this->instance_plural );
        $defaults = $this->filter_defaults( $this->get_defaults() );
        $item = array();
        foreach ( $defaults as $key => $default_value ) {
            if ( isset( $_POST[ $key ] ) ) {
                $item[ $key ] = wp_unslash( $_POST[ $key ] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
            }
        }
        $item = $this->process_item_before_save( $item );
        $id = isset( $item['id'] ) ? intval( $item['id'] ) : 0;

        if ( $this->instance_helper::get_by_id( $id ) ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update(
                $table,
                $item,
                array( 'id' => $id )
            );
            do_action( "clickwhale_{$this->instance_single}_updated", $id, wp_unslash( $_POST ) );
            $this->set_transient( $id, 'updated' );

        } else {
            unset( $item['id'] );
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert( $table, $item );
            $id = $wpdb->insert_id;
            do_action( "clickwhale_{$this->instance_single}_inserted", $id, wp_unslash( $_POST ) );
            $this->set_transient( $id, 'added' );
        }

        $this->after_save( $id, wp_unslash( $_POST ) );

	    wp_safe_redirect(
            esc_url_raw(
                admin_url( 'admin.php?page=' . $this->page . '&id=' . $id )
            )
        );
        exit;
    }
}
