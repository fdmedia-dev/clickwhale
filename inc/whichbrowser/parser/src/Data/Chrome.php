<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Data;

class Chrome
{
    public static $DESKTOP = [];
    public static $MOBILE = [];

    public static function getChannel($platform, $version)
    {
        require_once __DIR__ . '/../../data/browsers-chrome.php';

        $version = implode('.', array_slice(explode('.', $version), 0, 3));

        switch ($platform) {
            case 'desktop':
                if (isset(Chrome::$DESKTOP[$version])) {
                    return Chrome::$DESKTOP[$version];
                }
                break;
            case 'mobile':
                if (isset(Chrome::$MOBILE[$version])) {
                    return Chrome::$MOBILE[$version];
                }
                break;
        }

        return 'canary';
    }
}
