<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model;

use Magento\Framework\Model\AbstractExtensibleModel;
use UnboundCommerce\GooglePay\Api\Data\IntermediatePaymentDataInterface;

/**
 * Class IntermediatePaymentData
 */
class IntermediatePaymentData extends AbstractExtensibleModel implements IntermediatePaymentDataInterface
{
    /**
     * {@inheritdoc}
     */
    public function getCallbackTrigger()
    {
        return $this->getData(self::CALLBACK_TRIGGER);
    }

    /**
     * {@inheritdoc}
     */
    public function setCallbackTrigger($callbackTrigger)
    {
        return $this->setData(self::CALLBACK_TRIGGER, $callbackTrigger);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalsInformation()
    {
        return $this->getData(self::TOTALS_INFORMATION);
    }

    /**
     * {@inheritdoc}
     */
    public function setTotalsInformation($totalsInformation)
    {
        return $this->setData(self::TOTALS_INFORMATION, $totalsInformation);
    }
}
