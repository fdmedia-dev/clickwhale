<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Analyser\Header\Useragent\Device;

use Clickwhale\Vendor\WhichBrowser\Constants;

trait Gps
{
    private function detectGps($ua)
    {
        if (!preg_match('/Nuvi/ui', $ua)) {
            return;
        }
        
        $this->detectGarmin($ua);
    }





    /* Garmin Nuvi */

    private function detectGarmin($ua)
    {
        if (preg_match('/Nuvi/u', $ua) && preg_match('/Qtopia/u', $ua)) {
            $this->data->device->setIdentification([
                'manufacturer'  =>  'Garmin',
                'model'         =>  'Nuvi',
                'type'          =>  Constants\DeviceType::GPS
            ]);
        }
    }
}
