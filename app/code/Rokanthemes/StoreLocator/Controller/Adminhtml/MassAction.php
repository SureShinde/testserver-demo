<?php
/**
 * Copyright © 2019 Rokanthemes. All rights reserved.
 */

namespace Rokanthemes\StoreLocator\Controller\Adminhtml;

use \Magento\Backend\App\Action;
use \Magento\Ui\Component\MassAction\Filter;
use \Rokanthemes\StoreLocator\Model\ResourceModel\Store\CollectionFactory as StoreCollectionFactory;
use \Rokanthemes\StoreLocator\Api\StoreRepositoryInterface;

abstract class MassAction extends Action
{
    protected $filter; 
    protected $storeCollectionFactory;
    protected $storeRepository;


    public function __construct(
        Action\Context $context,
        Filter $filter,
        StoreCollectionFactory $storeCollectionFactory,
        StoreRepositoryInterface $storeRepository
    ) {
        $this->filter = $filter;
        $this->storeRepository = $storeRepository;
        $this->categoryRepository= $categoryRepository;
        parent::__construct($context);
    }
}
