<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Data;

use Clickwhale\Vendor\WhichBrowser\Constants;

class Manufacturers
{
    public static $GENERIC = [];
    public static $TELEVISION = [];

    public static function identify($type, $name)
    {
        $name = preg_replace('/^CUS\:/u', '', trim($name));

        require_once __DIR__ . '/../../data/manufacturer-names.php';

        if ($type == Constants\DeviceType::TELEVISION) {
            if (isset(Manufacturers::$TELEVISION[$name])) {
                return self::$TELEVISION[$name];
            }
        }

        if (isset(Manufacturers::$GENERIC[$name])) {
            return self::$GENERIC[$name];
        }

        return $name;
    }
}
