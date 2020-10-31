<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model;

use Magento\Quote\Model\QuoteIdMaskFactory;
use UnboundCommerce\GooglePay\Api\GuestTransactionInformationManagementInterface;
use UnboundCommerce\GooglePay\Api\TransactionInformationManagementInterface;

/**
 * Class GuestTransactionInformationManagement
 */
class GuestTransactionInformationManagement implements GuestTransactionInformationManagementInterface
{
    /**
     * @var QuoteIdMaskFactory
     */
    protected $quoteIdMaskFactory;

    /**
     * @var TransactionInformationManagementInterface
     */
    protected $transactionInformationManagement;

    /**
     * @param              QuoteIdMaskFactory                        $quoteIdMaskFactory
     * @param              TransactionInformationManagementInterface $transactionInformationManagement
     * @codeCoverageIgnore
     */
    public function __construct(
        QuoteIdMaskFactory $quoteIdMaskFactory,
        TransactionInformationManagementInterface $transactionInformationManagement
    ) {
        $this->quoteIdMaskFactory = $quoteIdMaskFactory;
        $this->transactionInformationManagement = $transactionInformationManagement;
    }

    /**
     * {@inheritDoc}
     */
    public function calculate($cartId, $intermediatePaymentData)
    {
        /**
 * @var $quoteIdMask \Magento\Quote\Model\QuoteIdMask 
*/
        $quoteIdMask = $this->quoteIdMaskFactory->create()->load($cartId, 'masked_id');
        return $this->transactionInformationManagement->calculate(
            $quoteIdMask->getQuoteId(),
            $intermediatePaymentData
        );
    }
}
