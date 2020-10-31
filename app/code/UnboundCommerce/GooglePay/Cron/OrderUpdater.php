<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Cron;

use Magento\Framework\DB\Transaction;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\CreditmemoRepository;
use Magento\Sales\Model\Order\Invoice;
use UnboundCommerce\GooglePay\Gateway\Config\Config;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Class OrderUpdater
 */
class OrderUpdater
{
    const TRANSACTION_STATUS = 'status';

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Order $order
     */
    protected $order;

    /**
     * @var array $commentList
     */
    protected $commentList;

    /**
     * @var string $status
     */
    protected $status;

    /**
     * @var string $transactionId
     */
    protected $transactionId;

    /**
     * @var Creditmemo
     */
    protected $creditmemo;

    /**
     * @var Transaction
     */
    protected $transaction;

    /**
     * @var CreditmemoRepository
     */
    private $creditmemoRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param Config               $config
     * @param Transaction          $transaction
     * @param CreditmemoRepository $creditmemoRepository
     * @param Logger               $logger
     */
    public function __construct(
        Config $config,
        Transaction $transaction,
        CreditmemoRepository $creditmemoRepository,
        Logger $logger
    ) {
        $this->config = $config;
        $this->transaction = $transaction;
        $this->creditmemoRepository = $creditmemoRepository;
        $this->logger = $logger;
    }

    /**
     * Execute operation
     *
     * @param  Order           $order
     * @param  string          $status
     * @param  array           $commentList
     * @param  string          $transactionId
     * @param  Creditmemo|null $creditmemo
     * @return boolean
     */
    public function execute($order, $status, $commentList, $transactionId, $creditmemo = null)
    {
        $this->order = $order;
        $this->status = $status;
        $this->commentList = $commentList;
        $this->transactionId = $transactionId;
        $this->creditmemo = $creditmemo;
        if ($this->order->getPayment()->getMethod() !== Config::CODE) {
            return false;
        }
        $this->addCommentsToOrder();

        if (Status::isAuthTransaction($status)) {
            $this->handleAuthorise();
        } elseif (Status::isCaptureTransaction($status)) {
            $this->handleCapture();
        } elseif (Status::isSaleTransaction($status)) {
            $this->handleSale();
        } elseif (Status::isRefundTransaction($status)) {
            if (!$creditmemo) {
                $creditmemos = $this->order->getCreditmemosCollection();
                foreach ($creditmemos as $orderCreditmemo) {
                    if ($orderCreditmemo->getTransactionId() == $transactionId) {
                        $this->creditmemo = $orderCreditmemo;
                        break;
                    }
                }
            }
            if ($this->creditmemo) {
                try {
                    $this->handleRefund();
                } catch (\Exception $e) {
                    $this->logger->addCron($e->getMessage());
                    return false;
                }
            }
        }

        try {
            $date = new \DateTime();
            $this->order->setUpdatedAt($date);
            $this->order->save();
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Close pending transactions
     *
     * @param array $orders
     */
    public function closePendingTransactions($orders)
    {
        foreach ($orders as $order) {
            $this->order = $order;
            if ($this->order->getPayment()->getMethod() !== Config::CODE) {
                continue;
            }
            $orderStatus = $this->getCurrentStatus($this->order);

            if (Status::isAuthTransaction($orderStatus)) {
                $this->status = Status::AUTH_FAILED;
                $this->handleAuthorise();
                $comment = "Closing authorisation as we did not receive any update from the payment gateway";
            } elseif (Status::isCaptureTransaction($orderStatus)) {
                $this->status = Status::CAPTURE_FAILED;
                $this->handleCapture();
                $comment = "Closing invoice transaction as we did not receive any update from the payment gateway";
            } elseif (Status::isSaleTransaction($orderStatus)) {
                $this->status = Status::SALE_FAILED;
                $this->handleSale();
                $comment = "Closing payment transaction as we did not receive any update from the payment gateway";
            } else {
                continue;
            }
            $this->commentList = [$comment];
            $this->addCommentsToOrder();
            try {
                $this->order->save();
            } catch (\Exception $e) {
                $this->logger->addCron($e->getMessage());
            }
        }
    }

    /**
     * Checks whether comments should be added to order
     *
     * @return boolean
     */
    protected function shouldAddCommentsToOrder()
    {
        $addComments = false;
        $currentStatus = $this->getCurrentStatus($this->order);
        if (!Status::transactionSucceeded($this->status)) {
            $addComments = true;
        } elseif (Status::isAuthTransaction($currentStatus)) {
            if (Status::transactionPending($currentStatus) || Status::transactionReceived($currentStatus)) {
                $addComments = true;
            }
        }
        return $addComments;
    }

    /**
     * Add comments in comment list to order
     */
    protected function addCommentsToOrder()
    {
        $addComments = $this->shouldAddCommentsToOrder();
        foreach ($this->commentList as $comment) {
            if ($this->transactionId) {
                $comment = $comment . " Transaction ID: \"" . $this->transactionId . "\"";
            }
            if ($addComments) {
                $this->order->addCommentToStatusHistory($comment);
                $this->logger->addCron("Added comment to order status history: " . $comment);
            } else {
                $this->logger->addCron($comment);
            }
        }
    }

    /**
     * Get current payment status of order
     *
     * @param  Order $order
     * @return string
     */
    public function getCurrentStatus($order)
    {
        return $order->getPayment()->getAdditionalInformation(self::TRANSACTION_STATUS);
    }

    /**
     * Set current payment status of order
     *
     * @param Order  $order
     * @param string $status
     */
    public function setCurrentStatus($order, $status)
    {
        $order->getPayment()->setAdditionalInformation(self::TRANSACTION_STATUS, $status);
    }

    /**
     * Handle authorization
     *
     * @return void
     */
    public function handleAuthorise()
    {
        $currentStatus = $this->getCurrentStatus($this->order);
        if ((Status::transactionReceived($currentStatus) || Status::transactionPending($currentStatus)) && $this->order->isPaymentReview()) {
            if ($this->status === Status::AUTH_SUCCEEDED) {
                $this->order->setState(Order::STATE_PROCESSING)->setStatus(Order::STATE_PROCESSING);
                $this->setCurrentStatus($this->order, Status::AUTH_SUCCEEDED);
            } elseif ($this->status === Status::AUTH_FAILED) {
                $authTrans = $this->order->getPayment()->getAuthorizationTransaction();
                $authTrans->close();
                $this->order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
                $this->setCurrentStatus($this->order, Status::AUTH_FAILED);
            }
        }
    }

    /**
     * Handle capture
     *
     * @return void
     */
    public function handleCapture()
    {
        $currentStatus = $this->getCurrentStatus($this->order);
        if ((Status::transactionReceived($currentStatus) || Status::transactionPending($currentStatus)) && $this->order->isPaymentReview()) {
            if ($this->status === Status::CAPTURE_SUCCEEDED) {
                $authTrans = $this->order->getPayment()->getAuthorizationTransaction();
                $captureTransId = $this->order->getPayment()->getLastTransId();
                $captureTrans = $authTrans->getChildTransactions(null, $captureTransId);
                if ($captureTrans != null) {
                    $captureTrans->close();
                }
                $this->order->getPayment()->accept();
                $this->setCurrentStatus($this->order, Status::CAPTURE_SUCCEEDED);
            } elseif ($this->status === Status::CAPTURE_FAILED) {
                $captureTransId = $this->order->getPayment()->getLastTransId();
                $invoice = null;
                foreach ($this->order->getInvoiceCollection() as $invoice) {
                    if ($invoice->getTransactionId() == $captureTransId) {
                        $invoice->load($invoice->getId());
                        break;
                    }
                }
                if ($invoice instanceof Invoice) {
                    $invoice->cancel();
                    $this->order->addRelatedObject($invoice);
                }
                $this->order->setState(Order::STATE_PENDING_PAYMENT)->setStatus(Order::STATE_PENDING_PAYMENT);
                $this->setCurrentStatus($this->order, Status::CAPTURE_FAILED);
            }
        }
    }

    /**
     * Handle sale
     *
     * @return void
     */
    public function handleSale()
    {
        $currentStatus = $this->getCurrentStatus($this->order);
        if ((Status::transactionReceived($currentStatus) || Status::transactionPending($currentStatus)) && $this->order->isPaymentReview()) {
            if ($this->status === Status::SALE_SUCCEEDED) {
                $this->order->getPayment()->accept();
                $this->setCurrentStatus($this->order, Status::SALE_SUCCEEDED);
            } elseif ($this->status === Status::SALE_FAILED) {
                $this->order->getPayment()->deny();
                $this->setCurrentStatus($this->order, Status::SALE_FAILED);
            }
        }
    }

    /**
     * Handle refund
     *
     * @return void
     * @throws \Exception
     */
    public function handleRefund()
    {
        $updated = false;

        if ($this->creditmemo->canCancel()) {
            if ($this->status === Status::REFUND_SUCCEEDED) {
                $this->creditmemo->setState(Creditmemo::STATE_REFUNDED);
                $this->setCurrentStatus($this->order, Status::REFUND_SUCCEEDED);
                $updated = true;
            } elseif ($this->status === Status::REFUND_FAILED) {
                $this->creditmemo->setState(Creditmemo::STATE_CANCELED);
                $this->setCurrentStatus($this->order, Status::REFUND_FAILED);
                $updated = true;
            }
        }
        if ($updated) {
            $transaction = $this->transaction
                ->addObject($this->creditmemo)
                ->addObject($this->order);
            $transaction->save();
            $this->creditmemoRepository->save($this->creditmemo);
        }
    }
}
