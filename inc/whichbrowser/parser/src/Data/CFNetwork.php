<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Data;

class CFNetwork
{
    public static $OSX = [];
    public static $IOS = [];

    public static function getVersion($platform, $version)
    {
        require_once __DIR__ . '/../../data/os-cfnetwork.php';

        switch ($platform) {
            case 'osx':
                if (isset(CFNetwork::$OSX[$version])) {
                    return CFNetwork::$OSX[$version];
                }
                break;
            case 'ios':
                if (isset(CFNetwork::$IOS[$version])) {
                    return CFNetwork::$IOS[$version];
                }
                break;
        }
    }
}
