<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Api\Data;

/**
 * Interface PaymentMethodTokenizationDataInterface
 *
 * @api
 */
interface PaymentMethodTokenizationDataInterface
{
    const TYPE = 'type';
    const TOKEN = 'token';

    /**
     * Return the type of tokenization
     *
     * @return string|null
     */
    public function getType();

    /**
     * Set the type of tokenization
     *
     * @param  string $type
     * @return $this
     */
    public function setType($type);

    /**
     * Return payment method token
     *
     * @return string|null
     */
    public function getToken();

    /**
     * Set payment method token
     *
     * @param  string $token
     * @return $this
     */
    public function setToken($token);
}
