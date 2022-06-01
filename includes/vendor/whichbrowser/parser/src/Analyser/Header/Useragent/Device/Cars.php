<?php

namespace Flowdee\ClickWhale\Vendor\WhichBrowser\Analyser\Header\Useragent\Device;

use Flowdee\ClickWhale\Vendor\WhichBrowser\Constants;

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
