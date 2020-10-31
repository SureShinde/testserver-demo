<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Api;

/**
 * Interface GuestTransactionInformationManagementInterface
 *
 * @api
 */
interface GuestTransactionInformationManagementInterface
{
    /**
     * Estimate shipping methods and calculate quote totals based on address.
     *
     * @param  string                                                               $cartId
     * @param  \UnboundCommerce\GooglePay\Api\Data\IntermediatePaymentDataInterface $intermediatePaymentData
     * @return array
     */
    public function calculate($cartId, $intermediatePaymentData);
}
