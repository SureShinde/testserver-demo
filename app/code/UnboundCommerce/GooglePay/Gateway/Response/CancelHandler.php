<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

class CancelHandler implements HandlerInterface
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
     * Constructor for cancellation response handler
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Handles cancellation response
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

        $transactionStatus = $response[self::TRANSACTION_STATUS];

        $this->logger->addInfo(Status::STATUS_COMMENT_MAPPER[$transactionStatus] . " Transaction ID: " . $response[self::TRANSACTION_ID]);

        if (isset($response[self::TRANSACTION_INFO])) {
            foreach ($response[self::TRANSACTION_INFO] as $key => $value) {
                $payment->setAdditionalInformation($key, $value);
            }
        }

        $payment->setAdditionalInformation(self::TRANSACTION_STATUS, $transactionStatus);

        if (isset($response[self::COMMENT])) {
            $payment->addTransactionCommentsToOrder($response[self::TRANSACTION_ID], $response[self::COMMENT]);
        }

        $payment->setIsTransactionClosed(true);
    }
}
