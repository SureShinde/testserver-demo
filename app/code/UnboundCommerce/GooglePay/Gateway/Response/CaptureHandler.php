<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

class CaptureHandler implements HandlerInterface
{
    const TRANSACTION_ID = 'transactionId';

    const TRANSACTION_STATUS = 'status';

    const TRANSACTION_INFO = 'transactionInfo';

    const COMMENT = 'transactionComment';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * Constructor for capture response handler
     *
     * @param Logger                   $logger
     * @param OrderRepositoryInterface $orderRepository
     */
    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Logger $logger
    ) {
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    /**
     * Handles capture response
     *
     * @param  array $handlingSubject
     * @param  array $response
     * @return void
     * @throws \Exception
     */
    public function handle(array $handlingSubject, array $response)
    {
        if (!isset($handlingSubject['payment'])
            || !$handlingSubject['payment'] instanceof PaymentDataObjectInterface
        ) {
            throw new \InvalidArgumentException('Payment data object should be provided');
        }

        /**
 * @var PaymentDataObjectInterface $paymentDO 
*/
        $paymentDO = $handlingSubject['payment'];

        /**
 * @var $payment \Magento\Sales\Model\Order\Payment 
*/
        $payment = $paymentDO->getPayment();
        $payment->setTransactionId($response[self::TRANSACTION_ID]);

        if (isset($response[self::TRANSACTION_INFO])) {
            foreach ($response[self::TRANSACTION_INFO] as $key => $value) {
                $payment->setAdditionalInformation($key, $value);
            }
        }

        $transactionStatus = $response[self::TRANSACTION_STATUS];

        if (Status::transactionReceived($transactionStatus) || Status::transactionPending($transactionStatus)) {
            $payment->setIsTransactionPending(true);
            $payment->setIsTransactionClosed(false);
            $payment->setShouldCloseParentTransaction(false);
        } else {
            $payment->setIsTransactionClosed(true);
            $payment->setShouldCloseParentTransaction(true);
        }

        $this->logger->addInfo(Status::STATUS_COMMENT_MAPPER[$transactionStatus] . " Transaction ID: " . $response[self::TRANSACTION_ID]);

        $payment->setAdditionalInformation(self::TRANSACTION_STATUS, $transactionStatus);

        if (isset($response[self::COMMENT])) {
            $payment->addTransactionCommentsToOrder($response[self::TRANSACTION_ID], $response[self::COMMENT]);
        }
    }
}
