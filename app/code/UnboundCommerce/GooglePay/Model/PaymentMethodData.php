<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use UnboundCommerce\GooglePay\Api\Data\PaymentMethodDataInterface;

/**
 * Class PaymentMethodData
 */
class PaymentMethodData extends AbstractExtensibleModel implements PaymentMethodDataInterface
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
    public function getDescription()
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     */
    public function setDescription($description)
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo()
    {
        return $this->getData(self::INFO);
    }

    /**
     * {@inheritdoc}
     */
    public function setInfo($info)
    {
        return $this->setData(self::INFO, $info);
    }

    /**
     * {@inheritdoc}
     */
    public function getTokenizationData()
    {
        return $this->getData(self::TOKENIZATION_DATA);
    }

    /**
     * {@inheritdoc}
     */
    public function setTokenizationData($tokenizationData)
    {
        return $this->setData(self::TOKENIZATION_DATA, $tokenizationData);
    }
}
