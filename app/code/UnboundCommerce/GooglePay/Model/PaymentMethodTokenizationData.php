<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use UnboundCommerce\GooglePay\Api\Data\PaymentMethodTokenizationDataInterface;

/**
 * Class PaymentMethodTokenizationData
 */
class PaymentMethodTokenizationData extends AbstractExtensibleModel implements PaymentMethodTokenizationDataInterface
{
    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->getData(self::TYPE);
    }

    /**
     * {@inheritdoc}
     */
    public function setType($type)
    {
        return $this->setData(self::TYPE, $type);
    }

    /**
     * {@inheritdoc}
     */
    public function getToken()
    {
        return $this->getData(self::TOKEN);
    }

    /**
     * {@inheritdoc}
     */
    public function setToken($token)
    {
        return $this->setData(self::TOKEN, $token);
    }
}
