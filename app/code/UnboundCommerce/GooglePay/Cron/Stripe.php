<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Cron;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoRepository;
use Magento\Sales\Model\OrderRepository;
use UnboundCommerce\GooglePay\Gateway\Config\Config;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Model\Adminhtml\Source\GatewayName;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;
use UnboundCommerce\GooglePay\Service\Gateway\stripe\Client as StripeClient;

/**
 * Stripe Cron
 */
class Stripe
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Order $order
     */
    protected $order;

    /**
     * @var Creditmemo
     */
    protected $creditmemo;

    /**
     * @var StripeClient
     */
    protected $stripeClient;

    /**
     * @var OrderUpdater
     */
    protected $orderUpdater;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var SearchCriteriaBuilder
     */
    private $searchCriteriaBuilder;

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var CreditmemoRepository
     */
    private $creditmemoRepository;

    /**
     * Constructor
     *
     * @param Config                $config
     * @param StripeClient          $stripeClient
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepository       $orderRepository
     * @param OrderUpdater          $orderUpdater
     * @param CreditmemoRepository  $creditmemoRepository
     * @param Logger                $logger
     */
    public function __construct(
        Config $config,
        StripeClient $stripeClient,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepository $orderRepository,
        OrderUpdater $orderUpdater,
        CreditmemoRepository $creditmemoRepository,
        Logger $logger
    ) {
        $this->config = $config;
        $this->stripeClient = $stripeClient;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->orderUpdater = $orderUpdater;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->logger = $logger;
        $this->stripeClient->setLogger($logger);
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $count = 0;
            $this->logger->addCron("Started Stripe cron execution");
            $data = $this->config->getMerchantGatewayCredentials();
            $count += $this->updatePendingPayments($data);
            $count += $this->updatePendingRefunds($data);
            if ($count > 0) {
                $this->logger->addCron("Number of Stripe transactions updated: " . $count);
            }
        } catch (\Exception $e) {
            error_log(print_r($e, true));
        }
    }

    /**
     * Update pending payments
     *
     * @param  array $data
     * @return integer
     */
    public function updatePendingPayments($data)
    {
        $transactionsUpdated = 0;
        try {
            $dateStart = new \DateTime();
            $dateStart = $dateStart->modify('-3 days');
            $this->searchCriteriaBuilder->addFilter('status', 'payment_review', 'eq');
            $this->searchCriteriaBuilder->addFilter('updated_at', $dateStart, 'gteq');
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $orders = $this->orderRepository->getList($searchCriteria)->getItems();

            $this->logger->addCron("No. of pending payments: " . count($orders));

            foreach ($orders as $order) {
                $this->order = $order;
                $payment = $order->getPayment();
                if (!$payment || $payment->getMethod() !== Config::CODE) {
                    continue;
                }
                $additionalInfo = $payment->getAdditionalInformation();
                $gatewayId = $additionalInfo['gatewayId'] ? $additionalInfo['gatewayId'] : null;
                $environment = $additionalInfo['environment'] ? $additionalInfo['environment'] : null;
                if ($additionalInfo && isset($additionalInfo['originalReference']) && $gatewayId == GatewayName::PROCESSOR_STRIPE && $environment == $this->config->getEnvironment()) {
                    $data['transactionId'] = $additionalInfo['originalReference'];

                    $result = $this->stripeClient->retrieveTransaction($data, false);
                    if (!$result['isValid']) {
                        continue;
                    }
                    $processed = $this->processPayment($result['body']);

                    if ($processed) {
                        ++$transactionsUpdated;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
        }
        return $transactionsUpdated;
    }

    /**
     * Update pending refunds
     *
     * @param  array $data
     * @return integer
     */
    public function updatePendingRefunds($data)
    {
        $transactionsUpdated = 0;
        try {
            $dateStart = new \DateTime();
            $dateStart = $dateStart->modify('-3 days');
            $this->searchCriteriaBuilder->addFilter('state', Creditmemo::STATE_OPEN, 'eq');
            $this->searchCriteriaBuilder->addFilter('updated_at', $dateStart, 'gteq');
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $creditmemos = $this->creditmemoRepository->getList($searchCriteria)->getItems();

            $this->logger->addCron("No. of pending refunds: " . count($creditmemos));

            foreach ($creditmemos as $creditmemo) {
                $this->creditmemo = $creditmemo;
                $this->order = $this->creditmemo->getOrder();

                $payment = $this->order->getPayment();
                if (!$payment || $payment->getMethod() !== Config::CODE) {
                    continue;
                }
                $additionalInfo = $payment->getAdditionalInformation();
                $gatewayId = $additionalInfo['gatewayId'] ? $additionalInfo['gatewayId'] : null;
                $environment = $additionalInfo['environment'] ? $additionalInfo['environment'] : null;
                $transactionId = $this->creditmemo->getTransactionId();

                if ($transactionId && $gatewayId == GatewayName::PROCESSOR_STRIPE && $environment == $this->config->getEnvironment()) {
                    $data['transactionId'] = $transactionId;

                    $result = $this->stripeClient->retrieveTransaction($data, true);
                    if (!$result['isValid']) {
                        continue;
                    }
                    $processed = $this->processRefund($result['body']);

                    if ($processed) {
                        ++$transactionsUpdated;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
        }
        return $transactionsUpdated;
    }

    //    public function updatePendingRefunds1($data)
    //    {
    //        try {
    //            // get order
    //            $dateStart = new \DateTime();
    //            $dateStart = $dateStart->modify('-10 hours');
    //            $this->searchCriteriaBuilder->addFilter('entity_id', '42', 'eq');
    //            $searchCriteria = $this->searchCriteriaBuilder->create();
    //            $creditmemos = $this->creditmemoRepository->getList($searchCriteria)->getItems();
    //            /* @var Creditmemo $creditmemo*/
    //            foreach ($creditmemos as $creditmemo) {
    //                $this->creditmemo = $creditmemo;
    //                $this->order = $this->creditmemo->getOrder();
    //                error_log("Increment Id:");
    //                error_log($this->creditmemo->getIncrementId());
    //                $this->creditmemo->setInvoiceId(-1);
    //                $this->creditmemo->unsetData('invoice');
    //                $this->creditmemo->save();
    //                $this->creditmemoRepository->save($this->creditmemo);
    //            }
    //        } catch (\Exception $e) {
    //            error_log(print_r($e, true));
    //        }
    //    }

    /**
     * Process payment
     *
     * @param  array $chargeObject
     * @return boolean
     */
    protected function processPayment($chargeObject)
    {
        if (!isset($chargeObject['status']) || !isset($chargeObject['id'])) {
            return false;
        }

        $transactionId = $chargeObject['id'];
        $orderStatus = $this->orderUpdater->getCurrentStatus($this->order);
        $action = null;
        $transactionStatus = null;

        $this->logger->addCron("Processing Stripe transaction: " . $transactionId);
        $this->logger->addCron("Status: " . $chargeObject['status']);
        $this->logger->addCron("Order: " . $this->order->getIncrementId());

        if (Status::isAuthTransaction($orderStatus)) {
            $action = 'authorise';
        } elseif (Status::isSaleTransaction($orderStatus)) {
            $action = 'sale';
        } elseif (Status::isCaptureTransaction($orderStatus)) {
            $action = 'capture';
        }

        if ($action) {
            $transactionStatus =  $this->stripeClient->getStatus($action, $chargeObject['status']);
        }

        if (!$transactionStatus) {
            $this->logger->addCron("This transaction status is not supported so will be ignored: " . $chargeObject['status']);
            return false;
        }

        if (Status::transactionPending($transactionStatus) || Status::transactionReceived($transactionStatus)) {
            return false;
        }

        $commentList = $this->getCommentForTransaction($chargeObject, $transactionStatus);
        try {
            $processed = $this->orderUpdater->execute($this->order, $transactionStatus, $commentList, $transactionId);
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
            return false;
        }

        if ($processed) {
            $this->logger->addCron("Processed Stripe transaction: " . $transactionId);
        }

        return $processed;
    }

    /**
     * Process refund
     *
     * @param  array $refundObject
     * @return boolean
     */
    protected function processRefund($refundObject)
    {
        if (!isset($refundObject['status'])) {
            return false;
        }

        $transactionId = $this->creditmemo->getTransactionId();
        $transactionStatus =  $this->stripeClient->getStatus('refund', $refundObject['status']);

        $this->logger->addCron("Processing Stripe transaction: " . $transactionId);
        $this->logger->addCron("Status: " . $refundObject['status']);
        $this->logger->addCron("Order: " . $this->order->getIncrementId());

        if (!$transactionStatus) {
            $this->logger->addCron("This transaction status is not supported so will be ignored: " . $refundObject['status']);
            return false;
        }

        $commentList = $this->getCommentForTransaction($refundObject, $transactionStatus);
        try {
            $processed = $this->orderUpdater->execute($this->order, $transactionStatus, $commentList, $transactionId, $this->creditmemo);
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
            return false;
        }

        if ($processed) {
            $this->logger->addCron("Processed Stripe transaction: " . $transactionId);
        }

        return $processed;
    }

    /**
     * Get comment for charge/refund object
     *
     * @param  array  $transaction
     * @param  string $status
     * @return array
     */
    protected function getCommentForTransaction($transaction, $status)
    {
        $commentList = [];
        $comment = Status::STATUS_COMMENT_MAPPER[$status];
        if (Status::transactionFailed($status)) {
            if (isset($object['failure_code'])) {
                $failureCode = $transaction["failure_code"];
                $comment = $comment . "<br /> Failure code: $failureCode";
            }
            if (isset($transaction['failure_message'])) {
                $failureMessage = $transaction["failure_message"];
                $comment = $comment . "<br /> Failure message: $failureMessage";
            }
        }
        array_push($commentList, $comment);
        return $commentList;
    }
}
