<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Api;

/**
 * Interface GuestOrderManagementInterface
 *
 * @api
 */
interface GuestOrderManagementInterface
{
    /**
     * Update quote and place order with the provided payment data
     *
     * @param  string                                                   $cartId
     * @param  \UnboundCommerce\GooglePay\Api\Data\PaymentDataInterface $paymentData
     * @return array
     */
    public function placeOrder($cartId, $paymentData);
}
