<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Cron;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction\Repository as TransactionRepository;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\OrderRepository;
use UnboundCommerce\GooglePay\Gateway\Config\Config;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Model\PaymentGateway\AdyenWebhook;
use UnboundCommerce\GooglePay\Model\ResourceModel\AdyenWebhook\CollectionFactory as AdyenWebhookFactory;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Adyen Cron
 */
class Adyen
{
    /**
     * @var AdyenWebhookFactory
     */
    protected $adyenWebhookFactory;

    /**
     * @var OrderFactory
     */
    protected $orderFactory;

    /**
     * @var Order
     */
    protected $order;

    /**
     * @var string
     */
    protected $pspReference;

    /**
     * @var string
     */
    protected $originalReference;

    /**
     * @var string
     */
    protected $merchantReference;

    /**
     * @var string
     */
    protected $eventCode;

    /**
     * @var boolean
     */
    protected $success;

    /**
     * @var string
     */
    protected $paymentMethod;

    /**
     * @var string
     */
    protected $reason;

    /**
     * @var string
     */
    protected $value;

    /**
     * @var string
     */
    protected $currency;

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
     * @var TransactionRepository
     */
    private $transactionRepository;

    /**
     * Constructor
     *
     * @param AdyenWebhookFactory   $adyenWebhookFactory
     * @param OrderFactory          $orderFactory
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepository       $orderRepository
     * @param TransactionRepository $transactionRepository
     * @param OrderUpdater          $orderUpdater
     * @param Logger                $logger
     */
    public function __construct(
        AdyenWebhookFactory $adyenWebhookFactory,
        OrderFactory $orderFactory,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepository $orderRepository,
        TransactionRepository $transactionRepository,
        OrderUpdater $orderUpdater,
        Logger $logger
    ) {
        $this->adyenWebhookFactory = $adyenWebhookFactory;
        $this->orderFactory = $orderFactory;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->transactionRepository = $transactionRepository;
        $this->orderUpdater = $orderUpdater;
        $this->logger = $logger;
    }

    /**
     * @return void
     */
    public function execute()
    {
        try {
            $this->logger->addCron("Started Adyen cron execution");
            $this->order = null;

            $dateStart = new \DateTime();
            $dateStart->modify('-3 days');
            $dateEnd = new \DateTime();
            $dateEnd->modify('-2 minute');
            $dateRange = ['from' => $dateStart, 'to' => $dateEnd, 'datetime' => true];
            $webhooks = $this->adyenWebhookFactory->create();
            $webhooks->addFieldToFilter('processed', 0);
            $webhooks->addFieldToFilter('created_at', $dateRange);
            $total = count($webhooks);

            $this->logger->addCron("Total webhooks: " . $total);
            $count = 0;

            foreach ($webhooks as $webhook) {
                if (!$webhook instanceof AdyenWebhook) {
                    continue;
                }

                $this->declareVariables($webhook);

                $incrementId = $this->merchantReference;

                $orderSearchCriteria = $this->searchCriteriaBuilder
                    ->addFilter('increment_id', $incrementId, 'eq')
                    ->create();

                $orderList = $this->orderRepository->getList($orderSearchCriteria)->getItems();
                $this->order = reset($orderList);

                if (!$this->order || $this->order->getPayment()->getMethod() !== Config::CODE) {
                    $this->logger->addCron("Deleting webhook as order is not present in database: " . $incrementId);
                    $webhook->delete();
                    continue;
                }

                $this->logger->addCron("Processing Adyen webhook: " . $webhook->getWebhookId());

                $processed = $this->processWebhook();

                if ($processed) {
                    $webhook->setProcessed(1);
                    $now = new \DateTime();
                    $webhook->setUpdatedAt($now);
                    $webhook->save();
                    $this->logger->addCron("Processed Adyen webhook: " . $webhook->getWebhookId());
                    ++$count;
                }
            }

            if ($count > 0) {
                $this->logger->addCron("Number of Adyen webhooks updated: " . $count);
            }
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
        }
    }

    /**
     * Deleting webhooks older than a week
     */
    public function deleteOldWebhooks()
    {
        try {
            $this->logger->addCron("Deleting Adyen webhooks older than a week");
            $dateStart = new \DateTime();
            $dateStart->modify('-7 days');
            $webhooks = $this->adyenWebhookFactory->create();
            $webhooks->addFieldToFilter('created_at', $dateStart, 'lteq');
            foreach ($webhooks as $webhook) {
                $webhook->delete();
            }
            $this->logger->addCron("Number of Adyen webhooks deleted: " . count($webhooks));
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
        }
    }

    /**
     * Declare Adyen webhook variables
     *
     * @param  AdyenWebhook $webhook
     * @return void
     */
    protected function declareVariables($webhook)
    {
        //  declare the common parameters
        $this->pspReference = $webhook->getPspreference();
        $this->originalReference = $webhook->getOriginalReference();
        $this->merchantReference = $webhook->getMerchantReference();
        $this->eventCode = $webhook->getEventCode();
        $success = $webhook->getSuccess();
        if (in_array($success, ['0', 'false', '', false])) {
            $this->success = false;
        } else {
            $this->success = true;
        }
        $this->paymentMethod = $webhook->getPaymentMethod();
        $this->reason = $webhook->getReason();
        $this->value = $webhook->getAmountValue();
        $this->currency = $webhook->getAmountCurrency();
    }

    /**
     * Get comment for Adyen webhook
     *
     * @param  String|null $statusComment
     * @return array
     */
    protected function getCommentForWebhook($statusComment)
    {
        $comment = $statusComment ? "$statusComment <br />" : '';
        $successResult = $this->success ? 'true' : 'false';
        $success = (!empty($this->reason)) ? "$successResult <br />reason: $this->reason" : $successResult;
        $comment = $comment . "Adyen Notification: <br /> event code: $this->eventCode <br /> success: $success";
        return [$comment];
    }

    /**
     * Process Adyen webhook
     *
     * @return boolean
     */
    protected function processWebhook()
    {
        $status = null;
        $comment = null;
        switch ($this->eventCode) {
        case AdyenWebhook::AUTHORISATION:
            $status = $this->getStatusForAuth($this->success);
            break;
        case AdyenWebhook::CAPTURE:
            $status = $this->success ? Status::CAPTURE_SUCCEEDED : Status::CAPTURE_FAILED;
            break;
        case AdyenWebhook::CAPTURE_FAILED:
            $status = Status::CAPTURE_FAILED;
            $comment = "The capture has failed due to a technical issue. We will fix the issue if possible, and resubmit the capture request.";
            break;
        case AdyenWebhook::CANCELLATION:
            $status = $this->success ? Status::VOID_SUCCEEDED : Status::VOID_FAILED;
            break;
        case AdyenWebhook::REFUND_FAILED:
            $status = Status::REFUND_FAILED;
            $comment = "The refund has failed due to a technical issue. You should retry the refund.";
            break;
        case AdyenWebhook::REFUNDED_REVERSED:
            $status = Status::REFUND_FAILED;
            $comment = "The refund has failed as we have received the refunded amount - This can happen if the card is closed. ";
            break;
        case AdyenWebhook::REFUND:
            $status = $this->success ? Status::REFUND_SUCCEEDED : Status::REFUND_FAILED;
            break;
        case AdyenWebhook::PENDING:
            $status = $this->getStatusForPending();
            break;
        default:
            $this->logger->addCron("This webhook event is not supported so will be ignored: " . $this->eventCode);
            break;
        }
        if (!$comment && $status) {
            $comment = Status::STATUS_COMMENT_MAPPER[$status];
        }

        $commentList = $this->getCommentForWebhook($comment);

        if ($status || !empty($commentList)) {
            try {
                return $this->orderUpdater->execute($this->order, $status, $commentList, $this->pspReference);
            } catch (\Exception $e) {
                $this->logger->addCron($e->getMessage());
            }
        }
        return false;
    }

    /**
     * Get status for authorisation/sale transactions
     *
     * @param  boolean|null $success
     * @return string|null
     */
    protected function getStatusForAuth($success = false)
    {
        $orderStatus = $this->orderUpdater->getCurrentStatus($this->order);

        if (Status::isAuthTransaction($orderStatus)) {
            $authStatus = $success ? Status::AUTH_SUCCEEDED : Status::AUTH_FAILED;
            return $authStatus;
        } elseif (Status::isSaleTransaction($orderStatus)) {
            $saleStatus = $success ? Status::SALE_SUCCEEDED : Status::SALE_FAILED;
            return $saleStatus;
        }
        if (!$orderStatus) {
            $this->logger->addCron("Unable to read current order payment status");
        } else {
            $this->logger->addCron("Invalid order payment status: " . $orderStatus);
        }
        return null;
    }

    /**
     * Get status for pending transactions
     *
     * @return string|null
     */
    protected function getStatusForPending()
    {
        $orderStatus = $this->orderUpdater->getCurrentStatus($this->order);

        if (Status::isAuthTransaction($orderStatus)) {
            return Status::AUTH_PENDING;
        } elseif (Status::isSaleTransaction($orderStatus)) {
            return Status::SALE_PENDING;
        } elseif (Status::isCaptureTransaction($orderStatus)) {
            return Status::CAPTURE_PENDING;
        } elseif (Status::isRefundTransaction($orderStatus)) {
            return Status::REFUND_PENDING;
        } elseif (Status::isVoidTransaction($orderStatus)) {
            return Status::VOID_PENDING;
        }
        if (!$orderStatus) {
            $this->logger->addCron("Unable to read current order payment status");
        } else {
            $this->logger->addCron("Invalid order payment status: " . $orderStatus);
        }
        return null;
    }
}
