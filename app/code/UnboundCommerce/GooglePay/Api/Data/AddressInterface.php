<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Api\Data;

/**
 * Interface AddressInterface
 *
 * @api
 */
interface AddressInterface
{
    const NAME = 'name';
    const POSTAL_CODE = 'postal_code';
    const COUNTRY_CODE = 'country_code';
    const PHONE_NUMBER = 'phone_number';
    const ADDRESS_1 = 'address_1';
    const ADDRESS_2 = 'address_2';
    const ADDRESS_3 = 'address_3';
    const LOCALITY = 'locality';
    const ADMINISTRATIVE_AREA = 'administrative_area';
    const SORTING_CODE = 'sorting_code';
    const SHIPPING_METHOD = 'shipping_method';

    /**
     * Return name in address
     *
     * @return string|null
     */
    public function getName();

    /**
     * Set name in address
     *
     * @param  string $name
     * @return $this
     */
    public function setName($name);

    /**
     * Return postal code
     *
     * @return string|null
     */
    public function getPostalCode();

    /**
     * Set postal code
     *
     * @param  string $postalCode
     * @return $this
     */
    public function setPostalCode($postalCode);

    /**
     * Return country code
     *
     * @return string|null
     */
    public function getCountryCode();

    /**
     * Set country code
     *
     * @param  string $countryCode
     * @return $this
     */
    public function setCountryCode($countryCode);

    /**
     * Return telephone number in address
     *
     * @return string|null
     */
    public function getPhoneNumber();

    /**
     * Set telephone number for address
     *
     * @param  string $phoneNumber
     * @return $this
     */
    public function setPhoneNumber($phoneNumber);

    /**
     * Return address line 1
     *
     * @return string|null
     */
    public function getAddress1();

    /**
     * Set address line 1
     *
     * @param  string $address1
     * @return $this
     */
    public function setAddress1($address1);

    /**
     * Return address line 2
     *
     * @return string|null
     */
    public function getAddress2();

    /**
     * Set address line 2
     *
     * @param  string $address2
     * @return $this
     */
    public function setAddress2($address2);

    /**
     * Return address line 3
     *
     * @return string|null
     */
    public function getAddress3();

    /**
     * Set address line 3
     *
     * @param  string $address3
     * @return $this
     */
    public function setAddress3($address3);

    /**
     * Return locality (City, town, neighborhood or suburb)
     *
     * @return string|null
     */
    public function getLocality();

    /**
     * Set locality
     *
     * @param  string $locality
     * @return $this
     */
    public function setLocality($locality);

    /**
     * Return administrativeArea (Country subdivision, such as a state or province)
     *
     * @return string|null
     */
    public function getAdministrativeArea();

    /**
     * Set administrativeArea
     *
     * @param  string $administrativeArea
     * @return $this
     */
    public function setAdministrativeArea($administrativeArea);

    /**
     * Return sorting code
     *
     * @return string|null
     */
    public function getSortingCode();

    /**
     * Set sorting code
     *
     * @param  string $sortingCode
     * @return $this
     */
    public function setSortingCode($sortingCode);

    /**
     * Return shipping method code
     *
     * @return string|null
     */
    public function getShippingMethod();

    /**
     * Set shipping method code
     *
     * @param  string $shippingMethod
     * @return $this
     */
    public function setShippingMethod($shippingMethod);
}
