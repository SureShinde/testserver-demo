<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Block\Minicart;

use Magento\Catalog\Block\ShortcutInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use UnboundCommerce\GooglePay\Gateway\Config\Config;

/**
 * GooglePay Addons block
 *
 * @api
 */
class Addons extends Template implements ShortcutInterface
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
     * @param Context         $context
     * @param Config          $config
     * @param CheckoutSession $checkoutSession
     * @param array           $data
     */
    public function __construct(
        Context $context,
        Config $config,
        CheckoutSession $checkoutSession,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->config = $config;
        $this->checkoutSession = $checkoutSession;
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
     * Check whether googlepay mini-cart addons should be rendered
     *
     * @return bool
     */
    protected function shouldRender()
    {
        if (!$this->isActive() || !$this->showInMiniCart()) {
            return false;
        }

        if (!$this->shouldRenderAgreements() && !$this->shouldRenderCoupon()) {
            return false;
        }

        return true;
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
     * Check whether checkout agreements block should be rendered in mini-cart addons
     *
     * @return bool
     */
    public function shouldRenderAgreements()
    {
        if (!$this->config->showAgreementsInAddons($this->getStoreId())) {
            return false;
        }

        return true;
    }

    /**
     * Check whether coupon block should be rendered in mini-cart addons
     *
     * @return bool
     */
    public function shouldRenderCoupon()
    {
        if (!$this->config->showCouponInAddons($this->getStoreId())) {
            return false;
        }

        return true;
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
     * Return Alias
     *
     * @return string
     */
    public function getAlias()
    {
        return $this->getData(self::ALIAS_ELEMENT_INDEX);
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
}
