<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Api;

/**
 * Interface OrderManagementInterface
 *
 * @api
 */
interface OrderManagementInterface
{
    /**
     * Update quote and place order with the provided payment data
     *
     * @param  int                                                      $cartId
     * @param  \UnboundCommerce\GooglePay\Api\Data\PaymentDataInterface $paymentData
     * @return array
     */
    public function placeOrder($cartId, $paymentData);
}
