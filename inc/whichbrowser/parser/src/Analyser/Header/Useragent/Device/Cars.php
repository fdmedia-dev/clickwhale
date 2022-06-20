<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Analyser\Header\Useragent\Device;

use Clickwhale\Vendor\WhichBrowser\Constants;

trait Cars
{
    private function detectCars($ua)
    {
        if (!preg_match('/Car/ui', $ua)) {
            return;
        }

        $this->detectTesla($ua);
    }





    /* Tesla S */

    private function detectTesla($ua)
    {
        if (preg_match('/QtCarBrowser/u', $ua)) {
            $this->data->os->reset();
            $this->data->device->setIdentification([
                'manufacturer'  =>  'Tesla',
                'model'         =>  'Model S',
                'type'          =>  Constants\DeviceType::CAR
            ]);
        }
    }
}
