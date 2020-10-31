<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Block\Minicart;

use Magento\Catalog\Block\ShortcutInterface;
use Magento\Checkout\Helper\Data;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Quote\Model\QuoteIdMaskFactory;
use UnboundCommerce\GooglePay\Gateway\Config\Config;

/**
 * GooglePay Cart/Mini cart block
 */
class Button extends Template implements ShortcutInterface
{
    const ALIAS_ELEMENT_INDEX = 'alias';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var CheckoutSession
     */
    protected $checkoutSession;

    /**
     * @var CustomerSession
     */
    protected $customerSession;

    /**
     * @var Data
     */
    protected $checkoutHelper;

    /**
     * @var EavConfig
     */
    protected $eavConfig;

    /**
     * @var bool
     */
    protected $isMiniCart = false;

    /**
     * @param QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context               $context
     * @param Config                $config
     * @param CheckoutSession       $checkoutSession
     * @param CustomerSession       $customerSession
     * @param Data                  $checkoutHelper
     * @param EavConfig             $eavConfig
     * @param QuoteIdMaskFactory    $quoteIdMaskFactory
     * @param StoreManagerInterface $storeManager
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        Config $config,
        CheckoutSession $checkoutSession,
        CustomerSession $customerSession,
        Data $checkoutHelper,
        EavConfig $eavConfig,
        QuoteIdMaskFactory $quoteIdMaskFactory,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
        $this->customerSession = $customerSession;
        $this->checkoutHelper = $checkoutHelper;
        $this->eavConfig = $eavConfig;
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritdoc
     */
    protected function _toHtml()
    {
        if ($this->shouldRender()) {
            return parent::_toHtml();
        }
        return '';
    }

    /**
     * Check whether button should be rendered in cart or mini-cart
     *
     * @return bool
     */
    protected function shouldRender()
    {
        if (!$this->isActive()) {
            return false;
        }
        if ($this->getIsInCart()) {
            return true;
        }
        return $this->isMiniCart && $this->showInMiniCart();
    }

    /**
     * Return store id
     *
     * @return int
     */
    public function getStoreId()
    {
        return $this->checkoutSession->getQuote()->getStoreId();
    }

    /**
     * Return currency code
     *
     * @return string
     */
    public function getCurrentCurrencyCode()
    {
        return $this->storeManager->getStore()->getCurrentCurrencyCode();
    }

    /**
     * Return alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
    }

    /**
     * Check whether telephone is required for address.
     *
     * @return bool
     */
    protected function isTelephoneRequired()
    {
        try {
            $isTelephoneRequired = $this->eavConfig->getAttribute('customer_address', 'telephone')->getIsRequired();
            if ($isTelephoneRequired == "1" || $isTelephoneRequired == "true" || $isTelephoneRequired == true) {
                return true;
            }
            return false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Return available card types
     *
     * @return array
     */
    public function getAvailableCardTypes()
    {
        return $this->config->getAvailableCardTypes($this->getStoreId());
    }

    /**
     * Return merchant id
     *
     * @return string
     */
    public function getMerchantId()
    {
        return $this->config->getMerchantId($this->getStoreId());
    }

    /**
     * Return merchant name
     *
     * @return string
     */
    public function getMerchantName()
    {
        return $this->config->getMerchantName($this->getStoreId());
    }

    /**
     * Return whether shipping address is required
     *
     * @return bool
     */
    public function isShippingAddressRequired()
    {
        if ($this->checkoutSession->getQuote()->isVirtual()) {
            return false;
        }
        $isShippingAddressRequired = $this->config->isShippingAddressRequired($this->getStoreId());
        if ($isShippingAddressRequired == "1" || $isShippingAddressRequired == "true" || $isShippingAddressRequired == true) {
            return true;
        }
        return false;
    }

    /**
     * Return whether quote is virtual
     *
     * @return bool
     */
    public function isQuoteVirtual()
    {
        return $this->checkoutSession->getQuote()->isVirtual();
    }

    /**
     * Return whether user is logged in
     *
     * @return bool
     */
    public function isUserLoggedIn()
    {
        return $this->customerSession->isLoggedIn();
    }

    /**
     * Return whether billing address is required
     *
     * @return bool
     */
    public function isBillingAddressRequired()
    {
        $isBillingAddressRequired = $this->config->isBillingAddressRequired($this->getStoreId());
        if ($isBillingAddressRequired == "1" || $isBillingAddressRequired == "true" || $isBillingAddressRequired == true) {
            return true;
        }
        return false;
    }

    /**
     * Return whether google pay is active
     *
     * @return bool
     */
    public function isActive()
    {
        return $this->config->isActive($this->getStoreId());
    }

    /**
     * Return google pay button color
     *
     * @return string
     */
    public function getButtonColor()
    {
        return $this->config->getButtonColor($this->getStoreId());
    }

    /**
     * Return google pay button type
     *
     * @return string
     */
    public function getButtonType()
    {
        return $this->config->getButtonType($this->getStoreId());
    }

    /**
     * Return whether google pay button should be displayed in mini cart
     *
     * @return bool
     */
    public function showInMiniCart()
    {
        $showInMiniCart= $this->config->showInMiniCart($this->getStoreId());
        if ($showInMiniCart == "1" || $showInMiniCart == "true" || $showInMiniCart == true) {
            return true;
        }
        return false;
    }

    /**
     * Return frontend credentials
     *
     * @return array
     */
    public function getGatewayFrontendCredentials()
    {
        return $this->config->getGatewayFrontendCredentials();
    }

    /**
     * Return estimated amount
     *
     * @return float
     */
    public function getEstimatedAmount()
    {
        $quote = $this->checkoutSession->getQuote();
        $estimatedAmount = number_format($quote->getGrandTotal(), 2, '.', '');
        return $estimatedAmount;
    }

    /**
     * Return quote id
     *
     * @return int
     */
    public function getQuoteId()
    {
        return $this->checkoutSession->getQuote()->getEntityId();
    }

    /**
     * Return masked quote id
     *
     * @return int|null
     */
    public function getMaskedQuoteId()
    {
        $quote = $this->checkoutSession->getQuote();
        if (!$quote->getCustomer()->getId()) {
            /**
 * @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask 
*/
            $quoteIdMask = $this->quoteIdMaskFactory->create();
            return $quoteIdMask->load(
                $this->checkoutSession->getQuote()->getId(),
                'quote_id'
            )->getMaskedId();
        }
        return null;
    }

    /**
     * Check whether redirection to cart is required on button click
     *
     * @return bool
     */
    public function shouldRedirectToCart()
    {
        $quote = $this->checkoutSession->getQuote();
        $redirectToCart = false;
        if (!$this->customerSession->isLoggedIn() && !$this->checkoutHelper->isAllowedGuestCheckout($quote)) {
            $redirectToCart = true;
        }
        return $redirectToCart;
    }

    /**
     * Return transaction information
     *
     * @return array
     */
    public function getTransactionInfo()
    {
        $quote = $this->checkoutSession->getQuote();
        if ($quote->isVirtual()) {
            $totals = $quote->getTotals();
        } else {
            $totals = $quote->getShippingAddress()->getTotals();
        }

        $data = [];
        $data['displayItems'] = [];
        foreach ($totals as $code => $total) {
            $arr = $total->toArray();
            if ($code == 'grand_total') {
                $data['totalPriceLabel'] = $arr['title'];
                $data['totalPrice'] = number_format($arr['value'], 2, '.', '');
            } elseif ($arr['value'] != null) {
                $item = [];
                if ($code == 'subtotal') {
                    $item['type'] = 'SUBTOTAL';
                } elseif ($code == 'tax') {
                    $item['type'] = 'TAX';
                } else {
                    $item['type'] = 'LINE_ITEM';
                }
                $item['label'] = $arr['title'];
                $item['price'] = number_format($arr['value'], 2, '.', '');
                $data['displayItems'][] = $item;
            }
        }

        return $data;
    }

    /**
     * @param  bool $isCatalog
     * @return $this
     */
    public function setIsInCatalogProduct($isCatalog)
    {
        $this->isMiniCart = !$isCatalog;

        return $this;
    }

    /**
     * Return configuration data
     *
     * @param  string|null $containerId
     * @return array
     */
    public function getConfigData($containerId = null)
    {
        $res['active'] = $this->isActive();
        $res['render'] = $this->shouldRender();
        $res['cartName'] = $this->getCartName();
        $res['merchantId'] = $this->getMerchantId();
        $res['merchantName'] = $this->getMerchantName();
        $res['ccTypes'] = $this->getAvailableCardTypes();
        $res['shippingAddressRequired'] = $this->isShippingAddressRequired();
        $res['billingAddressRequired'] = $this->isBillingAddressRequired();
        $res['buttonColor'] = $this->getButtonColor();
        $res['buttonType'] = $this->getButtonType();
        $res['showInMiniCart'] = $this->showInMiniCart();
        $res['currencyCode'] = $this->getCurrentCurrencyCode();
        $res['quoteId'] = $this->getQuoteId();
        $res['maskedQuoteId'] = $this->getMaskedQuoteId();
        $res['isUserLoggedIn'] = $this->isUserLoggedIn();
        $res['estimatedAmount'] = $this->getEstimatedAmount();
        $res['gatewayInfo'] = $this->getGatewayFrontendCredentials();
        $res['redirectToCart'] = $this->shouldRedirectToCart();
        $res['containerId'] = $containerId;
        $res['transactionInfo'] = $this->getTransactionInfo();
        $res['isQuoteVirtual'] = $this->isQuoteVirtual();
        $res['isMiniCart'] = !$this->getIsInCart();
        $res['isTelephoneRequired'] = $this->isTelephoneRequired();
        return $res;
    }
}
