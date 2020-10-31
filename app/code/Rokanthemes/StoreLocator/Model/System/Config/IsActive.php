<?php
/**
 * Copyright © 2019 Rokanthemes. All rights reserved.
 */

namespace Rokanthemes\StoreLocator\Model\System\Config;

use \Magento\Framework\Option\ArrayInterface;
use \Rokanthemes\StoreLocator\Model\Source\IsActive as Source;

class IsActive implements ArrayInterface
{
    /**
     * @var \Rokanthemes\StoreLocator\Model\Source\IsActive
     */
    private $source;


    public function __construct(Source $source)
    {
        $this->source = $source;
    }

    public function toOptionArray()
    {
        return $this->source->getAvailableStatuses();
    }
}
