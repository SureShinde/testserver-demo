<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Api\Data;

/**
 * Interface PaymentMethodDataInterface
 *
 * @api
 */
interface PaymentMethodDataInterface
{
    const TYPE = 'type';
    const DESCRIPTION = 'description';
    const INFO = 'info';
    const TOKENIZATION_DATA = 'tokenization_data';

    /**
     * Return payment method type
     *
     * @return string|null
     */
    public function getType();

    /**
     * Set payment method type
     *
     * @param  string $type
     * @return $this
     */
    public function setType($type);

    /**
     * Return payment method description
     *
     * @return string|null
     */
    public function getDescription();

    /**
     * Set payment method description
     *
     * @param  string $description
     * @return $this
     */
    public function setDescription($description);

    /**
     * Return card info
     *
     * @return \UnboundCommerce\GooglePay\Api\Data\CardInfoInterface
     */
    public function getInfo();

    /**
     * Set card info
     *
     * @param  \UnboundCommerce\GooglePay\Api\Data\CardInfoInterface $info
     * @return $this
     */
    public function setInfo($info);

    /**
     * Return payment tokenization data
     *
     * @return \UnboundCommerce\GooglePay\Api\Data\PaymentMethodTokenizationDataInterface
     */
    public function getTokenizationData();

    /**
     * Set payment tokenization data
     *
     * @param  \UnboundCommerce\GooglePay\Api\Data\PaymentMethodTokenizationDataInterface $tokenizationData
     * @return $this
     */
    public function setTokenizationData($tokenizationData);
}
