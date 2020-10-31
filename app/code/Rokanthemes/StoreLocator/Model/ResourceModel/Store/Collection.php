<?php
/**
 * Copyright © 2019 Rokanthemes. All rights reserved.
 */

namespace Rokanthemes\StoreLocator\Model\ResourceModel\Store;

use \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use \Rokanthemes\StoreLocator\Model\Store as Model;
use \Rokanthemes\StoreLocator\Model\ResourceModel\Store as ResourceModel;

class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'store_id';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Model::class, ResourceModel::class);
    }
}
