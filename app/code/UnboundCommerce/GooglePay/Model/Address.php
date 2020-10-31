<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use UnboundCommerce\GooglePay\Api\Data\AddressInterface;

/**
 * Class Address
 */
class Address extends AbstractExtensibleModel implements AddressInterface
{
    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getData(self::NAME);
    }

    /**
     * {@inheritdoc}
     */
    public function setName($name)
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * {@inheritdoc}
     */
    public function getPostalCode()
    {
        return $this->getData(self::POSTAL_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setPostalCode($postalCode)
    {
        return $this->setData(self::POSTAL_CODE, $postalCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getCountryCode()
    {
        return $this->getData(self::COUNTRY_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setCountryCode($countryCode)
    {
        return $this->setData(self::COUNTRY_CODE, $countryCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getPhoneNumber()
    {
        return $this->getData(self::PHONE_NUMBER);
    }

    /**
     * {@inheritdoc}
     */
    public function setPhoneNumber($phoneNumber)
    {
        return $this->setData(self::PHONE_NUMBER, $phoneNumber);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddress1()
    {
        return $this->getData(self::ADDRESS_1);
    }

    /**
     * {@inheritdoc}
     */
    public function setAddress1($address1)
    {
        return $this->setData(self::ADDRESS_1, $address1);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddress2()
    {
        return $this->getData(self::ADDRESS_2);
    }

    /**
     * {@inheritdoc}
     */
    public function setAddress2($address2)
    {
        return $this->setData(self::ADDRESS_2, $address2);
    }

    /**
     * {@inheritdoc}
     */
    public function getAddress3()
    {
        return $this->getData(self::ADDRESS_3);
    }

    /**
     * {@inheritdoc}
     */
    public function setAddress3($address3)
    {
        return $this->setData(self::ADDRESS_3, $address3);
    }

    /**
     * {@inheritdoc}
     */
    public function getLocality()
    {
        return $this->getData(self::LOCALITY);
    }

    /**
     * {@inheritdoc}
     */
    public function setLocality($locality)
    {
        return $this->setData(self::LOCALITY, $locality);
    }

    /**
     * {@inheritdoc}
     */
    public function getAdministrativeArea()
    {
        return $this->getData(self::ADMINISTRATIVE_AREA);
    }

    /**
     * {@inheritdoc}
     */
    public function setAdministrativeArea($administrativeArea)
    {
        return $this->setData(self::ADMINISTRATIVE_AREA, $administrativeArea);
    }

    /**
     * {@inheritdoc}
     */
    public function getSortingCode()
    {
        return $this->getData(self::SORTING_CODE);
    }

    /**
     * {@inheritdoc}
     */
    public function setSortingCode($sortingCode)
    {
        return $this->setData(self::SORTING_CODE, $sortingCode);
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingMethod()
    {
        return $this->getData(self::SHIPPING_METHOD);
    }

    /**
     * {@inheritdoc}
     */
    public function setShippingMethod($shippingMethod)
    {
        return $this->setData(self::SHIPPING_METHOD, $shippingMethod);
    }
}
