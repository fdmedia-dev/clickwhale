<?php
/**
 * Minn Admin integration.
 *
 * Registers two ClickWhale surfaces inside the Minn Admin plugin
 * (https://wordpress.org/plugins/minn-admin/), following its documented
 * `minn_admin_surfaces` contract (see that plugin's docs/for-plugin-authors.md).
 * This file is required unconditionally from Clickwhale::get_instance():
 * when Minn Admin isn't installed, `minn_admin_surfaces` and the
 * `rest_api_init` callback below are simply never fired, so this is a free
 * no-op.
 *
 * ClickWhale gates access through its own configurable role list rather
 * than a single WP capability (Clickwhale_WP_User::is_current_user_role
 * _access_granted(): admins always pass, other roles opt in via Settings →
 * General → Access) — the same gate ClickWhale's own admin menu uses.
 * Adapter-side gating pattern: `cap` stays loose ('read') so both surfaces
 * below reach everyone ClickWhale itself would allow in, and every route's
 * permission_callback calls that same resolver.
 *
 * Routes live under ClickWhale's own `clickwhale/v1` REST namespace (not
 * Minn's) since this integration ships as part of ClickWhale itself, nested
 * under `/minn-admin/…` to stay clear of ClickWhale's own `links` route.
 *
 * == Links ==
 *
 * Links live in {prefix}clickwhale_links; clicks are individual event rows
 * in {prefix}clickwhale_track, summed per link exactly like ClickWhale's own
 * Links_Helper::query_links_with_click_data() does, and bucketed by day for
 * the status chart. ClickWhale ships a REST controller (clickwhale/v1/links)
 * but it only covers read-all + create, with no pagination, search, edit or
 * delete, so this is a table shim using ClickWhale's own
 * Helper::get_db_table_name() for table names and Links_Helper's
 * enums/sanitizers for parity with its admin screens. Edit and delete mirror
 * the plugin's own handlers (Clickwhale_Link_Edit::save_update(), the list
 * table's delete bulk action): same columns written, same `meta` cleanup on
 * delete. Create and edit deliberately do NOT fire ClickWhale's own
 * `clickwhale_link_inserted` / `clickwhale_link_updated` hooks — verified
 * live that ClickWhale Pro's own listener on those hooks
 * (Clickwhale_Pro_Link_Edit::insert_link_meta/update_link_meta, shipped
 * right in this repo's pro/ folder) expects the exact $_POST shape from its
 * own admin form (UTM fields present as empty strings, not absent) and
 * fatals on anything else. `clickwhale_link_deleted` IS fired (with a plain
 * array of ids) because both the free listener and Pro's
 * `delete_link_keywords( array $ids )` already expect exactly that shape —
 * checked before keeping it.
 *
 * == Smart Boxes (ClickWhale calls these "Smart Displays") ==
 *
 * A small promotional card/CTA for one Link, built via a drag-and-drop
 * builder (border styles, font sizes, an image picker, per-integration
 * embed data in {prefix}clickwhale_smart_display_data) that has no
 * equivalent in Minn's generic form vocabulary — same pattern as AAWP's
 * Comparison Tables: the list here (name, status, which Link it promotes,
 * shortcode, created date) is genuinely useful, and the two safe text
 * fields (`name`, `title`) are inline-editable, but the builder itself
 * stays an honest deep link ("Edit ↗") to ClickWhale's own screen. Delete
 * and Duplicate mirror Clickwhale_Smart_Displays_List_Table's own bulk
 * actions exactly (same tables, same "(copy)" suffix rule, same
 * smart_display_data row copy) — neither fires an action hook, matching
 * that source. The free tier caps these at 10
 * (Smart_Displays_Helper::get_limit()), but the list route still paginates
 * properly rather than assuming that ceiling holds under Pro.
 *
 * @package Clickwhale
 */

defined( 'ABSPATH' ) || exit;

/** True while the current user is inside ClickWhale's own configured access-role list. */
function clickwhale_minn_admin_can_view() {
	return clickwhale()->user->is_current_user_role_access_granted();
}

/**
 * Normalize a links-table row (joined with click counts) for the surface.
 *
 * @param object $row Joined links + click-count row.
 * @return array
 */
function clickwhale_minn_admin_link_item( $row ) {
	return array(
		'id'          => (int) $row->id,
		'title'       => (string) $row->title,
		'url'         => home_url( $row->slug ),
		'target_url'  => (string) $row->url,
		'redirection' => (int) $row->redirection,
		'link_target' => (string) $row->link_target,
		'description' => (string) $row->description,
		'clicks'      => isset( $row->clicks_count ) ? (int) $row->clicks_count : 0,
		'created_at'  => (string) $row->created_at,
		'updated_at'  => (string) $row->updated_at,
	);
}

/**
 * Validate + coerce a submitted link; WP_Error on bad input.
 *
 * @param WP_REST_Request $request Request carrying the submitted fields.
 * @return array|WP_Error
 */
function clickwhale_minn_admin_link_payload( WP_REST_Request $request ) {
	$title  = sanitize_text_field( (string) $request['title'] );
	$target = esc_url_raw( trim( (string) $request['target_url'] ) );
	// Validate after sanitizing: esc_url_raw() returns '' for unsupported schemes such as javascript:.
	if ( '' === trim( $title ) || '' === $target ) {
		return new WP_Error( 'invalid', __( 'Title and a valid target URL are both required.', 'clickwhale' ), array( 'status' => 400 ) );
	}
	$redirections = array_keys( \Clickwhale\Helpers\Links_Helper::get_redirections() );
	$targets      = array_keys( \Clickwhale\Helpers\Links_Helper::get_link_targets() );
	$redirection  = (int) $request['redirection'];
	$link_target  = (string) $request['link_target'];
	return array(
		'title'       => $title,
		'url'         => $target,
		'redirection' => in_array( $redirection, $redirections, true ) ? $redirection : $redirections[0],
		'link_target' => in_array( $link_target, $targets, true ) ? $link_target : 'blank',
		'description' => sanitize_textarea_field( (string) $request['description'] ),
	);
}

/**
 * Random slug that isn't used by another link, link page, post or term,
 * mirroring the checks in Clickwhale_Rest_Controller::create_item().
 *
 * @return string
 */
function clickwhale_minn_admin_unique_slug(): string {
	do {
		$slug = \Clickwhale\Helpers\Links_Helper::generate_random_slug();
	} while (
		\Clickwhale\Helpers\Links_Helper::get_by_slug( $slug )
		|| \Clickwhale\Helpers\Linkpages_Helper::get_by_slug( $slug )
		|| \Clickwhale\Helpers\Helper::get_post_by_slug( $slug )
		|| \Clickwhale\Helpers\Helper::get_taxonomy_by_slug( $slug )
	);

	return $slug;
}

/**
 * Normalize a smart_displays row for the surface: status derived exactly
 * like the plugin's own list table.
 *
 * @param array $row Smart-display row (ARRAY_A).
 * @return array
 */
function clickwhale_minn_admin_smart_display_item( $row ) {
	$promotes = '';
	if ( ! empty( $row['link_id'] ) ) {
		$link = \Clickwhale\Helpers\Links_Helper::get_by_id( (int) $row['link_id'] );
		if ( ! empty( $link['title'] ) ) {
			$promotes = (string) $link['title'];
		}
	}
	return array(
		'id'         => (int) $row['id'],
		'name'       => (string) $row['name'],
		'title'      => (string) $row['title'],
		'status'     => '' !== (string) $row['title'] ? 'active' : 'inactive',
		'promotes'   => $promotes,
		'shortcode'  => \Clickwhale\Helpers\Smart_Displays_Helper::get_shortcode( (int) $row['id'] ),
		'created_at' => (string) $row['created_at'],
	);
}

add_filter(
	'minn_admin_surfaces',
	function ( $surfaces ) {
		$redirection_options = array();
		foreach ( \Clickwhale\Helpers\Links_Helper::get_redirections() as $code => $label ) {
			$redirection_options[] = array( (string) $code, $label );
		}
		$target_options = array();
		foreach ( \Clickwhale\Helpers\Links_Helper::get_link_targets() as $value => $label ) {
			$target_options[] = array( $value, $label );
		}

		$surfaces['clickwhale-links'] = array(
			'label'      => __( 'Links', 'clickwhale' ),
			'family'     => 'redirects',
			'sub'        => 'ClickWhale',
			'icon'       => 'link',
			'cap'        => 'read',
			'status'     => array( 'route' => 'clickwhale/v1/minn-admin/links/status' ),
			'collection' => array(
				'route'    => 'clickwhale/v1/minn-admin/links',
				'itemsKey' => 'items',
				'totalKey' => 'total',
				'search'   => 'search={q}',
				'columns'  => array(
					// Explicit widths: 'mono' otherwise falls back to a fixed
					// 84px, truncating both the short link and the target URL.
					array(
						'key'    => 'title',
						'label'  => __( 'Title', 'clickwhale' ),
						'format' => 'title',
						'width'  => 'minmax(0,1fr)',
					),
					array(
						'key'    => 'url',
						'label'  => __( 'Short link', 'clickwhale' ),
						'format' => 'mono',
						'width'  => 'minmax(140px,1fr)',
					),
					array(
						'key'    => 'target_url',
						'label'  => __( 'Target', 'clickwhale' ),
						'format' => 'mono',
						'width'  => 'minmax(160px,1.3fr)',
					),
					array(
						'key'    => 'clicks',
						'label'  => __( 'Clicks', 'clickwhale' ),
						'format' => 'num',
						'width'  => '80px',
					),
					array(
						'key'    => 'created_at',
						'label'  => __( 'Created', 'clickwhale' ),
						'format' => 'ago',
					),
				),
				'create'   => array(
					'label'  => __( 'Add link', 'clickwhale' ),
					'route'  => 'clickwhale/v1/minn-admin/links',
					'method' => 'POST',
					'fields' => array(
						array(
							'key'   => 'title',
							'label' => __( 'Title', 'clickwhale' ),
						),
						array(
							'key'   => 'target_url',
							'label' => __( 'Target URL', 'clickwhale' ),
							'type'  => 'url',
							'mono'  => true,
						),
						array(
							'key'     => 'redirection',
							'label'   => __( 'Redirect type', 'clickwhale' ),
							'type'    => 'select',
							'options' => $redirection_options,
						),
						array(
							'key'     => 'link_target',
							'label'   => __( 'Open in', 'clickwhale' ),
							'type'    => 'select',
							'options' => $target_options,
						),
						array(
							'key'      => 'description',
							'label'    => __( 'Description', 'clickwhale' ),
							'type'     => 'textarea',
							'required' => false,
						),
					),
				),
				'detail'   => array(
					'skip' => array( 'id' ),
					'edit' => array(
						'route'  => 'clickwhale/v1/minn-admin/links/{id}',
						'method' => 'PUT',
						'fields' => array(
							array(
								'key'   => 'title',
								'label' => __( 'Title', 'clickwhale' ),
							),
							array(
								'key'   => 'target_url',
								'label' => __( 'Target URL', 'clickwhale' ),
								'mono'  => true,
							),
							array(
								'key'     => 'redirection',
								'label'   => __( 'Redirect type', 'clickwhale' ),
								'type'    => 'select',
								'options' => $redirection_options,
							),
							array(
								'key'     => 'link_target',
								'label'   => __( 'Open in', 'clickwhale' ),
								'type'    => 'select',
								'options' => $target_options,
							),
							array(
								'key'   => 'description',
								'label' => __( 'Description', 'clickwhale' ),
								'type'  => 'textarea',
							),
						),
					),
				),
				'actions'  => array(
					array(
						'label' => __( 'Open short link', 'clickwhale' ),
						'href'  => '{url}',
					),
					array(
						'label' => __( 'Visit target', 'clickwhale' ),
						'href'  => '{target_url}',
					),
					array(
						'label' => __( 'Edit in ClickWhale', 'clickwhale' ) . ' ↗',
						'href'  => admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-link&id={id}' ),
					),
					array(
						'label'   => __( 'Delete link', 'clickwhale' ),
						'method'  => 'DELETE',
						'route'   => 'clickwhale/v1/minn-admin/links/{id}',
						'confirm' => __( 'Delete this link permanently?', 'clickwhale' ),
						'danger'  => true,
					),
				),
				'bulk'     => array(
					array(
						'label'   => __( 'Delete', 'clickwhale' ),
						'method'  => 'DELETE',
						'route'   => 'clickwhale/v1/minn-admin/links/{id}',
						'confirm' => __( 'Delete the selected links permanently?', 'clickwhale' ),
						'danger'  => true,
					),
				),
			),
		);

		$surfaces['clickwhale-smart-displays'] = array(
			'label'      => __( 'Smart Boxes', 'clickwhale' ),
			'sub'        => 'ClickWhale',
			'icon'       => 'block',
			'cap'        => 'read',
			'collection' => array(
				'route'    => 'clickwhale/v1/minn-admin/smart-displays',
				'itemsKey' => 'items',
				'totalKey' => 'total',
				'search'   => 'search={q}',
				'columns'  => array(
					array(
						'key'    => 'name',
						'label'  => __( 'Name', 'clickwhale' ),
						'format' => 'title',
						'width'  => 'minmax(0,1fr)',
					),
					array(
						'key'    => 'status',
						'label'  => __( 'Status', 'clickwhale' ),
						'format' => 'pill',
						'width'  => '100px',
					),
					array(
						'key'   => 'promotes',
						'label' => __( 'Promotes', 'clickwhale' ),
						'width' => 'minmax(0,1fr)',
					),
					array(
						'key'    => 'shortcode',
						'label'  => __( 'Shortcode', 'clickwhale' ),
						'format' => 'mono',
						'width'  => 'minmax(140px,1fr)',
					),
					array(
						'key'    => 'created_at',
						'label'  => __( 'Created', 'clickwhale' ),
						'format' => 'ago',
					),
				),
				'detail'   => array(
					'skip' => array( 'id', 'shortcode' ),
					'edit' => array(
						'route'  => 'clickwhale/v1/minn-admin/smart-displays/{id}',
						'method' => 'PUT',
						'fields' => array(
							array(
								'key'   => 'name',
								'label' => __( 'Name', 'clickwhale' ),
							),
							array(
								'key'   => 'title',
								'label' => __( 'Title', 'clickwhale' ),
							),
						),
					),
				),
				'actions'  => array(
					array(
						'label' => __( 'Edit in ClickWhale', 'clickwhale' ) . ' ↗',
						'href'  => admin_url( 'admin.php?page=' . CLICKWHALE_SLUG . '-edit-smart-display&id={id}' ),
					),
					array(
						'label'  => __( 'Duplicate', 'clickwhale' ),
						'method' => 'POST',
						'route'  => 'clickwhale/v1/minn-admin/smart-displays/{id}/duplicate',
					),
					array(
						'label'   => __( 'Delete', 'clickwhale' ),
						'method'  => 'DELETE',
						'route'   => 'clickwhale/v1/minn-admin/smart-displays/{id}',
						'confirm' => __( 'Delete this smart box permanently?', 'clickwhale' ),
						'danger'  => true,
					),
				),
				'bulk'     => array(
					array(
						'label'   => __( 'Delete', 'clickwhale' ),
						'method'  => 'DELETE',
						'route'   => 'clickwhale/v1/minn-admin/smart-displays/{id}',
						'confirm' => __( 'Delete the selected smart boxes permanently?', 'clickwhale' ),
						'danger'  => true,
					),
				),
			),
		);

		return $surfaces;
	}
);

add_action(
	'rest_api_init',
	function () {
		$perm = function () {
			return clickwhale_minn_admin_can_view();
		};

		// Status card: totals + a 14-day daily click chart from the track table.
		register_rest_route(
			'clickwhale/v1',
			'/minn-admin/links/status',
			array(
				'methods'             => 'GET',
				'permission_callback' => $perm,
				'callback'            => function () {
					global $wpdb;
					$links_t = \Clickwhale\Helpers\Helper::get_db_table_name( 'links' );
					$track_t = \Clickwhale\Helpers\Helper::get_db_table_name( 'track' );
					$cats_t  = \Clickwhale\Helpers\Helper::get_db_table_name( 'categories' );
					// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table names are ClickWhale's own prefix-built names, not user input.
					if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $links_t ) ) ) {
						return rest_ensure_response( array( 'rows' => array() ) );
					}
					$links     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$links_t}" );
					$has_track = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $track_t ) );
					$has_cats  = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $cats_t ) );
					$rows      = array(
						array(
							'label' => __( 'Links', 'clickwhale' ),
							'value' => number_format_i18n( $links ),
						),
					);
					if ( $has_cats ) {
						$cats   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$cats_t}" );
						$rows[] = array(
							'label' => __( 'Categories', 'clickwhale' ),
							'value' => number_format_i18n( $cats ),
						);
					}
					$out = array( 'rows' => $rows );
					if ( $has_track ) {
						// created_at is stored in UTC.
						$since   = gmdate( 'Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS );
						$clicks7 = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$track_t} WHERE event_type = 'click' AND created_at >= %s", $since ) );
						$total   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$track_t} WHERE event_type = 'click'" );
						$out['rows'][] = array(
							'label' => __( 'Clicks, 7 days', 'clickwhale' ),
							'value' => number_format_i18n( $clicks7 ),
						);
						$out['rows'][] = array(
							'label' => __( 'Clicks, all time', 'clickwhale' ),
							'value' => number_format_i18n( $total ),
						);

						$days   = 14;
						$start  = strtotime( gmdate( 'Y-m-d' ) ) - ( $days - 1 ) * DAY_IN_SECONDS;
						$startd = gmdate( 'Y-m-d 00:00:00', $start );
						$daily  = $wpdb->get_results( $wpdb->prepare( "SELECT DATE(created_at) AS d, COUNT(*) AS c FROM {$track_t} WHERE event_type = 'click' AND created_at >= %s GROUP BY DATE(created_at)", $startd ) );
						// phpcs:enable
						$map    = array();
						foreach ( (array) $daily as $r ) {
							$map[ (string) $r->d ] = (int) $r->c;
						}
						$points = array();
						for ( $i = 0; $i < $days; $i++ ) {
							$day      = gmdate( 'Y-m-d', $start + $i * DAY_IN_SECONDS );
							$points[] = array(
								'label' => gmdate( 'M j', strtotime( $day ) ),
								'value' => isset( $map[ $day ] ) ? $map[ $day ] : 0,
							);
						}
						$out['chart'] = array(
							'title'   => __( 'Last 14 days', 'clickwhale' ),
							'primary' => __( 'Clicks', 'clickwhale' ),
							'points'  => $points,
						);
					}
					$out['actions'] = array(
						array(
							'label' => __( 'Open ClickWhale', 'clickwhale' ),
							'href'  => admin_url( 'admin.php?page=' . CLICKWHALE_SLUG ),
						),
					);
					return rest_ensure_response( $out );
				},
			)
		);

		register_rest_route(
			'clickwhale/v1',
			'/minn-admin/links',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => $perm,
					'callback'            => function ( WP_REST_Request $request ) {
						global $wpdb;
						$links_t  = \Clickwhale\Helpers\Helper::get_db_table_name( 'links' );
						$track_t  = \Clickwhale\Helpers\Helper::get_db_table_name( 'track' );
						$search   = trim( (string) $request['search'] );
						$page     = max( 1, (int) ( $request['page'] ? $request['page'] : 1 ) );
						$per_page = 25;
						$where    = '1=1';
						$args     = array();
						if ( '' !== $search ) {
							$like   = '%' . $wpdb->esc_like( $search ) . '%';
							$where .= ' AND (links.title LIKE %s OR links.slug LIKE %s OR links.url LIKE %s)';
							$args   = array( $like, $like, $like );
						}
						// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- table names are ClickWhale's own prefix-built names, $where is prepared above.
						$join = "LEFT JOIN ( SELECT link_id, COUNT(*) clicks FROM {$track_t} WHERE event_type = 'click' GROUP BY link_id ) track ON links.id = track.link_id";
						$total = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$links_t} links WHERE {$where}", $args ) : "SELECT COUNT(*) FROM {$links_t} links WHERE {$where}" );
						$sql   = "SELECT links.*, COALESCE(track.clicks,0) AS clicks_count FROM {$links_t} links {$join} WHERE {$where} ORDER BY links.id DESC LIMIT %d OFFSET %d";
						$rows  = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $args, array( $per_page, ( $page - 1 ) * $per_page ) ) ) );
						// phpcs:enable
						return rest_ensure_response(
							array(
								'items' => array_map( 'clickwhale_minn_admin_link_item', (array) $rows ),
								'total' => $total,
							)
						);
					},
				),
				array(
					'methods'             => 'POST',
					'permission_callback' => $perm,
					'callback'            => function ( WP_REST_Request $request ) {
						global $wpdb;
						$data = clickwhale_minn_admin_link_payload( $request );
						if ( is_wp_error( $data ) ) {
							return $data;
						}
						$data['slug'] = clickwhale_minn_admin_unique_slug();
						$now          = gmdate( 'Y-m-d H:i:s' );
						$data        += array(
							// Same defaults the link editor pre-checks for new links.
							'nofollow'   => (bool) \Clickwhale\Helpers\Helper::get_clickwhale_option( 'link_manager', 'nofollow' ),
							'sponsored'  => (bool) \Clickwhale\Helpers\Helper::get_clickwhale_option( 'link_manager', 'sponsored' ),
							'categories' => '',
							'author'     => get_current_user_id(),
							'created_at' => $now,
							'updated_at' => $now,
						);
						$links_t = \Clickwhale\Helpers\Helper::get_db_table_name( 'links' );
						// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ClickWhale's own prefix-built name.
						$wpdb->insert( $links_t, $data );
						$id = $wpdb->insert_id;
						if ( ! $id ) {
							return new WP_Error( 'cw_link_cannot_create', __( 'There was an error saving the ClickWhale link.', 'clickwhale' ), array( 'status' => 500 ) );
						}
						// Not firing `clickwhale_link_inserted` — see the header note.
						$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$links_t} WHERE id = %d", $id ) );
						// phpcs:enable
						$response = rest_ensure_response( clickwhale_minn_admin_link_item( $row ) );
						$response->set_status( 201 );
						return $response;
					},
				),
			)
		);

		register_rest_route(
			'clickwhale/v1',
			'/minn-admin/links/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'PUT',
					'permission_callback' => $perm,
					'callback'            => function ( WP_REST_Request $request ) {
						global $wpdb;
						$links_t = \Clickwhale\Helpers\Helper::get_db_table_name( 'links' );
						$id      = (int) $request['id'];
						// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ClickWhale's own prefix-built name.
						$row     = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$links_t} WHERE id = %d", $id ) );
						if ( ! $row ) {
							return new WP_Error( 'not_found', __( 'Link not found.', 'clickwhale' ), array( 'status' => 404 ) );
						}
						$data = clickwhale_minn_admin_link_payload( $request );
						if ( is_wp_error( $data ) ) {
							return $data;
						}
						$data['updated_at'] = gmdate( 'Y-m-d H:i:s' );
						$wpdb->update( $links_t, $data, array( 'id' => $id ) );
						// Not firing `clickwhale_link_updated` — see the header note.
						$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$links_t} WHERE id = %d", $id ) );
						// phpcs:enable
						return rest_ensure_response( clickwhale_minn_admin_link_item( $row ) );
					},
				),
				array(
					'methods'             => 'DELETE',
					'permission_callback' => $perm,
					'callback'            => function ( WP_REST_Request $request ) {
						global $wpdb;
						$id = (int) $request['id'];
						// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->delete( \Clickwhale\Helpers\Helper::get_db_table_name( 'links' ), array( 'id' => $id ) );
						$wpdb->delete( \Clickwhale\Helpers\Helper::get_db_table_name( 'meta' ), array( 'link_id' => $id ) );
						// phpcs:enable
						do_action( 'clickwhale_link_deleted', array( $id ) );
						return rest_ensure_response( array( 'deleted' => $id ) );
					},
				),
			)
		);

		register_rest_route(
			'clickwhale/v1',
			'/minn-admin/smart-displays',
			array(
				'methods'             => 'GET',
				'permission_callback' => $perm,
				'callback'            => function ( WP_REST_Request $request ) {
					global $wpdb;
					$table    = \Clickwhale\Helpers\Helper::get_db_table_name( 'smart_displays' );
					$search   = trim( (string) $request['search'] );
					$page     = max( 1, (int) ( $request['page'] ? $request['page'] : 1 ) );
					$per_page = 25;
					$where    = '1=1';
					$args     = array();
					if ( '' !== $search ) {
						$like   = '%' . $wpdb->esc_like( $search ) . '%';
						$where .= ' AND (name LIKE %s OR title LIKE %s)';
						$args   = array( $like, $like );
					}
					// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- table name is ClickWhale's own prefix-built name, $where is prepared above.
					$total = (int) $wpdb->get_var( $args ? $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE {$where}", $args ) : "SELECT COUNT(*) FROM {$table} WHERE {$where}" );
					$sql   = "SELECT * FROM {$table} WHERE {$where} ORDER BY id DESC LIMIT %d OFFSET %d";
					$rows  = $wpdb->get_results( $wpdb->prepare( $sql, array_merge( $args, array( $per_page, ( $page - 1 ) * $per_page ) ) ), ARRAY_A );
					// phpcs:enable
					return rest_ensure_response(
						array(
							'items' => array_map( 'clickwhale_minn_admin_smart_display_item', (array) $rows ),
							'total' => $total,
						)
					);
				},
			)
		);

		register_rest_route(
			'clickwhale/v1',
			'/minn-admin/smart-displays/(?P<id>\d+)',
			array(
				array(
					'methods'             => 'GET',
					'permission_callback' => $perm,
					'callback'            => function ( WP_REST_Request $request ) {
						$row = \Clickwhale\Helpers\Smart_Displays_Helper::get_by_id( (int) $request['id'] );
						if ( empty( $row ) ) {
							return new WP_Error( 'not_found', __( 'Smart box not found.', 'clickwhale' ), array( 'status' => 404 ) );
						}
						return rest_ensure_response( clickwhale_minn_admin_smart_display_item( $row ) );
					},
				),
				array(
					'methods'             => 'PUT',
					'permission_callback' => $perm,
					'callback'            => function ( WP_REST_Request $request ) {
						global $wpdb;
						$id  = (int) $request['id'];
						$row = \Clickwhale\Helpers\Smart_Displays_Helper::get_by_id( $id );
						if ( empty( $row ) ) {
							return new WP_Error( 'not_found', __( 'Smart box not found.', 'clickwhale' ), array( 'status' => 404 ) );
						}
						// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->update(
							\Clickwhale\Helpers\Helper::get_db_table_name( 'smart_displays' ),
							array(
								'name'  => sanitize_text_field( (string) $request['name'] ),
								'title' => sanitize_text_field( (string) $request['title'] ),
							),
							array( 'id' => $id )
						);
						return rest_ensure_response( clickwhale_minn_admin_smart_display_item( \Clickwhale\Helpers\Smart_Displays_Helper::get_by_id( $id ) ) );
					},
				),
				array(
					'methods'             => 'DELETE',
					'permission_callback' => $perm,
					'callback'            => function ( WP_REST_Request $request ) {
						global $wpdb;
						$id = (int) $request['id'];
						// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$wpdb->delete( \Clickwhale\Helpers\Helper::get_db_table_name( 'smart_displays' ), array( 'id' => $id ) );
						$wpdb->delete( \Clickwhale\Helpers\Helper::get_db_table_name( 'smart_display_data' ), array( 'smart_display_id' => $id ) );
						// phpcs:enable
						return rest_ensure_response( array( 'deleted' => $id ) );
					},
				),
			)
		);

		register_rest_route(
			'clickwhale/v1',
			'/minn-admin/smart-displays/(?P<id>\d+)/duplicate',
			array(
				'methods'             => 'POST',
				'permission_callback' => $perm,
				'callback'            => function ( WP_REST_Request $request ) {
					global $wpdb;
					$id         = (int) $request['id'];
					$table      = \Clickwhale\Helpers\Helper::get_db_table_name( 'smart_displays' );
					$data_table = \Clickwhale\Helpers\Helper::get_db_table_name( 'smart_display_data' );
					$original   = \Clickwhale\Helpers\Smart_Displays_Helper::get_by_id( $id );
					if ( empty( $original ) ) {
						return new WP_Error( 'not_found', __( 'Smart box not found.', 'clickwhale' ), array( 'status' => 404 ) );
					}
					unset( $original['id'], $original['created_at'] );
					if ( ! empty( $original['name'] ) ) {
						/* translators: %s: original smart box name */
						$original['name'] = sprintf( __( '%s (copy)', 'clickwhale' ), $original['name'] );
					} elseif ( ! empty( $original['title'] ) ) {
						/* translators: %s: original smart box title */
						$original['title'] = sprintf( __( '%s (copy)', 'clickwhale' ), $original['title'] );
					}
					// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name is ClickWhale's own prefix-built name.
					$wpdb->insert( $table, $original );
					$new_id = $wpdb->insert_id;
					if ( ! $new_id ) {
						return new WP_Error( 'duplicate_failed', __( 'Could not duplicate this smart box.', 'clickwhale' ), array( 'status' => 500 ) );
					}
					$data_rows = $wpdb->get_results(
						$wpdb->prepare( "SELECT integration, is_active, params, raw_data FROM {$data_table} WHERE smart_display_id = %d", $id ),
						ARRAY_A
					);
					foreach ( (array) $data_rows as $data_row ) {
						$wpdb->insert( $data_table, array_merge( array( 'smart_display_id' => $new_id ), $data_row ) );
					}
					// phpcs:enable
					$response = rest_ensure_response( clickwhale_minn_admin_smart_display_item( \Clickwhale\Helpers\Smart_Displays_Helper::get_by_id( $new_id ) ) );
					$response->set_status( 201 );
					return $response;
				},
			)
		);
	}
);
