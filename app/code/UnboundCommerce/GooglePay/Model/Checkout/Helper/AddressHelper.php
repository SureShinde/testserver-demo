<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model\Checkout\Helper;

use Magento\Directory\Model\RegionFactory;
use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Tax\Helper\Data;

class AddressHelper extends \Magento\Framework\App\Helper\AbstractHelper
{

    /**
     * @var RegionFactory
     */
    protected $regionFactory;

    /**
     * @var Data
     */
    protected $taxHelper;

    /**
     * @var PriceCurrencyInterface
     */
    protected $priceCurrency;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        RegionFactory $regionFactory,
        Data $taxHelper,
        PriceCurrencyInterface $priceCurrency
    ) {
        $this->regionFactory = $regionFactory;
        $this->taxHelper = $taxHelper;
        $this->priceCurrency = $priceCurrency;
        parent::__construct($context);
    }

    /**
     * Get the region ID based on the region code.
     *
     * @param  $regionCode - region code
     * @param  $countryId  - country code
     * @return int|bool
     */
    public function getRegionIdByCode($regionCode, $countryId)
    {
        try {
            $region = $this->regionFactory->create();
            $regionId = $region->loadByCode($regionCode, $countryId)->getId();
            return $regionId;
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * Get the region ID based on the region name
     *
     * @param  $regionName - region name
     * @param  $countryId  - country code
     * @return string|bool
     */
    public function getRegionCodeByName($regionName, $countryId)
    {
        try {
            $region = $this->regionFactory->create();
            $regionCode = $region->loadByName($regionName, $countryId)->getCode();
            return $regionCode;
        } catch (\Exception $e) {
            $this->_logger->critical($e->getMessage());
            return false;
        }
    }

    /**
     * Get the shipping method label based on price and scope
     *
     * @param  $priceExclTax - price excluding tax
     * @param  $priceInclTax - price including tax
     * @param  $methodTitle  - shipping method title
     * @param  $store
     * @return string|bool
     */
    public function getShippingMethodLabel($priceExclTax, $priceInclTax, $methodTitle, $store)
    {
        $format = '%s : %s%s';
        $inclTaxFormat = ' (%s %s)';
        $renderedInclTax = '';
        $price = $priceExclTax;
        if ($this->taxHelper->displayShippingPriceIncludingTax()) {
            $price = $priceInclTax;
        }

        if ($priceInclTax != $price && $this->taxHelper->displayShippingBothPrices()) {
            $renderedInclTax = sprintf($inclTaxFormat, 'Incl. Tax', $priceInclTax);
        }

        $shippingPrice = $this->getShippingPrice($price, $store);
        return sprintf($format, $shippingPrice, $methodTitle, $renderedInclTax);
    }

    /**
     * Get formatted shipping price
     *
     * @param  $price
     * @param  $store
     * @return string|bool
     */
    public function getShippingPrice($price, $store)
    {
        return $this->priceCurrency->format($price, false, 2, $store);
    }
}
