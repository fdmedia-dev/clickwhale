<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Data;

class DeviceProfiles
{
    public static $PROFILES = [];

    public static function identify($url)
    {
        require_once __DIR__ . '/../../data/profiles.php';

        if (isset(self::$PROFILES[$url])) {
            return self::$PROFILES[$url];
        }

        return false;
    }
}
