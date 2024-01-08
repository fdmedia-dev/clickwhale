<?php
do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap clickwhale-pro-promo">
    <div class="clickwhale-pro-promo--hero">
        <div class="clickwhale-pro-promo--container">
            <h2>Swim past your competition with ClickWhale <em>PRO</em></h2>
            <p>Take your website to the next level with great additions to our plugin!</p>
            <a href="https://clickwhale.pro/pricing/?campaign=ClickWhale%20Free%20Plugin%3A%20Pro%20Upgrade&ref=5"
               class="button-get-pro"
               target="_blank"
               rel="noopener">Upgrade to ClickWhale (PRO) Now!</a>
            <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/whale.svg" alt="ClickWhale">
        </div>
    </div>
    <div class="clickwhale-pro-promo--content">
        <div class="clickwhale-pro-promo--container">
            <h2>Things that await you:</h2>
            <div class="clickwhale-pro-promo--why">
                <div class="clickwhale-pro-promo--why-grid">
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/pro/chart-bars.svg" alt="Detailed Statistics">
                        <h3>Detailed Statistics <span>Link Manager, Link Pages</span></h3>
                        <p>Detailed statistics which allow you to filter and analyze link clicks and link page
                            views.</p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/pro/filter-3.svg" alt="Conversion Tracking">
                        <h3>Conversion Tracking <span>Tracking Codes</span></h3>
                        <p>Easily place conversion tracking codes to track the performance of your WooCommerce or Easy
                            Digital Downloads store.</p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/pro/social-media-profiles.svg" alt="Social Profiles">
                        <h3>Social Profiles <span>Link Pages</span></h3>
                        <p>Add your social profiles to your existing link page in order to gain more followers on your
                            favorite social networks.</p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/pro/utm-tracking.svg" alt="UTM Campaign Tracking">
                        <h3>UTM Campaign Tracking <span>Link Manager</span></h3>
                        <p>Gain the ability to track UTM campaigns with better parameters for exceptional
                            campaign efficacy when you upgrade to ClickWhale PRO.</p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/pro/feed.svg" alt="Blog Posts Feed">
                        <h3>Blog Posts Feed <span>Link Pages</span></h3>
                        <p>Automatically show the latest blog posts directly on your link
                            pages for further reach.</p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/pro/customize-options.svg"
                             alt="More Customization Options">
                        <h3>More Customization Options <span>Link Pages</span></h3>
                        <p>Additional features from the plugin to customize link pages include options to add
                            branded backgrounds, images, and more.</p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/pro/no-limits.svg" alt="No Limitations">
                        <h3>No Limitations <span>Link Manager, Link Pages, Tracking Codes</span></h3>
                        <p>Limitations be gone! Curate and publish as many tracking codes, link pages and branded links
                            as needed when upgrading to ClickWhale PRO.</p>
                    </div>
                    <div class="clickwhale-pro-promo--why-item">
                        <img src="<?php echo CLICKWHALE_ADMIN_ASSETS_DIR ?>/images/pro/remove-credits.svg" alt="Remove Plugin Credits">
                        <h3>Remove Plugin Credits <span>Link Pages, Tracking Codes</span></h3>
                        <p>Keep the branding personal with the ability to remove ClickWhale credits like the logo from
                            footers and more.</p>
                    </div>
                </div>

                <div class="clickwhale-pro-promo--why-action">
                    <a href="https://clickwhale.pro/pricing/?campaign=ClickWhale%20Free%20Plugin%3A%20Pro%20Upgrade&ref=5"
                       class="button-get-pro"
                       target="_blank"
                       rel="noopener">Get ClickWhale (PRO)</a>
                </div>

            </div>
        </div>
    </div>
    <div class="clickwhale-pro-promo--subscribe" id="clickwhaleSubscribe">
        <div class="clickwhale-pro-promo--container">
            <h2>Get your exclusive upgrade discount now!</h2>
            <p><strong>Save 10%</strong> on your upgrade to ClickWhale (PRO) - Thank you for using our plugin already!
            </p>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
                <input type="hidden" name="action" value="clickwhale_pro_subscription_action">
                <input type="email" name="email" placeholder="Your email address" required>
                <button type="submit">Claim my discount</button>
            </form>
			<?php if ( ! empty( $_GET['success'] ) ) { ?>
                <p>Thanks! Please check your inbox for the discount code.</p>
			<?php } ?>
        </div>
    </div>
</div>
