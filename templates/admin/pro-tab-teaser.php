<?php

use Clickwhale\Helpers\Helper;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Reusable "locked tab" teaser card, shared by every tabbed admin screen
 * that has Pro-only tabs (Settings, Link edit, Link Page edit).
 *
 * @uses array $clickwhale_active_tab {
 *     @type string $name   Fallback heading if teaser.title is not set.
 *     @type array  $teaser {
 *         @type string $title       Optional. Defaults to $clickwhale_active_tab['name'].
 *         @type string $description
 *         @type array  $features    List of feature bullet strings.
 *         @type string $image       Filename inside assets/admin/images/pro/.
 *         @type string $utm_content Passed to Helper::get_pro_link().
 *     }
 * }
 */

$clickwhale_teaser = $clickwhale_active_tab['teaser'] ?? array();

if ( ! $clickwhale_teaser ) {
    return;
}
?>
<div class="clickwhale-teaser-tab">
    <div class="clickwhale-teaser-tab--content">
        <h2>
            <?php echo esc_html( $clickwhale_teaser['title'] ?? $clickwhale_active_tab['name'] ); ?>
            <em><?php esc_html_e( 'PRO', 'clickwhale' ); ?></em>
        </h2>
        <?php if ( ! empty( $clickwhale_teaser['description'] ) ) : ?>
            <p><?php echo esc_html( $clickwhale_teaser['description'] ); ?></p>
        <?php endif; ?>
        <?php if ( ! empty( $clickwhale_teaser['features'] ) ) : ?>
            <ul class="clickwhale-teaser-tab--features">
                <?php foreach ( $clickwhale_teaser['features'] as $clickwhale_feature ) : ?>
                    <li>
                        <svg class="feather"><use href="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR . '/images/feather-sprite.svg#check' ); ?>"></use></svg>
                        <?php echo esc_html( $clickwhale_feature ); ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <a href="<?php echo esc_url( Helper::get_pro_link( $clickwhale_teaser['utm_content'] ?? 'settings_tab_teaser' ) ); ?>"
           class="button-get-pro"
           target="_blank"
           rel="noopener"
        ><?php esc_html_e( 'Upgrade to ClickWhale PRO', 'clickwhale' ); ?></a>
    </div>
    <?php if ( ! empty( $clickwhale_teaser['image'] ) ) : ?>
        <div class="clickwhale-teaser-tab--preview">
            <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR . '/images/pro/' . $clickwhale_teaser['image'] ); ?>" alt="">
        </div>
    <?php endif; ?>
</div>
