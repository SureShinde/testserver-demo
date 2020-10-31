<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model;

use Magento\Checkout\Model\Session;
use Magento\Framework\Exception\LocalizedException;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Model\Checkout\Helper\OrderPlace;
use UnboundCommerce\GooglePay\Api\OrderManagementInterface;
use UnboundCommerce\GooglePay\Model\Checkout\Helper\QuoteUpdater;
use UnboundCommerce\GooglePay\Model\Checkout\Helper\AddressHelper;

/**
 * Class OrderManagement
 */
class OrderManagement implements OrderManagementInterface
{

    const PAYMENT_TOKEN = 'paymentToken';
    const PAYMENT_DATA = 'paymentData';

    /**
     * Quote repository.
     *
     * @var \Magento\Quote\Api\CartRepositoryInterface
     */
    protected $cartRepository;

    /**
     * @var OrderPlace
     */
    protected $orderPlace;

    /**
     * @var QuoteUpdater
     */
    protected $quoteUpdater;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Session
     */
    protected $session;

    /**
     * @var AddressHelper
     */
    protected $addressHelper;

    /**
     * @param \Magento\Quote\Api\CartRepositoryInterface $cartRepository
     * @param QuoteUpdater                               $quoteUpdater
     * @param OrderPlace                                 $orderPlace
     * @param Logger                                     $logger
     * @param Session                                    $session
     * @param AddressHelper                              $addressHelper
     */
    public function __construct(
        \Magento\Quote\Api\CartRepositoryInterface $cartRepository,
        QuoteUpdater $quoteUpdater,
        OrderPlace $orderPlace,
        Logger $logger,
        Session $session,
        AddressHelper $addressHelper
    ) {
        $this->cartRepository = $cartRepository;
        $this->orderPlace = $orderPlace;
        $this->quoteUpdater = $quoteUpdater;
        $this->logger = $logger;
        $this->session = $session;
        $this->addressHelper = $addressHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function placeOrder($cartId, $paymentData)
    {
        $result = [];
        try {
            /**
 * @var \Magento\Quote\Model\Quote $quote 
*/
            $quote = $this->cartRepository->get($cartId);
            if ($this->isPaymentDataValid($paymentData)) {
                $this->quoteUpdater->execute($quote, $paymentData);
            } elseif (!$quote->getPayment()->getAdditionalInformation(self::PAYMENT_TOKEN) && !$quote->getPayment()->getAdditionalInformation(self::PAYMENT_DATA)) {
                throw new LocalizedException(__('Checkout failed to initialize. Verify and try again.'));
            }

            $this->orderPlace->execute($quote);
            $order = $this->session->getLastRealOrder();

            if ($order->getPayment()) {
                $payment = $order->getPayment();
                $threeDS = $payment->getAdditionalInformation('threeDSRedirect') ? $payment->getAdditionalInformation('threeDSRedirect') : false;
                if ($threeDS) {
                    throw new LocalizedException(__('Payment requires 3DS which is currently not supported.'));
                }
            }

            $data['transactionState'] = 'SUCCESS';
        } catch (\Exception $e) {
            $this->logger->addError($e->getMessage());
            $data['transactionState'] = 'ERROR';
            $data['error'] = [];
            $data['error']['reason'] = 'PAYMENT_DATA_INVALID';
            $data['error']['message'] = 'Unable to process payment with provided credentials';
            $data['error']['intent'] = 'PAYMENT_AUTHORIZATION';
        }

        $result['paymentAuthorizationResult'] = $data;
        return $result;
    }

    /**
     * @param  \UnboundCommerce\GooglePay\Api\Data\PaymentDataInterface $paymentData
     * @return boolean
     */
    protected function isPaymentDataValid($paymentData)
    {
        if (!empty($paymentData) && !empty($paymentData->getPaymentMethodData())) {
            return true;
        }
        return false;
    }
}
