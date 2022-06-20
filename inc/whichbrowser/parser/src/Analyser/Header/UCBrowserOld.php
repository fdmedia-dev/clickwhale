<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Analyser\Header;

use Clickwhale\Vendor\WhichBrowser\Parser;
use Clickwhale\Vendor\WhichBrowser\Constants;

class UCBrowserOld
{
    public function __construct($header, &$data)
    {
        $this->data =& $data;

        if ($this->data->device->type == Constants\DeviceType::DESKTOP) {
            $this->data->device->type = Constants\DeviceType::MOBILE;

            $this->data->os->reset();
        }

        if (!isset($this->data->browser->name) || $this->data->browser->name != 'UC Browser') {
            $this->data->browser->name = 'UC Browser';
            $this->data->browser->version = null;
        }

        $this->data->browser->mode = 'proxy';
        $this->data->engine->reset([ 'name' => 'Gecko' ]);

        $extra = new Parser([ 'headers' => [ 'User-Agent' => $header ]]);
        
        if ($extra->device->type != Constants\DeviceType::DESKTOP) {
            if ($extra->os->getName() !== '' && ($this->data->os->getName() === '' || $extra->os->getVersion() !== '')) {
                $this->data->os = $extra->os;
            }
            if ($extra->device->identified) {
                $this->data->device = $extra->device;
            }
        }
    }
}
