<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Api\Data;

/**
 * Interface IntermediatePaymentDataInterface
 *
 * @api
 */
interface IntermediatePaymentDataInterface
{
    const CALLBACK_TRIGGER = 'callback_trigger';
    const TOTALS_INFORMATION = 'totals_information';

    /**
     * Return the reason for which payment data callback was invoked
     *
     * @return string|null
     */
    public function getCallbackTrigger();

    /**
     * Set reason for which payment data callback was invoked
     *
     * @param  string $callbackTrigger
     * @return $this
     */
    public function setCallbackTrigger($callbackTrigger);
    
    /**
     * Return totals information
     *
     * @return \Magento\Checkout\Api\Data\TotalsInformationInterface
     */
    public function getTotalsInformation();

    /**
     * Set totals information
     *
     * @param  \Magento\Checkout\Api\Data\TotalsInformationInterface $totalsInformation
     * @return $this
     */
    public function setTotalsInformation($totalsInformation);
}
