<?php
do_action( 'clickwhale_admin_banner' );
?>

<div class="wrap clickwhale-pro-promo">
	<div class="clickwhale-pro-promo--hero">
		<div class="clickwhale-pro-promo--container">
			<h1>Swim passed the competition with ClickWhale <em>PRO</em></h1>
			<p>Take your branded links and link page analytics, customization, and integration to PROfessional
				levels!</p>
			<a href="" rel="noopener">Go PRO!</a>
			<img src="<?php echo ADMIN_IMAGES_DIR ?>/whale.svg" alt="ClickWhale">
		</div>
	</div>
	<div class="clickwhale-pro-promo--content">
		<div class="clickwhale-pro-promo--container">
			<h2>Things that await you:</h2>
			<div class="clickwhale-pro-promo--why">
				<div class="clickwhale-pro-promo--why-grid">
					<div class="clickwhale-pro-promo--why-item">
						<img src="<?php echo ADMIN_IMAGES_DIR ?>/pro/feed.svg" alt="Blog Posts Feed">
						<h3>Blog Posts Feed <span>Link Pages</span></h3>
						<p>The addition of display variants when outputting new blog posts directly into link
							pages for further reach.</p>
					</div>
					<div class="clickwhale-pro-promo--why-item">
						<img src="<?php echo ADMIN_IMAGES_DIR ?>/pro/filter-3.svg" alt="Conversion Tracking">
						<h3>Conversion Tracking <span>Tracking Codes</span></h3>
						<p>Get a more in-depth analysis of web traffic by placing conversion tracking codes, including
							supported eCommerce plugins, with ease.</p>
					</div>
					<div class="clickwhale-pro-promo--why-item">
						<img src="<?php echo ADMIN_IMAGES_DIR ?>/pro/chart-bars.svg" alt="Detailed Statistics">
						<h3>Detailed Statistics <span>Link Manager, Link Pages</span></h3>
						<p>ClickWhale brings even more analytic tracking for customized, branded links with the
							addition of comparison graphs and precise analysis options.</p>
					</div>
					<div class="clickwhale-pro-promo--why-item">
						<img src="<?php echo ADMIN_IMAGES_DIR ?>/pro/customize-options.svg"
						     alt="More Customization Options">
						<h3>More Customization Options <span>Link Pages</span></h3>
						<p>Additional features from the plugin to customize link pages include options to add
							branded background images, and more.</p>
					</div>
					<div class="clickwhale-pro-promo--why-item">
						<img src="<?php echo ADMIN_IMAGES_DIR ?>/pro/no-limits.svg" alt="No Limits">
						<h3>No Limits <span>Link Manager, Link Pages, Tracking codes</span></h3>
						<p>Limitations be gone! Curate and publish as many tracking codes, link pages and branded links
							as needed when upgrading to ClickWhale PRO.</p>
					</div>
					<div class="clickwhale-pro-promo--why-item">
						<img src="<?php echo ADMIN_IMAGES_DIR ?>/pro/remove-credits.svg" alt="Remove Plugin Credits">
						<h3>Remove Plugin Credits <span>Link Pages, Tracking Codes</span></h3>
						<p>Keep the branding personal with the ability to disable ClickWhale credits like the logo from
							footers and more.</p>
					</div>
					<div class="clickwhale-pro-promo--why-item">
						<img src="<?php echo ADMIN_IMAGES_DIR ?>/pro/social-media-profiles.svg" alt="Social Profiles">
						<h3>Social Profiles <span>Link Pages</span></h3>
						<p>Further the customization with the ability to add social media icons to curated link pages
							that are designed with ClickWhale PRO.</p>
					</div>
					<div class="clickwhale-pro-promo--why-item">
						<img src="<?php echo ADMIN_IMAGES_DIR ?>/pro/utm-tracking.svg" alt="UTM Campaign Tracking">
						<h3>UTM Campaign Tracking <span>Link Manager</span></h3>
						<p>Gain the ability to track UTM campaigns with better parameters for exceptional
							campaign efficacy when you upgrade to ClickWhale PRO.</p>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="clickwhale-pro-promo--subscribe" id="clickwhaleSubscribe">
		<div class="clickwhale-pro-promo--container">
			<h2>Sign up for the next step!</h2>
			<p>You will receive further information by email</p>
			<form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post">
				<input type="hidden" name="action" value="clickwhale_pro_subscription_action">
				<input type="email" name="email" placeholder="Your email address">
				<button type="submit">Sign Up</button>
			</form>
			<?php if ( ! empty( $_GET['success'] ) ) { ?>
				<p>Thanks for subscribing!</p>
			<?php } ?>
		</div>
	</div>
</div>