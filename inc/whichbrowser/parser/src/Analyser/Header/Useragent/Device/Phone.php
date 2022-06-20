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

trait Phone
{
    private function detectPhone($ua)
    {
        $this->detectNttTeless($ua);
        $this->detectSnom($ua);
    }





    /* NTT Teless */

    private function detectNttTeless($ua)
    {
        if (preg_match('/Product\=NTT\/Teless/ui', $ua, $match)) {
            $this->data->device->manufacturer = 'NTT';
            $this->data->device->model = 'Teless';
            $this->data->device->identified |= Constants\Id::MATCH_UA;
            $this->data->device->type = Constants\DeviceType::MOBILE;
            $this->data->device->subtype = Constants\DeviceSubType::DESKTOP;
        }
    }

    /* SNOM */

    private function detectSnom($ua)
    {
        if (preg_match('/snom(.+)-SIP/ui', $ua, $match)) {
            $this->data->device->manufacturer = 'SNOM';
            $this->data->device->model = $match[1] . ' IP Telephone';
            $this->data->device->identified |= Constants\Id::MATCH_UA;
            $this->data->device->type = Constants\DeviceType::MOBILE;
            $this->data->device->subtype = Constants\DeviceSubType::DESKTOP;
        }
    }
}
