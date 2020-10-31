<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Block\Adminhtml\System\Config\Form\Field;

use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\UrlInterface;
use UnboundCommerce\GooglePay\Gateway\Config\Config;

/**
 * AdyenWebhookUrl block
 *
 * @api
 */
class AdyenWebhookUrl extends Field
{
    /**
     * Render Adyen webhook url
     *
     * @param  AbstractElement $element
     * @return string
     */
    protected function _renderValue(AbstractElement $element)
    {
        $stores = $this->_storeManager->getStores();
        $valueReturn = '';
        $urlArray = [];

        foreach ($stores as $store) {
            $baseUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_WEB, true);
            if ($baseUrl) {
                $value      = $baseUrl . Config::CODE . '/webhook/adyen';
                $urlArray[] = "<div>".$this->escapeHtml($value)."</div>";
            }
        }

        $urlArray = array_unique($urlArray);
        foreach ($urlArray as $uniqueUrl) {
            $valueReturn .= "<div>".$uniqueUrl."</div>";
        }

        return '<td class="value">' . $valueReturn . '</td>';
    }
}
