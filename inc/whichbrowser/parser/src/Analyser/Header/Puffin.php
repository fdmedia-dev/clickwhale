<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Analyser\Header;

use Clickwhale\Vendor\WhichBrowser\Data;

class Puffin
{
    public function __construct($header, &$data)
    {
        $this->data =& $data;

        $parts = explode('/', $header);

        if ($this->data->browser->name != 'Puffin') {
            $this->data->browser->name = 'Puffin';
            $this->data->browser->version = null;
            $this->data->browser->stock = false;
        }

        $this->data->device->type = 'mobile';

        if (count($parts) > 1 && $parts[0] == 'Android') {
            if (!isset($this->data->os->name) || $this->data->os->name != 'Android') {
                $this->data->os->name = 'Android';
                $this->data->os->version = null;
            }

            $device = Data\DeviceModels::identify('android', $parts[1]);
            if ($device->identified) {
                $device->identified |= $this->data->device->identified;
                $this->data->device = $device;
            }
        }

        if (count($parts) > 1 && $parts[0] == 'iPhone OS') {
            if (!isset($this->data->os->name) || $this->data->os->name != 'iOS') {
                $this->data->os->name = 'iOS';
                $this->data->os->version = null;
            }

            $device = Data\DeviceModels::identify('ios', $parts[1]);

            if ($device->identified) {
                $device->identified |= $this->data->device->identified;
                $this->data->device = $device;
            }
        }
    }
}
