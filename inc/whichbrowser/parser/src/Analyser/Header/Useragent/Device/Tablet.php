<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Analyser\Header\Useragent\Device;

use Clickwhale\Vendor\WhichBrowser\Constants;
use Clickwhale\Vendor\WhichBrowser\Data;
use Clickwhale\Vendor\WhichBrowser\Model\Version;

trait Tablet
{
    private function detectTablet($ua)
    {
        $this->detectWebTab($ua);
    }





    /* WeTab */

    private function detectWebTab($ua)
    {
        if (preg_match('/WeTab-Browser /ui', $ua, $match)) {
            $this->data->device->manufacturer = 'WeTab';
            $this->data->device->model = 'WeTab';
            $this->data->device->identified |= Constants\Id::MATCH_UA;
            $this->data->device->type = Constants\DeviceType::TABLET;

            $this->data->browser->name = 'WebTab Browser';
            $this->data->browser->version = null;

            $this->data->os->name = 'MeeGo';
            $this->data->os->version = null;
        }
    }
}
