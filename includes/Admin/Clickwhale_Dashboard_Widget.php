<?php

namespace Clickwhale\Admin;

use WP_REST_Request;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "ClickWhale — Quick Add" Dashboard widget (fixes #447).
 *
 * Pretty Links ships a "Quick Add" widget on wp-admin's Home dashboard: a
 * Target URL field, an optional custom slug, and a one-click create button
 * — no trip to the full link editor needed. This mirrors that shape for
 * ClickWhale's own links table.
 *
 * The widget's JS submits to `POST clickwhale/v1/quick-add`, a thin wrapper
 * (register_route()/quick_add() below) around ClickWhale's own existing
 * `POST clickwhale/v1/links` (Clickwhale_Rest_Controller::create_item()),
 * dispatched in-process via rest_do_request() rather than a second HTTP
 * round trip. That reuses create_item()'s real validation untouched (slug
 * collision checks across CW links, CW link pages, WP posts, WP taxonomies,
 * and a live wp_remote_get() against the home URL) instead of duplicating
 * any of it — but create_item() unconditionally hardcodes
 * `created_by_api => true` on every row it inserts, and
 * Clickwhale_Links_List_Table::get_current_data() defaults its own "Created
 * by" filter to admin-only, EXCLUDING created_by_api rows from the default
 * Links list entirely. A link from this widget is a first-party wp-admin
 * action, not external API traffic, so quick_add() resets that flag back to
 * 0 immediately after create_item() succeeds — otherwise a freshly created
 * link would look like it silently vanished the moment the widget redirects
 * to the Links page.
 *
 * A blank custom-slug field is filled client-side with a random 6-letter
 * slug (Links_Helper::generate_random_slug()'s exact alphabet/length)
 * before submit; on the rare collision (409) the widget retries once with a
 * fresh random slug.
 *
 * `title` is required by create_item()'s schema but Pretty Links' own Quick
 * Add has no title field either, so this widget defaults it to the target
 * URL itself — a normal starting point, renamable later from ClickWhale's
 * own edit screen.
 *
 * The REST nonce is inlined straight into the render callback's script
 * block (`wp_create_nonce( 'wp_rest' )`), matching how every other
 * ClickWhale admin screen inlines its own ajax nonces (Clickwhale_Link_Edit,
 * Clickwhale_Ajax) rather than via wp_localize_script — this plugin doesn't
 * localize a REST nonce anywhere else.
 */
class Clickwhale_Dashboard_Widget {

	/**
	 * Register the "ClickWhale — Quick Add" widget, gated the same way as
	 * every other ClickWhale admin surface.
	 *
	 * @return void
	 */
	public function add_widget() {
		if ( ! clickwhale()->user->is_current_user_role_access_granted() ) {
			return;
		}

		wp_add_dashboard_widget(
			'clickwhale_quick_add',
			__( 'ClickWhale Quick Add', 'clickwhale' ),
			array( $this, 'render_widget' )
		);
	}

	/**
	 * Register the thin `quick-add` wrapper route the widget's JS calls.
	 *
	 * @return void
	 */
	public function register_route() {
		register_rest_route(
			'clickwhale/v1',
			'/quick-add',
			array(
				'methods'             => 'POST',
				'permission_callback' => function () {
					return clickwhale()->user->is_current_user_role_access_granted();
				},
				'callback'            => array( $this, 'quick_add' ),
			)
		);
	}

	/**
	 * Create a link via ClickWhale's own create_item(), then reset
	 * created_by_api back to 0 — see the class docblock for why.
	 *
	 * @param WP_REST_Request $request Request carrying title/target_url/slug.
	 * @return \WP_REST_Response
	 */
	public function quick_add( WP_REST_Request $request ) {
		// The widget uses the target URL as the title. Titles are limited to 255 characters
		// but target URLs to 1000, so shorten long titles instead of rejecting the link.
		$title = (string) $request['title'];
		if ( mb_strlen( $title ) > 255 ) {
			$title = mb_substr( $title, 0, 254 ) . '…';
		}

		$internal = new WP_REST_Request( 'POST', '/clickwhale/v1/links' );
		$internal->set_body_params(
			array(
				'title'      => $title,
				'target_url' => $request['target_url'],
				'slug'       => $request['slug'],
				// Same defaults the link editor pre-checks for new links.
				'nofollow'   => (bool) \Clickwhale\Helpers\Helper::get_clickwhale_option( 'link_manager', 'nofollow' ),
				'sponsored'  => (bool) \Clickwhale\Helpers\Helper::get_clickwhale_option( 'link_manager', 'sponsored' ),
			)
		);

		$response = rest_do_request( $internal );

		if ( $response->get_status() >= 300 ) {
			return $response;
		}

		$data = $response->get_data();

		if ( ! empty( $data['id'] ) ) {
			global $wpdb;
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update(
				\Clickwhale\Helpers\Helper::get_db_table_name( 'links' ),
				array( 'created_by_api' => 0 ),
				array( 'id' => (int) $data['id'] )
			);
			$data['created_by_api'] = false;
			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * Render the widget: form + inline style/script.
	 *
	 * @return void
	 */
	public function render_widget() {
		$rest_url     = esc_url_raw( rest_url( 'clickwhale/v1/quick-add' ) );
		$rest_nonce   = wp_create_nonce( 'wp_rest' );
		$slug_prefix  = trailingslashit( home_url() );
		$links_url    = admin_url( 'admin.php?page=' . CLICKWHALE_SLUG );
		$default_slug = \Clickwhale\Helpers\Links_Helper::generate_random_slug();
		$icons        = trailingslashit( CLICKWHALE_ADMIN_ASSETS_DIR ) . 'images/feather-sprite.svg';
		?>
		<div class="clickwhale-quick-add">
			<p class="clickwhale-quick-add__tagline">
				<svg class="feather" width="18" height="18" aria-hidden="true">
					<use href="<?php echo esc_url( $icons ); ?>#link"></use>
				</svg>
				<?php esc_html_e( 'Turn a long link into a trackable ClickWhale link.', 'clickwhale' ); ?>
			</p>

			<form class="clickwhale-quick-add__form">
				<p class="clickwhale-quick-add__field">
					<label for="clickwhale-quick-add-url"><?php esc_html_e( 'Target URL', 'clickwhale' ); ?></label>
					<input type="url" id="clickwhale-quick-add-url" placeholder="https://yourstore.com/products/summer-sale?utm_source=newsletter" required="required" />
				</p>
				<p class="clickwhale-quick-add__field">
					<label for="clickwhale-quick-add-slug">
						<?php esc_html_e( 'Custom slug', 'clickwhale' ); ?>
						<span class="clickwhale-quick-add__optional"><?php esc_html_e( 'optional', 'clickwhale' ); ?></span>
					</label>
					<span class="clickwhale-quick-add__slug-row">
						<span class="clickwhale-quick-add__prefix"><?php echo esc_html( $slug_prefix ); ?></span>
						<input type="text" id="clickwhale-quick-add-slug" value="<?php echo esc_attr( $default_slug ); ?>" />
					</span>
				</p>
				<p class="clickwhale-quick-add__notice" data-clickwhale-quick-add-notice hidden="hidden"></p>
				<button type="submit" class="button button-primary clickwhale-quick-add__submit">
					<span class="dashicons dashicons-plus-alt2"></span>
					<?php esc_html_e( 'Create Link', 'clickwhale' ); ?>
				</button>
			</form>
		</div>

		<style>
			.clickwhale-quick-add__tagline {
				display: flex;
				align-items: center;
				gap: .5rem;
				color: #646970;
				margin: 0 0 1rem;
				padding-bottom: 1rem;
				border-bottom: 1px solid #e7e7e7;
			}
			.clickwhale-quick-add__tagline .feather {
				stroke: var( --clickwhale-brand-blue, #397eff );
				fill: none;
				stroke-width: 2;
				flex-shrink: 0;
			}
			.clickwhale-quick-add__field {
				margin: 0 0 1rem;
			}
			.clickwhale-quick-add__field label {
				display: block;
				font-weight: 600;
				margin-bottom: .35rem;
			}
			.clickwhale-quick-add__optional {
				font-weight: 400;
				color: #646970;
			}
			#clickwhale-quick-add-url {
				width: 100%;
				border-radius: 4px;
			}
			.clickwhale-quick-add__slug-row {
				display: flex;
			}
			.clickwhale-quick-add__prefix {
				display: flex;
				align-items: center;
				padding: 0 8px;
				background: #f6f7f7;
				border: 1px solid #8c8f94;
				border-right: none;
				border-radius: 4px 0 0 4px;
				color: #646970;
				font-family: Consolas, Monaco, monospace;
				font-size: 13px;
				white-space: nowrap;
			}
			.clickwhale-quick-add__slug-row input {
				flex: 1;
				min-width: 0;
				border-radius: 0 4px 4px 0;
				font-family: Consolas, Monaco, monospace;
			}
			.clickwhale-quick-add__notice {
				margin: 0 0 1rem;
				color: var( --clickwhale-brand-red, #dc3545 );
			}
			.clickwhale-quick-add__submit {
				display: inline-flex;
				align-items: center;
				gap: .35rem;
				background: var( --clickwhale-brand-blue, #397eff );
				border-color: var( --clickwhale-brand-blue, #397eff );
			}
		</style>

		<script>
		( function () {
			var form = document.querySelector( '#clickwhale_quick_add .clickwhale-quick-add__form' );
			if ( ! form ) {
				return;
			}

			var urlField    = form.querySelector( '#clickwhale-quick-add-url' );
			var slugField   = form.querySelector( '#clickwhale-quick-add-slug' );
			var notice      = form.querySelector( '[data-clickwhale-quick-add-notice]' );
			var submitBtn   = form.querySelector( '.clickwhale-quick-add__submit' );

			function randomSlug() {
				var chars = 'abcdefghijklmnopqrstuvwxyz';
				var out   = '';
				for ( var i = 0; i < 6; i++ ) {
					out += chars.charAt( Math.floor( Math.random() * chars.length ) );
				}
				return out;
			}

			function showNotice( message ) {
				notice.textContent = message;
				notice.hidden = false;
			}

			function createLink( slug, allowRetry ) {
				return fetch( <?php echo wp_json_encode( $rest_url ); ?>, {
					method: 'POST',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': <?php echo wp_json_encode( $rest_nonce ); ?>
					},
					credentials: 'same-origin',
					body: JSON.stringify( {
						title: urlField.value,
						target_url: urlField.value,
						slug: slug
					} )
				} ).then( function ( response ) {
					if ( 409 === response.status && allowRetry ) {
						return createLink( randomSlug(), false );
					}
					return response.json().then( function ( data ) {
						return { ok: response.ok, data: data };
					} );
				} );
			}

			form.addEventListener( 'submit', function ( event ) {
				event.preventDefault();
				notice.hidden = true;

				if ( ! urlField.value ) {
					return;
				}

				submitBtn.disabled = true;
				var slug = slugField.value.trim() || randomSlug();

				createLink( slug, ! slugField.value.trim() ).then( function ( outcome ) {
					if ( ! outcome.ok ) {
						submitBtn.disabled = false;
						showNotice( ( outcome.data && outcome.data.message ) || <?php echo wp_json_encode( __( 'Could not create this link.', 'clickwhale' ) ); ?> );
						return;
					}

					window.location.href = <?php echo wp_json_encode( $links_url ); ?>;
				} ).catch( function () {
					submitBtn.disabled = false;
					showNotice( <?php echo wp_json_encode( __( 'Could not reach the server. Please try again.', 'clickwhale' ) ); ?> );
				} );
			} );
		} )();
		</script>
		<?php
	}
}
