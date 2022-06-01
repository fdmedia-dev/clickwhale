<?php

namespace Flowdee\ClickWhale\Vendor\WhichBrowser\Model;

use Flowdee\ClickWhale\Vendor\WhichBrowser\Model\Primitive\NameVersion;

class Engine extends NameVersion
{
    /**
     * Get an array of all defined properties
     *
     * @internal
     *
     * @return array
     */

    public function toArray()
    {
        $result = [];

        if (!empty($this->name)) {
            $result['name'] = $this->name;
        }
        
        if (!empty($this->version)) {
            $result['version'] = $this->version->toArray();
        }

        if (isset($result['version']) && empty($result['version'])) {
            unset($result['version']);
        }

        return $result;
    }
}
