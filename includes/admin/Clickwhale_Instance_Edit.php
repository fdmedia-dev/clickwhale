<?php
namespace clickwhale\includes\admin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

abstract class Clickwhale_Instance_Edit {

	/**
	 * Instance type as plural e.g. "links", "linkpages"
	 *
	 * @var string
	 * @since 1.6.0
	 */
	public $instance_plural;

	/**
	 * Instance type as single e.g. "link", "linkpage"
	 *
	 * @var string
	 * @since 1.6.0
	 */
	public $instance_single;

    /**
     * @var string
     */
	protected $page;

    /**
     * @var string
     */
	protected $filter_param;

	public function __construct( string $instance_plural, string $instance_single ) {
		$this->instance_plural = $instance_plural;
		$this->instance_single = $instance_single;
		$this->page            = CLICKWHALE_SLUG . '-edit-' . str_replace( '_', '-', $this->instance_single );
		$this->filter_param    = ! empty ( $_GET['id'] ) ? 'edit' : 'add';

		add_action( "admin_post_save_update_clickwhale_$this->instance_single", array( $this, 'save_update' ) );

		if ( ! empty( $_GET['page'] ) && $_GET['page'] === $this->page ) {
			add_action( 'admin_print_footer_scripts', array( $this, 'admin_scripts' ) );
			add_filter( 'admin_title', array( $this, "set_{$this->filter_param}_page_title" ), 10, 2 );
		}
	}

	/**
	 * Default values for the instance
	 *
	 * @return array
	 * @since 1.6.0
	 */
	abstract public function get_defaults(): array;

	/**
	 * Get current instance
	 *
	 * @param $request
	 *
	 * @return mixed
	 * @since 1.6.0
	 */
	public function get_item( $request ) {
		if ( ! is_numeric( $request['id'] ) ) {
			$this->no_item();
		}

		// get default values
		$defaults = apply_filters( "clickwhale_{$this->instance_single}_defaults", $this->get_defaults() );

		// if item id=0 or id doesn't set/exists than use $defaults
		if ( empty( $request['id'] ) ) {
			return $defaults;
		}

		// get data by id
		$helper = ucfirst( "{$this->instance_plural}_Helper" );

		$item = call_user_func(
			array( "clickwhale\\includes\\helpers\\" . $helper, 'get_by_id' ),
			$request['id'] );

		// if link with id doesn't exist
		if ( ! $item ) {
			$this->no_item();
		}

		return $item;
	}

	/**
	 * Save or update instance
	 *
	 * @return mixed
	 * @since 1.6.0
	 */
	abstract public function save_update();

	/**
	 * Message if id wasn't found
	 *
	 * @return void
	 * @since 1.6.0
	 */
	protected function no_item() {
		wp_die(
			__( 'You attempted to edit an item that does not exist. Perhaps it was deleted?', CLICKWHALE_NAME ),
			__( 'Error', CLICKWHALE_NAME ),
			array(
				'link_text' => esc_html( __( "Back", CLICKWHALE_NAME ) ),
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
	protected function set_transient( string $id, string $value ) {
		set_transient( $this->instance_single . '-' . $id, $value, 15 ); // 15 seconds
	}

	/**
	 * Show message after save or update item
	 *
	 * @param string $id
	 *
	 * @return void
	 * @since 1.6.0
	 */
	public function show_message( string $id ) {
        if ( empty( $id ) ) {
            return;
        }

        $transient = get_transient( "$this->instance_single-$id" );

		if ( empty( $transient ) ) {
			return;
		}

		if ( $transient === 'added' ) {
			echo '<div class="updated"><p>' . ucwords( str_replace( '_', ' ', $this->instance_single ) ) . __( ' was successfully saved', CLICKWHALE_NAME ) . '</p></div>';

		} elseif ( $transient === 'updated' ) {
			echo '<div class="updated"><p>' . ucwords( str_replace( '_', ' ', $this->instance_single ) ) . __( ' was successfully updated', CLICKWHALE_NAME ) . '</p></div>';
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

        $item = $this->get_item( $_REQUEST );

		return 'Edit &#8220;' . esc_attr( wp_unslash( $item['title'] ) ) . '&#8221; ' . str_replace( '_', ' ', $this->instance_single ) . ' ' . $admin_title;
	}

	/**
	 *  Set admin page title (Add instance)
	 *
	 * @param string $admin_title
	 * @param string $title
	 *
	 * @return string
	 * @since 1.6.0
	 */
	public function set_add_page_title( string $admin_title, string $title ): string {
		return 'Add ' . ucwords( str_replace( '_', ' ', $this->instance_single ) ) . ' ' . $admin_title;
	}

	/**
	 * JS for current page
	 *
	 * @return void
	 * @since 1.6.0
	 */
	abstract public function admin_scripts(): void;
}