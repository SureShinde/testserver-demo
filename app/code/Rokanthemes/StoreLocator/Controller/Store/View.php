<?php
/**
 * Copyright © 2019 Rokanthemes. All rights reserved. 
 */

namespace Rokanthemes\StoreLocator\Controller\Store;

class View extends \Rokanthemes\StoreLocator\Controller\Index
{
    /**
     * @return \Magento\Framework\View\Result\Page
     */
    public function execute()
    {
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Store Locator'));

        return $resultPage;
    }
}
