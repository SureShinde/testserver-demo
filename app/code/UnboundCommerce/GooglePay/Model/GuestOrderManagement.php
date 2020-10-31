<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model;

use Magento\Quote\Model\QuoteIdMaskFactory;
use UnboundCommerce\GooglePay\Api\OrderManagementInterface;
use UnboundCommerce\GooglePay\Api\GuestOrderManagementInterface;

/**
 * Class GuestOrderManagement
 */
class GuestOrderManagement implements GuestOrderManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var OrderManagementInterface
     */
    protected $orderManagement;

    /**
     * @param QuoteIdMaskFactory       $quoteIdMaskFactory
     * @param OrderManagementInterface $orderManagement
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        OrderManagementInterface $orderManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->orderManagement = $orderManagement;
    }

    /**
     * {@inheritDoc}
     */
    public function placeOrder($cartId, $paymentData)
    {
        /**
 * @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask 
*/
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->orderManagement->placeOrder(
            $quoteIdMask->getQuoteId(),
            $paymentData
        );
    }
}
