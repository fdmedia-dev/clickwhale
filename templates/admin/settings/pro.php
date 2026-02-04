<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

do_action( 'clickwhale_admin_banner' );
?>
<div class="wrap clickwhale-pro-promo">
    <div class="clickwhale-pro-promo--hero">
        <div class="clickwhale-pro-promo--container">
            <h2><?php esc_html_e( 'Swim past your competition with ClickWhale', 'clickwhale' ); ?> <em><?php esc_html_e( 'PRO', 'clickwhale' ); ?></em></h2>
            <p><?php esc_html_e( 'Take your website to the next level with great additions to our plugin!', 'clickwhale' ); ?></p>
            <a href="https://clickwhale.pro/upgrade/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=upgrade_to_pro_page"
               class="button-get-pro"
               target="_blank"
               rel="noopener"
               alt="<?php esc_attr_e( 'Upgrade to ClickWhale (PRO) Now!', 'clickwhale' ); ?>"
            ><?php esc_html_e( 'Upgrade to ClickWhale (PRO) Now!', 'clickwhale' ); ?></a>
            <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/whale.svg" alt="ClickWhale" />
        </div>
    </div>
    <div class="clickwhale-pro-promo--content">
        <div class="clickwhale-pro-promo--container">
            <h2><?php esc_html_e( 'Things that await you:', 'clickwhale' ); ?></h2>
            <div class="clickwhale-pro-promo--why">
                <div class="clickwhale-pro-promo--why-grid">
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/pro/chart-bars.svg" alt="<?php esc_attr_e( 'Detailed Statistics', 'clickwhale' ); ?>" />
                        <h3><?php esc_html_e( 'Detailed Statistics', 'clickwhale' ); ?> <span><?php esc_html_e( 'Link Manager', 'clickwhale' ); ?>, <?php esc_html_e( 'Link Pages', 'clickwhale' ); ?></span></h3>
                        <p><?php esc_html_e( 'Detailed statistics which allow you to filter and analyze link clicks and link page views.', 'clickwhale' ); ?></p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/pro/keyword-auto-linker.png" alt="<?php esc_attr_e( 'Keyword Auto Linker', 'clickwhale' ); ?>" />
                        <h3><?php esc_html_e( 'Keyword Auto Linker', 'clickwhale' ); ?> <span><?php esc_html_e( 'Link Manager', 'clickwhale' ); ?></span></h3>
                        <p><?php esc_html_e( 'Define keywords that will be automatically replaced with links throughout your posts, pages and custom post types.', 'clickwhale' ); ?></p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/pro/filter-3.svg" alt="<?php esc_attr_e( 'Conversion Tracking', 'clickwhale' ); ?>" />
                        <h3><?php esc_html_e( 'Conversion Tracking', 'clickwhale' ); ?> <span><?php esc_html_e( 'Tracking Codes', 'clickwhale' ); ?></span></h3>
                        <p><?php esc_html_e( 'Easily place conversion tracking codes to track the performance of your WooCommerce or Easy Digital Downloads store.', 'clickwhale' ); ?></p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/pro/utm-tracking.svg" alt="<?php esc_attr_e( 'UTM Campaign Tracking', 'clickwhale' ); ?>" />
                        <h3><?php esc_html_e( 'UTM Campaign Tracking', 'clickwhale' ); ?> <span><?php esc_html_e( 'Link Manager', 'clickwhale' ); ?></span></h3>
                        <p><?php esc_html_e( 'Gain the ability to track UTM campaigns with better parameters for exceptional campaign efficacy when you upgrade to ClickWhale PRO.', 'clickwhale' ); ?></p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/pro/social-media-profiles.svg" alt="<?php esc_attr_e( 'Social Profiles', 'clickwhale' ); ?>" />
                        <h3><?php esc_html_e( 'Social Profiles', 'clickwhale' ); ?> <span><?php esc_html_e( 'Link Pages', 'clickwhale' ); ?></span></h3>
                        <p><?php esc_html_e( 'Add your social profiles to your existing link page in order to gain more followers on your favorite social networks.', 'clickwhale' ); ?></p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/pro/feed.svg" alt="<?php esc_attr_e( 'Blog Posts Feed', 'clickwhale' ); ?>" />
                        <h3><?php esc_html_e( 'Blog Posts Feed', 'clickwhale' ); ?> <span><?php esc_html_e( 'Link Pages', 'clickwhale' ); ?></span></h3>
                        <p><?php esc_html_e( 'Automatically show the latest blog posts directly on your link pages for further reach.', 'clickwhale' ); ?></p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/pro/forms-block.svg" alt="<?php esc_attr_e( 'Forms Block', 'clickwhale' ); ?>" />
                        <h3><?php esc_html_e( 'Forms Block', 'clickwhale' ); ?> <span><?php esc_html_e( 'Link Pages', 'clickwhale' ); ?></span></h3>
                        <p><?php esc_html_e( 'Easily add forms from popular WordPress form plugins to your link page and collect leads or feedback from your social media followers.', 'clickwhale' ); ?></p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/pro/customize-options.svg" alt="<?php esc_attr_e( 'More Customization Options', 'clickwhale' ); ?>" />
                        <h3><?php esc_html_e( 'More Customization Options', 'clickwhale' ); ?> <span><?php esc_html_e( 'Link Pages', 'clickwhale' ); ?></span></h3>
                        <p><?php esc_html_e( 'Additional features from the plugin to customize link pages include options to add branded backgrounds, images, and more.', 'clickwhale' ); ?></p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/pro/no-limits.svg"
                             alt="<?php esc_attr_e( 'No Limitations', 'clickwhale' ); ?>" />
                        <h3><?php esc_html_e( 'No Limitations', 'clickwhale' ); ?> <span><?php esc_html_e( 'Link Manager', 'clickwhale' ); ?>, <?php esc_html_e( 'Link Pages', 'clickwhale' ); ?>, <?php esc_html_e( 'Tracking Codes', 'clickwhale' ); ?></span></h3>
                        <p><?php esc_html_e( 'Limitations be gone! Curate and publish as many tracking codes, link pages and branded links as needed when upgrading to ClickWhale PRO.', 'clickwhale' ); ?></p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo esc_url( CLICKWHALE_ADMIN_ASSETS_DIR ); ?>/images/pro/remove-credits.svg"
                             alt="<?php esc_attr_e( 'Remove Plugin Credits', 'clickwhale' ); ?>" />
                        <h3><?php esc_html_e( 'Remove Plugin Credits', 'clickwhale' ); ?> <span><?php esc_html_e( 'Link Pages', 'clickwhale' ); ?>, <?php esc_html_e( 'Tracking Codes', 'clickwhale' ); ?></span></h3>
                        <p><?php esc_html_e( 'Keep the branding personal with the ability to remove ClickWhale credits like the logo from footers and more.', 'clickwhale' ); ?></p>
                    </div>
                </div>
                <div class="clickwhale-pro-promo--why-action">
                    <a href="https://clickwhale.pro/upgrade/?utm_source=users&utm_medium=button&utm_campaign=plugin_admin&utm_content=upgrade_to_pro_page"
                       class="button-get-pro"
                       target="_blank"
                       rel="noopener"
                       alt="<?php esc_attr_e( 'Upgrade to ClickWhale (PRO)', 'clickwhale' ); ?>"
                    ><?php esc_html_e( 'Upgrade to ClickWhale (PRO)', 'clickwhale' ); ?></a>
                </div>
            </div>
        </div>
    </div>
</div>