<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Api\Data;

/**
 * Interface AdyenWebhookInterface
 *
 * @api
 */
interface AdyenWebhookInterface
{
    const WEBHOOK_ID = 'webhook_id';
    const IS_PRODUCTION = 'is_production';
    const PSP_REFERENCE = 'psp_reference';
    const ORIGINAL_REFERENCE = 'original_reference';
    const MERCHANT_REFERENCE = 'merchant_reference';
    const EVENT_CODE = 'event_code';
    const SUCCESS = 'success';
    const PAYMENT_METHOD = 'payment_method';
    const AMOUNT_VALUE = 'amount_value';
    const AMOUNT_CURRENCY = 'amount_currency';
    const REASON = 'reason';
    const ADDITIONAL_DATA = 'additional_data';
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';
    const PROCESSED = 'processed';


    /**
     * Return webhook id
     *
     * @return integer
     */
    public function getWebhookId();

    /**
     * Set webhook id
     *
     * @param  integer $webhookId
     * @return $this
     */
    public function setWebhookId($webhookId);

    /**
     * Check if webhook is in production environment
     *
     * @return boolean
     */
    public function getIsProduction();

    /**
     * Set if webhook is in production environment
     *
     * @param  boolean $isProduction
     * @return $this
     */
    public function setIsProduction($isProduction);

    /**
     * Return psp reference
     *
     * @return string
     */
    public function getPspReference();

    /**
     * Set psp reference
     *
     * @param  string $pspReference
     * @return $this
     */
    public function setPspReference($pspReference);

    /**
     * Return original reference
     *
     * @return string
     */
    public function getOriginalReference();

    /**
     * Set original reference
     *
     * @param  string $originalReference
     * @return $this
     */
    public function setOriginalReference($originalReference);

    /**
     * Return merchant reference
     *
     * @return string
     */
    public function getMerchantReference();

    /**
     * Set merchant reference
     *
     * @param  string $merchantReference
     * @return $this
     */
    public function setMerchantReference($merchantReference);

    /**
     * Return event code
     *
     * @return string
     */
    public function getEventCode();

    /**
     * Set event code
     *
     * @param  string $eventCode
     * @return $this
     */
    public function setEventCode($eventCode);

    /**
     * Check whether transaction succeeded or not
     *
     * @return string
     */
    public function getSuccess();

    /**
     * Set whether transaction succeeded or not
     *
     * @param  string $success
     * @return $this
     */
    public function setSuccess($success);

    /**
     * Return payment method
     *
     * @return string
     */
    public function getPaymentMethod();

    /**
     * Set payment method
     *
     * @param  string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod);

    /**
     * Get amount
     *
     * @return string
     */
    public function getAmountValue();

    /**
     * Set amount
     *
     * @param  string $amountValue
     * @return $this
     */
    public function setAmountValue($amountValue);

    /**
     * Return currency
     *
     * @return string
     */
    public function getAmountCurrency();

    /**
     * Set currency
     *
     * @param  string $amountCurrency
     * @return $this
     */
    public function setAmountCurrency($amountCurrency);

    /**
     * Return reason
     *
     * @return string
     */
    public function getReason();

    /**
     * Set reason
     *
     * @param  string $reason
     * @return $this
     */
    public function setReason($reason);

    /**
     * Return additional data
     *
     * @return string
     */
    public function getAdditionalData();

    /**
     * Set additional data
     *
     * @param  string $additionalData
     * @return $this
     */
    public function setAdditionalData($additionalData);

    /**
     * Return created time
     *
     * @return string
     */
    public function getCreatedAt();

    /**
     * Set created time
     *
     * @param  string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt);

    /**
     * Return updated time
     *
     * @return string
     */
    public function getUpdatedAt();

    /**
     * Set updated time
     *
     * @param  string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt);

    /**
     * Check whether webhook has been processed or not
     *
     * @return boolean
     */
    public function getProcessed();

    /**
     * Set whether webhook has been processed or not
     *
     * @param  boolean $processed
     * @return $this
     */
    public function setProcessed($processed);
}
