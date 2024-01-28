<?php

namespace clickwhale_pro\includes\admin\linkpages;

class Clickwhale_Pro_Linkpage_Preview {
	public function __construct() {
		add_action( 'admin_print_footer_scripts', [ $this, 'admin_scripts' ] );

		$this->render_preview();
	}

	public function render_preview() {
		ob_start();
		?>
        <div class="linkpage-preview--wrap">
            <div class="linkpage-preview">
                <div class="linkpage-preview--top">
                    <div class="linkpage-preview--logo"></div>
                    <div class="linkpage-preview--title"></div>
                    <div class="linkpage-preview--description"></div>
                    <div class="linkpage-preview--links">
                        <div class="linkpage-preview-row">
                            <a class="linkpage-public-row-link" href="javascript:void(0)">
                                <div class="linkpage-row--image"></div>
                                <div class="linkpage-row--title--wrap">
                                    <div class="linkpage-row--title">First Demo Link</div>
                                </div>
                                <div class="linkpage-row--end"></div>
                            </a>
                        </div>
                        <div class="linkpage-preview-row">
                            <a class="linkpage-public-row-link" href="javascript:void(0)">
                                <div class="linkpage-row--image"></div>
                                <div class="linkpage-row--title--wrap">
                                    <div class="linkpage-row--title">Second Demo Link</div>
                                </div>
                                <div class="linkpage-row--end"></div>
                            </a>
                        </div>
                        <div class="linkpage-preview-row">
                            <a class="linkpage-public-row-link" href="javascript:void(0)">
                                <div class="linkpage-row--image"></div>
                                <div class="linkpage-row--title--wrap">
                                    <div class="linkpage-row--title">Third Demo Link</div>
                                </div>
                                <div class="linkpage-row--end"></div>
                            </a>
                        </div>
                        <div class="linkpage-preview-row">
                            <a class="linkpage-public-row-link" href="javascript:void(0)">
                                <div class="linkpage-row--image"></div>
                                <div class="linkpage-row--title--wrap">
                                    <div class="linkpage-row--title">Fourth Demo Link</div>
                                </div>
                                <div class="linkpage-row--end"></div>
                            </a>
                        </div>
                        <div class="linkpage-preview-row">
                            <a class="linkpage-public-row-link" href="javascript:void(0)">
                                <div class="linkpage-row--image"></div>
                                <div class="linkpage-row--title--wrap">
                                    <div class="linkpage-row--title">Other Demo Link</div>
                                </div>
                                <div class="linkpage-row--end"></div>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="linkpage-preview--bottom"></div>
            </div>
        </div>
		<?php
		$preview = ob_get_contents();
		ob_end_clean();

		echo $preview;
	}

	public function admin_scripts() {
		?>
        <script type='text/javascript'>
            jQuery(document).ready(function () {

                // set current values
                const
                    preview = jQuery('.linkpage-preview'),
                    title = jQuery('#title'),
                    description = jQuery('#description'),
                    previewLogoWrap = jQuery('.linkpage-preview--logo'),
                    previewTitle = jQuery('.linkpage-preview--title'),
                    previewDescription = jQuery('.linkpage-preview--description'),
                    logoDefaultImgSrc = '<?php echo CLICKWHALE_PUBLIC_ASSETS_DIR ?>/images/whale.svg',
                    logoStyle = jQuery('[name="styles[logo_style]"]'),
                    textColor = jQuery('[name="styles[text_color]"]'),
                    bgStyle = jQuery('[name="styles[bg_style]"]'),
                    bgColor = jQuery('[name="styles[bg_color]"]'),
                    gradient = jQuery('[name="styles[bg_gradient]"]'),
                    pattern = jQuery('[name="styles[bg_pattern]"]'),
                    patternsFolder = '<?php echo CLICKWHALE_PRO_PUBLIC_ASSETS_DIR . '/patterns/' ?>',
                    linkBgColor = jQuery('[name="styles[link_bg_color]"]'),
                    linkBgColorHover = jQuery('[name="styles[link_bg_color_hover]"]'),
                    linkColor = jQuery('[name="styles[link_color]"]'),
                    linkColorHover = jQuery('[name="styles[link_color_hover]"]');


                let
                    logoStyleValue = jQuery('[name="styles[logo_style]"]:checked').val(),
                    bgStyleValue = jQuery('[name="styles[bg_style]"]:checked').val(),
                    gradientValue = jQuery('[name="styles[bg_gradient]"]:checked').val(),
                    patternValue = jQuery('[name="styles[bg_pattern]"]:checked').val();

                previewLogoWrap.prepend(`<img class="${logoStyleValue}" src="${get_logo_src()}" alt=""/>`);
                previewTitle.css('color', textColor.val()).text(title.val());
                previewDescription.css('color', textColor.val()).text(description.val());
                preview.attr('style', setBgStyle(bgStyleValue));

                jQuery('.linkpage-public-row-link')
                    .css({
                        'background-color': linkBgColor.val(),
                        'color': linkColor.val(),
                    })
                    .on('mouseenter', function () {
                        jQuery(this).css({
                            'background-color': linkBgColorHover.val(),
                            'color': linkColorHover.val(),
                        })
                    })
                    .on('mouseleave', function () {
                        jQuery(this).css({
                            'background-color': linkBgColor.val(),
                            'color': linkColor.val(),
                        })
                    });

                // MAGIC
                // title
                title.on('change', function () {
                    previewTitle.text(title.val())
                });

                // description
                description.on('change', function () {
                    previewDescription.text(description.val())
                });

                // logo
                jQuery('input[name="logo"]').bind("change", function () {
                    previewLogoWrap.find('img').attr('src', get_logo_src())
                });

                // on remove logo
                jQuery('.logo-field .linkpage-image-remove').on('click', function () {
                    previewLogoWrap.find('img').attr('src', logoDefaultImgSrc)
                });

                // logo style: default/rounded/circle
                logoStyle.on('change', function () {
                    previewLogoWrap.find('img').attr('class', this.value);
                });

                // background style: solid/gradient/pattern/image
                bgStyle.on('change', function () {
                    preview.attr('style', setBgStyle(this.value));
                });

                // page text color
                textColor.wpColorPicker({
                    change: function (event, ui) {
                        previewTitle.css('color', ui.color.toString());
                        previewDescription.css('color', ui.color.toString());
                    }
                });

                // site background color / solid color
                bgColor.wpColorPicker({
                    change: function (event, ui) {
                        preview.attr('style', `background: ${ui.color.toString()}`);
                    }
                });

                // gradients radio
                gradient.on('change', function () {
                    gradientValue
                        = this.value === 'custom'
                        ? jQuery('.gradient-style-custom-preview').attr('style')
                        : jQuery(`#gradientStyle${this.value}`).next().attr('style');

                    preview.attr('style', gradientValue);
                });

                // custom gradient
                // listener for attr "style" changes
                const observer = new MutationObserver(function (mutations) {
                    mutations.forEach(function (mutationRecord) {
                        preview.attr('style', jQuery('.gradient-style-custom-preview').attr('style'));
                    });
                });

                observer.observe(document.querySelector('.gradient-style-custom-preview'), {
                    attributes: true,
                    attributeFilter: ['style']
                });

                // patterns radio
                pattern.on('change', function () {
                    preview.attr('style', `background: url(${patternsFolder}${this.value}.svg) repeat center center;`);
                });

                // background image
                jQuery('[name="styles[bg_image][image]"]').bind("change", function () {
                    const imageId = this.value;
                    wp.media.attachment(imageId).fetch().then(function (data) {
                        preview.css('background-image', `url(${wp.media.attachment(imageId).get('url')}`);
                    });
                });
                jQuery('.background-field .linkpage-image-remove').on('click', function () {
                    preview.css('background-image', '');
                });

                // background image controls change
                jQuery('#bgImageWrap').on('change', 'select', function () {
                    preview.css(jQuery(this).attr('id'), this.value);
                });

                // Link background color
                linkBgColor.wpColorPicker({
                    change: function (event, ui) {
                        jQuery('.linkpage-public-row-link').css('background-color', ui.color.toString());
                    }
                });

                // Link color
                linkColor.wpColorPicker({
                    change: function (event, ui) {
                        jQuery('.linkpage-public-row-link').css('color', ui.color.toString());
                    }
                });

                function get_logo_src() {
                    return jQuery('.linkpage-image-upload img').attr('src') || logoDefaultImgSrc;
                }

                function setBgStyle(style) {
                    let result = '';

                    switch (style) {
                        case 'gradient':
                            const gradient = jQuery(`[name="styles[bg_${style}]"]:checked`).val();

                            if (gradient === 'custom') {
                                result = jQuery('.gradient-style-custom-preview').attr('style');
                            } else {
                                result = jQuery(`#gradientStyle${gradient}`).next().attr('style');
                            }
                            break;
                        case 'pattern':
                            const patternID = jQuery(`[name="styles[bg_${style}]"]:checked`).val();

                            result = `background: url(${patternsFolder}${patternID}.svg) repeat center center;`;
                            break;
                        case 'image':
                            if (jQuery('.clickwhale-background-image img')) {
                                const
                                    imagePosX = jQuery('[name="styles[bg_image][x]"]').val(),
                                    imagePosY = jQuery('[name="styles[bg_image][y]"]').val(),
                                    imageRepeat = jQuery('[name="styles[bg_image][repeat]"]').val(),
                                    imageSize = jQuery('[name="styles[bg_image][size]"]').val();

                                let imageURL = jQuery('.clickwhale-background-image img').attr('src');
                                imageURL = imageURL.replace(/-\d+[Xx]\d+/gm, '');

                                result = `background-image: url(${imageURL}); background-repeat: ${imageRepeat}; background-position-x: ${imagePosX}; background-position-y: ${imagePosY}; background-size: ${imageSize};`;
                            }
                            break;
                        default:
                            const color = jQuery(`[name="styles[bg_${style}]"]`).val();
                            result = `background: ${color}`;
                    }

                    return result;
                }

            });
        </script>
		<?php
	}
}