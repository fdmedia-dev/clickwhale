<?php

namespace Flowdee\ClickWhale\Vendor\WhichBrowser\Analyser\Header\Useragent\Device;

use Flowdee\ClickWhale\Vendor\WhichBrowser\Constants;

trait Appliance
{
    private function detectAppliance($ua)
    {
        $this->detectIOpener($ua);
        $this->detectWebLight($ua);
    }





    /* Netpliance i-Opener */

    private function detectIOpener($ua)
    {
        if (preg_match('/I-Opener [0-9.]+; Netpliance/u', $ua)) {
            $this->data->os->reset();
            $this->data->device->setIdentification([
                'manufacturer'  =>  'Netpliance',
                'model'         =>  'i-Opener',
                'type'          =>  Constants\DeviceType::DESKTOP
            ]);
        }
    }

    /* KOMATSU WebLight */

    private function detectWebLight($ua)
    {
        if (preg_match('/KOMATSU.*WL\//u', $ua)) {
            $this->data->os->reset();
            $this->data->device->setIdentification([
                'manufacturer'  =>  'KOMATSU',
                'model'         =>  'WebLight',
                'type'          =>  Constants\DeviceType::DESKTOP
            ]);
        }
    }
}
