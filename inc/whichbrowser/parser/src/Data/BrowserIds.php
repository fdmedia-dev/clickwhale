<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Data;

class BrowserIds
{
    public static $ANDROID_BROWSERS = [];

    public static function identify($model)
    {
        require_once __DIR__ . '/../../data/id-android.php';

        if (isset(BrowserIds::$ANDROID_BROWSERS[$model])) {
            return BrowserIds::$ANDROID_BROWSERS[$model];
        }
    }
}
