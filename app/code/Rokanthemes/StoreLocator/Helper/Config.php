<?php
/**
 * Copyright © 2019 Rokanthemes. All rights reserved.
 */

namespace Rokanthemes\StoreLocator\Helper;

use \Magento\Framework\App\Helper\AbstractHelper;
use \Magento\Framework\App\Config\ScopeConfigInterface;
use \Magento\Store\Model\ScopeInterface;

class Config extends AbstractHelper
{
    /**
     * @return string|null
     */
    public function getGoogleApiKeyFrontend()
    {
        return $this->scopeConfig->getValue('storelocator/google_api_key/frontend', ScopeConfigInterface::SCOPE_TYPE_DEFAULT);
    }
}
