<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Cron;

use Braintree\Transaction;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoRepository;
use Magento\Sales\Model\OrderRepository;
use UnboundCommerce\GooglePay\Gateway\Config\Config;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Model\Adminhtml\Source\GatewayName;
use UnboundCommerce\GooglePay\Service\Gateway\braintree\Client as BraintreeClient;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Braintree Cron
 */
class Braintree
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
     * @var BraintreeClient
     */
    protected $braintreeClient;

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
     * @param BraintreeClient       $braintreeClient
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepository       $orderRepository
     * @param OrderUpdater          $orderUpdater
     * @param CreditmemoRepository  $creditmemoRepository
     * @param Logger                $logger
     */
    public function __construct(
        Config $config,
        BraintreeClient $braintreeClient,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepository $orderRepository,
        OrderUpdater $orderUpdater,
        CreditmemoRepository $creditmemoRepository,
        Logger $logger
    ) {
        $this->config = $config;
        $this->braintreeClient = $braintreeClient;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->orderUpdater = $orderUpdater;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->logger = $logger;
        $this->braintreeClient->setLogger($logger);
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $count = 0;
            $this->logger->addCron("Started Braintree cron execution");
            $data = $this->config->getMerchantGatewayCredentials();
            $count += $this->updatePendingPayments($data);
            $count += $this->updatePendingRefunds($data);
            if ($count > 0) {
                $this->logger->addCron("Number of Braintree transactions updated: " . $count);
            }
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
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
            $dateStart = $dateStart->modify('-10 days');
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
                if ($additionalInfo && isset($additionalInfo['originalReference']) && $gatewayId == GatewayName::PROCESSOR_BRAINTREE && $environment == $this->config->getEnvironment()) {
                    $data['transactionId'] = $additionalInfo['originalReference'];
                    $result = $this->braintreeClient->retrieveTransaction($data);
                    if (!$result['isValid']) {
                        continue;
                    }
                    $processed = $this->processTransaction($result['transaction']);
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
                if ($transactionId && $gatewayId == GatewayName::PROCESSOR_BRAINTREE && $environment == $this->config->getEnvironment()) {
                    $data['transactionId'] = $transactionId;

                    $result = $this->braintreeClient->retrieveTransaction($data);
                    if (!$result["isValid"]) {
                        continue;
                    }

                    $processed = $this->processTransaction($result['transaction']);
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
     * Process transaction
     *
     * @param  Transaction $transaction
     * @return boolean
     */
    protected function processTransaction($transaction)
    {
        if (!$transaction->id || !$transaction->status) {
            return false;
        }

        $orderStatus = $this->orderUpdater->getCurrentStatus($this->order);
        $action = null;
        $transactionStatus = null;
        $creditmemo = null;
        $commentList = [];

        $this->logger->addCron("Processing Braintree transaction: " . $transaction->id);
        $this->logger->addCron("Status: " . $transaction->status);
        $this->logger->addCron("Order: " . $this->order->getIncrementId());

        if ($transaction->type == Transaction::SALE) {
            if (Status::isAuthTransaction($orderStatus)) {
                $action = 'authorise';
            } elseif (Status::isSaleTransaction($orderStatus)) {
                $action = 'sale';
            } elseif (Status::isCaptureTransaction($orderStatus)) {
                $action = 'capture';
            }
        } else {
            $action = 'refund';
            $creditmemo = $this->creditmemo;
        }

        if ($action) {
            $transactionStatus =  $this->braintreeClient->getStatus($action, $transaction->status);
        }

        if (!$transactionStatus) {
            $this->logger->addCron("This transaction status is not supported so will be ignored: " . $transaction->status);
            return false;
        }

        if (Status::transactionPending($transactionStatus) || Status::transactionReceived($transactionStatus)) {
            return false;
        }

        $comment = Status::STATUS_COMMENT_MAPPER[$transactionStatus];

        if (Status::transactionFailed($transactionStatus)) {
            if (!empty($transaction->processorResponseText)) {
                $comment = $comment . "<br /> Processor response: $transaction->processorResponseText.";
            }
            if (!empty($transaction->processorSettlementResponseText)) {
                $comment = $comment . "<br /> Processor settlement response: $transaction->processorSettlementResponseText.";
            }
            if (!empty($transaction->gatewayRejectionReason)) {
                $comment = $comment . "<br /> Gateway rejection reason: $transaction->gatewayRejectionReason.";
            }
        }

        array_push($commentList, $comment);

        try {
            $processed = $this->orderUpdater->execute($this->order, $transactionStatus, $commentList, $transaction->id, $creditmemo);
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
            return false;
        }
        if ($processed) {
            $this->logger->addCron("Processed Braintree transaction: " . $transaction->id);
        }

        return $processed;
    }
}
