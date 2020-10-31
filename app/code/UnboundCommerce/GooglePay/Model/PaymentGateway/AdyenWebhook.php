<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model\PaymentGateway;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\Context;
use Magento\Framework\Model\ResourceModel\AbstractResource;
use Magento\Framework\Registry;
use UnboundCommerce\GooglePay\Api\Data\AdyenWebhookInterface;
use UnboundCommerce\GooglePay\Model\ResourceModel\AdyenWebhook as AdyenWebhookResourceModel;

/**
 * Class AdyenWebhook
 */
class AdyenWebhook extends AbstractModel implements AdyenWebhookInterface
{
    const AUTHORISATION = 'AUTHORISATION';
    const CAPTURE = 'CAPTURE';
    const CAPTURE_FAILED = 'CAPTURE_FAILED';
    const PENDING = 'PENDING';
    const REFUND = 'REFUND';
    const REFUND_FAILED = 'REFUND_FAILED';
    const REFUNDED_REVERSED = 'REFUNDED_REVERSED';
    const CANCELLATION = 'CANCELLATION';

    /**
     * Constructor
     *
     * @param Context               $context
     * @param Registry              $registry
     * @param AbstractResource|null $resource
     * @param AbstractDb|null       $resourceCollection
     * @param array                 $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        AbstractResource $resource = null,
        AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * Initialize Adyen webhook resource model
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(AdyenWebhookResourceModel::class);
    }

    /**
     * Get webhook id
     *
     * @return integer
     */
    public function getWebhookId()
    {
        return $this->getData(self::WEBHOOK_ID);
    }

    /**
     * Set webhook id
     *
     * @param  integer $webhookId
     * @return $this
     */
    public function setWebhookId($webhookId)
    {
        return $this->setData(self::WEBHOOK_ID, $webhookId);
    }

    /**
     * Check if webhook is in production environment
     *
     * @return boolean
     */
    public function getIsProduction()
    {
        return $this->getData(self::IS_PRODUCTION);
    }

    /**
     * Set if webhook is in production environment
     *
     * @param  boolean $isProduction
     * @return $this
     */
    public function setIsProduction($isProduction)
    {
        return $this->setData(self::IS_PRODUCTION, $isProduction);
    }

    /**
     * Get psp reference
     *
     * @return string
     */
    public function getPspReference()
    {
        return $this->getData(self::PSP_REFERENCE);
    }

    /**
     * Set psp reference
     *
     * @param  string $pspReference
     * @return $this
     */
    public function setPspReference($pspReference)
    {
        return $this->setData(self::PSP_REFERENCE, $pspReference);
    }

    /**
     * Get original reference
     *
     * @return string
     */
    public function getOriginalReference()
    {
        return $this->getData(self::ORIGINAL_REFERENCE);
    }

    /**
     * Set original reference
     *
     * @param  string $originalReference
     * @return $this
     */
    public function setOriginalReference($originalReference)
    {
        return $this->setData(self::ORIGINAL_REFERENCE, $originalReference);
    }

    /**
     * Get merchant reference
     *
     * @return string
     */
    public function getMerchantReference()
    {
        return $this->getData(self::MERCHANT_REFERENCE);
    }

    /**
     * Set merchant reference
     *
     * @param  string $merchantReference
     * @return $this
     */
    public function setMerchantReference($merchantReference)
    {
        return $this->setData(self::MERCHANT_REFERENCE, $merchantReference);
    }

    /**
     * Get event code
     *
     * @return string
     */
    public function getEventCode()
    {
        return $this->getData(self::EVENT_CODE);
    }

    /**
     * Set event code
     *
     * @param  string $eventCode
     * @return $this
     */
    public function setEventCode($eventCode)
    {
        return $this->setData(self::EVENT_CODE, $eventCode);
    }

    /**
     * Get whether transaction succeeded or not
     *
     * @return string
     */
    public function getSuccess()
    {
        return $this->getData(self::SUCCESS);
    }

    /**
     * Set whether transaction succeeded or not
     *
     * @param  string $success
     * @return $this
     */
    public function setSuccess($success)
    {
        return $this->setData(self::SUCCESS, $success);
    }

    /**
     * Get payment method
     *
     * @return string
     */
    public function getPaymentMethod()
    {
        return $this->getData(self::PAYMENT_METHOD);
    }

    /**
     * Set payment method
     *
     * @param  string $paymentMethod
     * @return $this
     */
    public function setPaymentMethod($paymentMethod)
    {
        return $this->setData(self::PAYMENT_METHOD, $paymentMethod);
    }

    /**
     * Get amount
     *
     * @return string
     */
    public function getAmountValue()
    {
        return $this->getData(self::AMOUNT_VALUE);
    }

    /**
     * Set amount
     *
     * @param  string $amountValue
     * @return $this
     */
    public function setAmountValue($amountValue)
    {
        return $this->setData(self::AMOUNT_VALUE, $amountValue);
    }

    /**
     * Get currency
     *
     * @return string
     */
    public function getAmountCurrency()
    {
        return $this->getData(self::AMOUNT_CURRENCY);
    }

    /**
     * Set currency
     *
     * @param  string $amountCurrency
     * @return $this
     */
    public function setAmountCurrency($amountCurrency)
    {
        return $this->setData(self::AMOUNT_CURRENCY, $amountCurrency);
    }

    /**
     * Get reason
     *
     * @return string
     */
    public function getReason()
    {
        return $this->getData(self::REASON);
    }

    /**
     * Set reason
     *
     * @param  string $reason
     * @return $this
     */
    public function setReason($reason)
    {
        return $this->setData(self::REASON, $reason);
    }

    /**
     * Get additional data
     *
     * @return string
     */
    public function getAdditionalData()
    {
        return $this->getData(self::ADDITIONAL_DATA);
    }

    /**
     * Set additional data
     *
     * @param  string $additionalData
     * @return $this
     */
    public function setAdditionalData($additionalData)
    {
        return $this->setData(self::ADDITIONAL_DATA, $additionalData);
    }

    /**
     * Get created time
     *
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created time
     *
     * @param  string $createdAt
     * @return $this
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * Get updated time
     *
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * Set updated time
     *
     * @param  string $updatedAt
     * @return $this
     */
    public function setUpdatedAt($updatedAt)
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * Check whether webhook has been processed or not
     *
     * @return boolean
     */
    public function getProcessed()
    {
        return $this->getData(self::PROCESSED);
    }

    /**
     * Set whether webhook has been processed or not
     *
     * @param  boolean $processed
     * @return $this
     */
    public function setProcessed($processed)
    {
        return $this->setData(self::PROCESSED, $processed);
    }

    /**
     * Check whether webhook exists in database
     *
     * @param  string $pspReference
     * @param  string $eventCode
     * @param  string $success
     * @param  string $originalReference
     * @return boolean
     */
    public function doesExist($pspReference, $eventCode, $success, $originalReference)
    {
        $result = $this->getResource()->getWebhook($pspReference, $eventCode, $success, $originalReference);
        return (empty($result)) ? false : true;
    }
}
