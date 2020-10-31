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

class RefundHandler implements HandlerInterface
{
    const TRANSACTION_ID = 'transactionId';

    const TRANSACTION_STATUS = 'status';

    const COMMENT = 'transactionComment';

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor for refund response handler
     *
     * @param Logger $logger
     */
    public function __construct(
        Logger $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Handles refund response
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
            $payment->getCreditmemo()->setState(\Magento\Sales\Model\Order\Creditmemo::STATE_OPEN);
        } else {
            $payment->setIsTransactionClosed(true);
            $closeParentTransaction = $payment->getCreditmemo()->getInvoice()->canRefund() ? false : true;
            $payment->setShouldCloseParentTransaction($closeParentTransaction);
        }

        $this->logger->addInfo(Status::STATUS_COMMENT_MAPPER[$transactionStatus] . " Transaction ID: " . $response[self::TRANSACTION_ID]);

        $payment->setAdditionalInformation(self::TRANSACTION_STATUS, $transactionStatus);

        if (isset($response[self::COMMENT])) {
            $payment->addTransactionCommentsToOrder($response[self::TRANSACTION_ID], $response[self::COMMENT]);
        }


        //        $payment->getOrder()->setState($transactionStatus);
        //        if($transactionStatus == 'canceled' || $transactionStatus == 'closed'){
        //            $payment->setIsTransactionClosed(true);
        //        } else {
        //            $payment->setIsTransactionClosed(false);
        //        }
        //        $payment->setIsTransactionPending(true);
        //        $payment->setIsTransactionClosed(true);
        //        $payment->setShouldCloseParentTransaction(true);

        //        if($payment->getCreditmemo()->getInvoice()->canRefund()){
        //            $payment->setShouldCloseParentTransaction(false);
        //        } else{
        //            $payment->setShouldCloseParentTransaction(true);
        //        }

        //        if(isset($response['errorMessage'])){
        //            $payment->addTransactionCommentsToOrder($payment->getTransactionId(), $response['errorMessage']);
        //            throw new \RuntimeException($response['errorMessage']);
        //        }
        //
        //        if(isset($response['transactionInfo'])){
        //            foreach($response['transactionInfo'] as $key => $value){
        //                $payment->setAdditionalInformation($key, $value);
        //            }
        //        }
        //
        //        if(isset($response[self::COMMENT])){
        //            $payment->addTransactionCommentsToOrder($payment->getTransactionId(), $response[self::COMMENT]);
        //        }
    }
}
