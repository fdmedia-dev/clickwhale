<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Analyser\Header\Useragent\Device;

use Clickwhale\Vendor\WhichBrowser\Constants;

trait Printer
{
    private function detectPrinter($ua)
    {
        if (!preg_match('/(TASKalfa|CanonIJCL|IR-S|PrintSmart|EpsonHello)/ui', $ua)) {
            return;
        }

        /* TASKalfa */

        if (preg_match('/TASKalfa ([0-9A-Z]+)/iu', $ua, $match)) {
            $this->data->device->setIdentification([
                'manufacturer'  =>  'Kyocera',
                'model'         =>  'TASKalfa ' . $match[1],
                'type'          =>  Constants\DeviceType::PRINTER
            ]);
        }


        /* Canon IJ */

        if (preg_match('/CanonIJCL/iu', $ua, $match)) {
            $this->data->device->setIdentification([
                'manufacturer'  =>  'Canon',
                'model'         =>  'IJ Printer',
                'type'          =>  Constants\DeviceType::PRINTER
            ]);
        }

        /* Canon iR S */

        if (preg_match('/IR-S/iu', $ua, $match)) {
            $this->data->device->setIdentification([
                'manufacturer'  =>  'Canon',
                'model'         =>  'imageRUNNER',
                'type'          =>  Constants\DeviceType::PRINTER
            ]);
        }

        /* HP Web PrintSmart */

        if (preg_match('/HP Web PrintSmart/iu', $ua, $match)) {
            $this->data->device->setIdentification([
                'manufacturer'  =>  'HP',
                'model'         =>  'Web PrintSmart',
                'type'          =>  Constants\DeviceType::PRINTER
            ]);
        }

        /* Epson Hello */

        if (preg_match('/EpsonHello\//iu', $ua, $match)) {
            $this->data->device->setIdentification([
                'manufacturer'  =>  'Epson',
                'model'         =>  'Hello',
                'type'          =>  Constants\DeviceType::PRINTER
            ]);
        }
    }
}
