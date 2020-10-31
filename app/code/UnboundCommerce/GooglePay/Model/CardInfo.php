<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use UnboundCommerce\GooglePay\Api\Data\CardInfoInterface;

/**
 * Class CardInfo
 */
class CardInfo extends AbstractExtensibleModel implements CardInfoInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCardDetails()
    {
        return $this->getData(self::CARD_DETAILS);
    }

    /**
     * {@inheritdoc}
     */
    public function setCardDetails($cardDetails)
    {
        return $this->setData(self::CARD_DETAILS, $cardDetails);
    }

    /**
     * {@inheritdoc}
     */
    public function getCardNetwork()
    {
        return $this->getData(self::CARD_NETWORK);
    }

    /**
     * {@inheritdoc}
     */
    public function setCardNetwork($cardNetwork)
    {
        return $this->setData(self::CARD_NETWORK, $cardNetwork);
    }

    /**
     * {@inheritdoc}
     */
    public function getBillingAddress()
    {
        return $this->getData(self::BILLING_ADDRESS);
    }

    /**
     * {@inheritdoc}
     */
    public function setBillingAddress($billingAddress)
    {
        return $this->setData(self::BILLING_ADDRESS, $billingAddress);
    }
}
