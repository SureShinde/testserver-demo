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
use UnboundCommerce\GooglePay\Service\Gateway\cybersource\Client as CybersourceClient;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Cybersource Cron
 */
class Cybersource
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
     * @var CybersourceClient
     */
    protected $cybersourceClient;

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
     * @param CybersourceClient     $cybersourceClient
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderUpdater          $orderUpdater
     * @param OrderRepository       $orderRepository
     * @param CreditmemoRepository  $creditmemoRepository
     * @param Logger                $logger
     */
    public function __construct(
        Config $config,
        CybersourceClient $cybersourceClient,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderUpdater $orderUpdater,
        OrderRepository $orderRepository,
        CreditmemoRepository $creditmemoRepository,
        Logger $logger
    ) {
        $this->config = $config;
        $this->cybersourceClient = $cybersourceClient;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderUpdater = $orderUpdater;
        $this->orderRepository = $orderRepository;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->logger = $logger;
        $this->cybersourceClient->setLogger($logger);
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $this->logger->addCron("Started Cybersource cron execution");
            $data = $this->config->getMerchantGatewayCredentials();
            $count = 0;
            //            $count += $this->processPaymentBatchDetailReport($data);
            $count += $this->updatePendingPayments($data);
            $count += $this->updatePendingRefunds($data);
            $this->logger->addCron("Number of Cybersource transactions updated: " . $count);
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
        }
    }

    /**
     * Process Payment Batch Detail Report
     *
     * @param  array $data
     * @return integer
     */
    public function processPaymentBatchDetailReport($data)
    {
        $transactionsUpdated = 0;

        try {
            $data['date'] = new \DateTime();
            $data['date']->modify('-1 day');
            $result = $this->cybersourceClient->getPaymentBatchDetailReport($data);
            if (!$result['isValid']) {
                $this->logger->addCron('Unable to download Payment Batch Detail Report');
                return $transactionsUpdated;
            }
            $paymentBatchDetails = explode(PHP_EOL, $result['paymentBatchDetailReport']);

            $this->logger->addCron("No. of transactions in report: " . count($paymentBatchDetails)-2);

            array_shift($paymentBatchDetails);

            // retrieve headers
            $headers = explode(',', array_shift($paymentBatchDetails));

            $statusKey = array_search('Status', $headers);
            $transactionIdKey = array_search('RequestID', $headers);
            $orderIdKey = array_search('MerchantReferenceNumber', $headers);
            $transactionReferenceKey = array_search('TransactionReferenceNumber', $headers);

            if (!$statusKey || !$transactionIdKey || !$orderIdKey) {
                $this->logger->addCron("Invalid Cybersource Payment Batch Detail Report format");
                return $transactionsUpdated;
            }
            foreach ($paymentBatchDetails as $payment) {
                $transaction = [];
                $transaction['status'] = $payment[$statusKey];
                $transaction['transactionId'] = $payment[$transactionIdKey];
                $transaction['orderId'] = $payment[$orderIdKey];
                $transaction['transactionReference'] = $payment[$transactionReferenceKey];
                $this->creditmemo = null;

                $searchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('increment_id', $transaction['orderId'], 'eq')
                    ->create();

                $orderList = $this->orderRepository->getList($searchCriteria)->getItems();
                $this->order = reset($orderList);

                if (!$this->order) {
                    $this->logger->addCron("Order id " . $transaction['orderId'] . " not found");
                    continue;
                }

                if ($this->order->getPayment()->getMethod() !== Config::CODE) {
                    $this->logger->addCron("Order payment method not supported: " . $this->order->getPayment()->getMethod());
                    continue;
                }

                //                $processed = $this->processTransaction($transaction);
                //                if ($processed) {
                //                    $this->logger->addCron("Processed Cybersource transaction: " . $transaction['transactionId']);
                //                    ++$transactionsUpdated;
                //                }
            }
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
        }
        return $transactionsUpdated;
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
            // get order
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
                $gatewayId = $additionalInfo['gatewayId'] ?? null;
                $environment = $additionalInfo['environment'] ?? null;

                if ($additionalInfo && isset($additionalInfo['originalReference']) && $gatewayId == GatewayName::PROCESSOR_CYBERSOURCE && $environment == $this->config->getEnvironment()) {
                    $data['transactionId'] = $payment->getLastTransId();

                    $result = $this->cybersourceClient->retrieveTransaction($data);
                    if (!$result['isValid']) {
                        continue;
                    }

                    $processed = $this->processTransaction($result['transaction']);
                    if ($processed) {
                        $this->logger->addCron("Processed Cybersource transaction: " . $data['transactionId']);
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
            // get order
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
                $gatewayId = $additionalInfo['gatewayId'] ??  null;
                $environment = $additionalInfo['environment'] ?? null;
                $transactionId = $this->creditmemo->getTransactionId();

                if ($transactionId && $gatewayId == GatewayName::PROCESSOR_CYBERSOURCE && $environment == $this->config->getEnvironment()) {
                    $data['transactionId'] = $this->creditmemo->getTransactionId();

                    $result = $this->cybersourceClient->retrieveTransaction($data);
                    if (!$result["isValid"]) {
                        continue;
                    }

                    $processed = $this->processTransaction($result['transaction']);
                    if ($processed) {
                        $this->logger->addCron("Processed Cybersource transaction: " . $data['transactionId']);
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
     * Process transaction
     *
     * @param  \CyberSource\Model\TssV2TransactionsGet200Response $transaction
     * @return boolean
     */
    protected function processTransaction($transaction)
    {
        $orderStatus = $this->orderUpdater->getCurrentStatus($this->order);
        $action = null;
        $status = null;
        $creditmemo = null;
        $transactionStatus = null;
        $commentList = [];
        $applicationInfo = $transaction->getApplicationInformation();
        $id = $transaction->getId();

        if ($applicationInfo) {
            $status = $applicationInfo->getStatus();
        }

        if (!$status || !$id) {
            return false;
        }

        $this->logger->addCron("Processing Cybersource transaction: " . $id);
        $this->logger->addCron("Status: " . $status);
        $this->logger->addCron("Order: " . $this->order->getIncrementId());

        if (Status::isAuthTransaction($orderStatus)) {
            $action = 'authorise';
        } elseif (Status::isSaleTransaction($orderStatus)) {
            $action = 'sale';
        } elseif (Status::isCaptureTransaction($orderStatus)) {
            $action = 'capture';
        } elseif (Status::isRefundTransaction($orderStatus)) {
            $action = 'refund';
            $creditmemo = $this->creditmemo;
        }

        if ($action) {
            $transactionStatus =  $this->cybersourceClient->getStatus($action, $status);
        }

        if (!$transactionStatus) {
            $this->logger->addCron("This transaction status is not supported so will be ignored: " . $status);
            return false;
        }

        if (Status::transactionPending($transactionStatus) || Status::transactionReceived($transactionStatus)) {
            return false;
        }

        $comment = Status::STATUS_COMMENT_MAPPER[$transactionStatus];

        if (Status::transactionFailed($transactionStatus)) {
            $errorInformation = $transaction->getErrorInformation();
            if (isset($errorInformation)) {
                $errorReason = $errorInformation->getReason();
                $errorMessage = $errorInformation->getMessage();
                if ($errorReason) {
                    $comment = $comment . "<br /> Error Reason: $errorReason";
                }
                if ($errorMessage) {
                    $comment = $comment . "<br /> Error Message: $errorMessage";
                }
            }
        }

        array_push($commentList, $comment);

        try {
            $processed = $this->orderUpdater->execute($this->order, $transactionStatus, $commentList, $id, $creditmemo);
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
            return false;
        }
        return $processed;
    }
}
