<?php
namespace clickwhale\includes\admin;

use WP_Error;
use WP_REST_Controller;
use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_HTTP_Response;

use clickwhale\includes\helpers\{
    Helper,
    Links_Helper,
    Linkpages_Helper,
    Categories_Helper
};

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ClickWhale REST API
 */
class Clickwhale_Rest_Controller extends WP_REST_Controller {

    public function __construct() {
        $this->namespace = 'clickwhale/v1';
        $this->rest_base = 'links';
    }

    /**
     * Registers routes
     */
    public function register_routes() {
        register_rest_route( $this->namespace,
            '/' . $this->rest_base,
            array(
                // Get all CW links
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array( $this, 'get_items' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args' => $this->get_collection_params()
                ),
                // Create CW link
                array(
                    'methods' => WP_REST_Server::CREATABLE,
                    'callback' => array( $this, 'create_item' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args' => $this->get_endpoint_args_for_item_schema()
                ),
                'schema' => array( $this, 'get_public_item_schema' )
            )
        );

        register_rest_route(
            $this->namespace,
            '/' . $this->rest_base . '/(?P<slug_or_id>.+)',
            array(
                // Get CW link by slug or by ID
                array(
                    'methods' => WP_REST_Server::READABLE,
                    'callback' => array( $this, 'get_item' ),
                    'permission_callback' => array( $this, 'permissions_check' ),
                    'args' => array(
                        'slug_or_id' => array(
                            'required' => true,
                            'type' => 'string',
                            'description' => esc_attr__( 'Unique slug or numeric ID of ClickWhale link', 'clickwhale' )
                        ),
                        'context' => $this->get_context_param(
                            array(
                                'default' => 'view'
                            )
                        )
                    )
                ),
                'schema' => array( $this, 'get_public_item_schema' )
            )
        );
    }

    #--------------------------------------------------------------------------------
    # `permission_callback` region
    #--------------------------------------------------------------------------------

    /**
     * Check if a given request has access to REST API items
     *
     * @param WP_REST_Request $request Full details about the request
     * @return WP_Error|boolean
     */
    public function permissions_check( $request ) {
        if ( clickwhale()->user->is_current_user_role_access_granted() ) {
            return true;
        }

        return new WP_Error(
            'clickwhale_rest_cannot_view_and_edit',
            __( 'Sorry, you are not allowed to view and edit links via REST API.', 'clickwhale' ),
            array( 'status' => rest_authorization_required_code() )
        );
    }

    #--------------------------------------------------------------------------------
    # `callback` region
    #--------------------------------------------------------------------------------

    /**
     * Get all CW links
     *
     * @param   WP_REST_Request $request
     * @return  WP_REST_Response|WP_HTTP_Response|WP_Error
     */
    public function get_items( $request ) {
        $items = array();
        $links = Links_Helper::get_links_with_click_data();

        if ( ! empty( $links ) ) {
            foreach ( $links as $link ) {
                //$request->set_param( 'context', 'view' );
                $item = $this->prepare_item_for_response( $link, $request );

                // If there's an error loading a collection, skip it and continue loading valid collections
                if ( is_wp_error( $item ) ) {
                    continue;
                }

                $items[] = $this->prepare_response_for_collection( $item );
            }
        }

        return rest_ensure_response( $items );
    }

    /**
     * Get CW link by given slug
     *
     * @param   WP_REST_Request $request
     * @return  WP_REST_Response|WP_HTTP_Response|WP_Error
     */
    public function get_item( $request ) {
        $slug_or_id = $request->get_param( 'slug_or_id' );

        if ( preg_match( '/^[1-9][0-9]*$/', $slug_or_id ) ) {
            $link = Links_Helper::get_link_by_id_with_click_data( $slug_or_id );

        } else {
            $slug = Links_Helper::sanitize_slug( $slug_or_id );

            if ( '' === $slug ) {
                return new WP_Error( 'invalid_cw_link_slug_or_id',
                    __( 'Provided link slug or ID is not valid.', 'clickwhale' ),
                    array( 'status' => 400 )
                );
            }

            $link = Links_Helper::get_link_by_slug_with_click_data( $slug );
        }

        if ( empty( $link ) ) {
            return new WP_Error( 'cw_link_not_found',
                __( 'The requested ClickWhale link was not found.', 'clickwhale' ),
                array( 'status' => 404 )
            );
        }

        return $this->prepare_item_for_response( $link, $request );
    }

    /**
     * Create CW link
     *
     * @param   WP_REST_Request $request
     * @return  WP_REST_Response|WP_HTTP_Response|WP_Error
     */
    public function create_item( $request ) {
        $schema = $this->get_item_schema();
        $params = array_intersect_key( $request->get_params(), $schema['properties'] );
        $slug = Links_Helper::sanitize_slug( $params['slug'] );

        if ( '' === $slug ) {
            return new WP_Error( 'slug_is_not_valid',
                __( 'The requested slug is not valid.', 'clickwhale' ),
                array( 'status' => 400 )
            );
        }

        /** Check if this slug already exists in WP */

        // 1. Search in CW `links` table
        $slug_match = Links_Helper::get_by_slug( $slug );

        if ( ! empty( $slug_match ) ) {
            return new WP_Error( 'cw_link_exists',
                __( 'ClickWhale link with this slug already exists.', 'clickwhale' ),
                array( 'status' => 409 )
            );
        }

        // 2. Search in CW `linkpages` table
        $slug_match = Linkpages_Helper::get_by_slug( $slug );

        if ( ! empty( $slug_match ) ) {
            return new WP_Error( 'cw_linkpage_exists',
                __( 'ClickWhale link page with this slug already exists.', 'clickwhale' ),
                array( 'status' => 409 )
            );
        }

        // 3. Search in WP `posts` table
        $slug_match = Helper::get_post_by_slug( $slug );

        if ( ! empty( $slug_match ) ) {
            return new WP_Error( 'wp_post_exists',
                sprintf(
                    /* translators: %s: matched resource type (e.g. post type or taxonomy) */
                    __( 'WordPress %s with this slug already exists.', 'clickwhale' ),
                    $slug_match['type']
                ),
                array( 'status' => 409 )
            );
        }

        // 4. Search in WP taxonomies
        $slug_match = Helper::get_taxonomy_by_slug( $slug );

        if ( ! empty( $slug_match ) ) {
            return new WP_Error( 'wp_taxonomy_exists',
                sprintf(
                    /* translators: %s: matched resource type (e.g. post type or taxonomy) */
                    __( 'WordPress %s with this slug already exists.', 'clickwhale' ),
                    $slug_match['type']
                ),
                array( 'status' => 409 )
            );
        }

        // 5. HTTP request to URL: check if slug is handled by custom endpoints, rewrite rules, .htaccess rules, etc.
        $slug_match = wp_remote_get( home_url( $slug ), [ 'timeout' => 2 ] );

        if ( ! is_wp_error( $slug_match ) && 200 === wp_remote_retrieve_response_code( $slug_match ) ) {
            return new WP_Error( 'wp_custom_endpoint_exists',
                __( 'WordPress custom endpoint with this slug already exists.', 'clickwhale' ),
                array( 'status' => 409 )
            );
        }

        global $wpdb;
        $link = array(
            'slug'           => $slug,
            'title'          => $params['title'],
            'url'            => $params['target_url'],
            'redirection'    => $params['redirection'],
            'link_target'    => $params['link_target'],
            'nofollow'       => $params['nofollow'],
            'sponsored'      => $params['sponsored'],
            'description'    => $params['description'],
            'created_by_api' => true
        );

        $categories = array();
        $all_cats = Categories_Helper::get_all();

        foreach ( $all_cats as $category ) {
            $categories[] = intval( $category->id );
        }

        $link['categories'] = implode( ',', array_intersect( $params['categories'], $categories ) );
        $link['author'] = get_current_user_id();

        $current_date = gmdate( 'Y-m-d H:i:s' );
        $link['created_at'] = $current_date;
        $link['updated_at'] = $current_date;

        $wpdb->insert(
            Helper::get_db_table_name( 'links' ),
            $link
        );

        $id = $wpdb->insert_id;

        if ( 0 === $id ) {
            return new WP_Error( 'cw_link_cannot_create',
                __( 'There was an error saving the ClickWhale link.', 'clickwhale' ),
                array( 'status' => 500 )
            );
        }

        $link['id'] = $id;
        //$request->set_param( 'context', 'edit' );
        $response = $this->prepare_item_for_response( $link, $request );

        // 201 Created
        $response->set_status( 201 );
        $response->header( 'Location', rest_url( sprintf( '/%s/%s/%d', $this->namespace, $this->rest_base, $id ) ) );

        return $response;
    }

    #--------------------------------------------------------------------------------
    # Misc
    #--------------------------------------------------------------------------------

    /**
     * Prepares item for REST response
     *
     * @param mixed $item
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
     */
    public function prepare_item_for_response( $item, $request ) {
        $clicks = $item['clicks_count'] ?? 0;

        return rest_ensure_response( array(
            'id'             => (int)    $item['id'],
            'slug'           => (string) $item['slug'],
            'url'            => home_url( $item['slug'] ),
            'target_url'     => (string) $item['url'],
            'title'          => (string) $item['title'],
            'redirection'    => (int)    $item['redirection'],
            'link_target'    => (string) $item['link_target'],
            'nofollow'       => (bool)   $item['nofollow'],
            'sponsored'      => (bool)   $item['sponsored'],
            'description'    => (string) $item['description'],
            'categories'     => (string) $item['categories'],
            'clicks'         => (int)    $clicks,
            'author'         => (int)    $item['author'],
            'created_by_api' => (bool)   $item['created_by_api'],
            'created_at'     => (string) $item['created_at'],
            'updated_at'     => (string) $item['updated_at']
        ) );
    }

    /**
     * Get the Link schema, conforming to JSON Schema
     *
     * @return array
     */
    public function get_item_schema(): array {
        if ( $this->schema ) {
            return $this->add_additional_fields_schema( $this->schema );
        }

        $defaults = clickwhale()->settings->default_options();
        $link_manager_options = get_option( 'clickwhale_link_manager_options' );

        $schema = array(
            '$schema'    => 'http://json-schema.org/draft-04/schema#',
            'title'      => 'cw-link',
            'type'       => 'object',
            'properties' => array(
                'slug_or_id' => array(
                    'description' => __( 'Unique slug or numeric ID of ClickWhale link', 'clickwhale' ),
                    'type'        => 'string',
                    'maxLength'   => 255,
                    'context'     => array( 'view' )
                ),
                'slug' => array(
                    'description' => __( 'Unique slug of ClickWhale link', 'clickwhale' ),
                    'type'        => 'string',
                    'maxLength'   => 255,
                    'required'    => true,
                    'context'     => array( 'edit' )
                ),
                'target_url' => array(
                    'description' => __( 'Target URL', 'clickwhale' ),
                    'type'        => 'string',
                    'format'      => 'uri',
                    'minLength'   => 1,
                    'maxLength'   => 1000,
                    'required'    => true,
                    'context'     => array( 'edit' )
                ),
                'title' => array(
                    'description' => __( 'Link title', 'clickwhale' ),
                    'type'        => 'string',
                    'minLength'   => 1,
                    'maxLength'   => 255,
                    'required'    => true,
                    'context'     => array( 'edit' )
                ),
                'redirection' => array(
                    'description' => __( 'Redirection type', 'clickwhale' ),
                    'type'        => 'integer',
                    'default'     => $link_manager_options['redirect_type'] ?? $defaults['link_manager']['options']['redirect_type'],
                    'enum'        => array_keys( Links_Helper::get_redirections() ),
                    'context'     => array( 'edit' )
                ),
                'link_target' => array(
                    'description' => __( 'Link target', 'clickwhale' ),
                    'type'        => 'string',
                    'default'     => $link_manager_options['link_target'] ?? $defaults['link_manager']['options']['link_target'],
                    'enum'        => array_keys( Links_Helper::get_link_targets() ),
                    'context'     => array( 'edit' )
                ),
                'nofollow' => array(
                    'description' => __( 'Mark links as nofollow & noindex', 'clickwhale' ),
                    'type'        => 'boolean',
                    'default'     => false,
                    'context'     => array( 'edit' )
                ),
                'sponsored' => array(
                    'description' => __( 'Mark links as sponsored', 'clickwhale' ),
                    'type'        => 'boolean',
                    'default'     => false,
                    'context'     => array( 'edit' )
                ),
                'description' => array(
                    'description' => __( 'Link description', 'clickwhale' ),
                    'type'        => 'string',
                    'default'     => '',
                    'maxLength'   => 255,
                    'context'     => array( 'edit' )
                ),
                'categories' => array(
                    'description' => __( 'Link categories', 'clickwhale' ),
                    'type'        => 'array',
                    'default'     => array(),
                    'items'       => array(
                        'type'     => 'integer',
                        'minimum'  => 1
                    ),
                    'maxItems'    => 100,
                    'uniqueItems' => true,
                    'context'     => array( 'edit' )
                )
            )
        );

        $this->schema = $schema;

        return $this->add_additional_fields_schema( $this->schema );
    }
}
