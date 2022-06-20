<?php
/**
 * @license MIT
 *
 * Modified by peterkrupenya on 20-June-2022 using Strauss.
 * @see https://github.com/BrianHenryIE/strauss
 */

namespace Clickwhale\Vendor\WhichBrowser\Analyser\Header;

class Baidu
{
    public function __construct($header, &$data)
    {
        $this->data =& $data;

        if (!isset($this->data->browser->name) || $this->data->browser->name != 'Baidu Browser') {
            $this->data->browser->name = 'Baidu Browser';
            $this->data->browser->version = null;
            $this->data->browser->stock = false;
        }
    }
}
