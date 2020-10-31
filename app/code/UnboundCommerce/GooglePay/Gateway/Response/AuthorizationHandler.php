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

class AuthorizationHandler implements HandlerInterface
{
    const TRANSACTION_ID = 'transactionId';

    const TRANSACTION_STATUS = 'status';

    const TRANSACTION_INFO = 'transactionInfo';

    const PAYMENT_TOKEN = 'paymentToken';

    const PAYMENT_DATA = 'paymentData';

    const ORIGINAL_REFERENCE = 'originalReference';

    const THREE_DS_INFO = 'threeDSInfo';

    const THREE_DS_REDIRECT = 'threeDSRedirect';

    const COMMENT = 'transactionComment';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor for authorization response handler
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Handles authorization response
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

        if (Status::transactionReceived($transactionStatus) || Status::transactionPending($transactionStatus)) {
            $payment->setIsTransactionPending(true);
        }

        $this->logger->addInfo(Status::STATUS_COMMENT_MAPPER[$transactionStatus] . " Transaction ID: " . $response[self::TRANSACTION_ID]);

        if (isset($response[self::TRANSACTION_INFO])) {
            foreach ($response[self::TRANSACTION_INFO] as $key => $value) {
                $payment->setAdditionalInformation($key, $value);
            }
        }

        $payment->setAdditionalInformation(self::ORIGINAL_REFERENCE, $response[self::TRANSACTION_ID]);
        $payment->setAdditionalInformation(self::TRANSACTION_STATUS, $transactionStatus);

        if ($payment->getAdditionalInformation(self::PAYMENT_TOKEN) != null) {
            $payment->unsAdditionalInformation(self::PAYMENT_TOKEN);
        }
        if ($payment->getAdditionalInformation(self::PAYMENT_DATA) != null) {
            $payment->unsAdditionalInformation(self::PAYMENT_DATA);
        }

        if (isset($response[self::COMMENT])) {
            $payment->addTransactionCommentsToOrder($response[self::TRANSACTION_ID], $response[self::COMMENT]);
        }

        $payment->setIsTransactionClosed(false);
    }
}
