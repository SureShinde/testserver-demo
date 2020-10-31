<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Cron;

use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderRepository;
use UnboundCommerce\GooglePay\Gateway\Config\Config;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Model\Adminhtml\Source\GatewayName;

/**
 * Class Cron
 */
class Cron
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
     * @var OrderUpdater
     */
    protected $orderUpdater;

    /**
     * @var Adyen
     */
    protected $adyen;

    /**
     * @var Braintree
     */
    protected $braintree;

    /**
     * @var Cybersource
     */
    protected $cybersource;

    /**
     * @var Stripe
     */
    protected $stripe;

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
     * @param Config                $config
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param OrderRepository       $orderRepository
     * @param OrderUpdater          $orderUpdater
     * @param Adyen                 $adyen
     * @param Braintree             $braintree
     * @param Cybersource           $cybersource
     * @param Stripe                $stripe
     * @param Logger                $logger
     */
    public function __construct(
        Config $config,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        OrderRepository $orderRepository,
        OrderUpdater $orderUpdater,
        Adyen $adyen,
        Braintree $braintree,
        Cybersource $cybersource,
        Stripe $stripe,
        Logger $logger
    ) {
        $this->config = $config;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->orderRepository = $orderRepository;
        $this->orderUpdater = $orderUpdater;
        $this->adyen = $adyen;
        $this->braintree = $braintree;
        $this->cybersource = $cybersource;
        $this->stripe = $stripe;
        $this->logger = $logger;
    }

    /**
     * Execute daily cron
     *
     * @return void
     */
    public function dailyCron()
    {
        $gateway = $this->config->getGateway();
        if ($gateway == GatewayName::PROCESSOR_CYBERSOURCE) {
            $this->cybersource->execute();
        }
        $this->handlePendingOrders($gateway);
    }

    /**
     * Handle old orders that are in pending state
     *
     * @param  string $gateway
     * @return void
     */
    public function handlePendingOrders($gateway)
    {
        $this->logger->addCron("Updating orders that are in pending state for more than 3 days");
        try {
            $from = new \DateTime();
            switch ($gateway) {
            case GatewayName::PROCESSOR_ADYEN:
                $this->adyen->deleteOldWebhooks();
                $from = $from->modify('-7 days');
                break;
            default:
                $from = $from->modify('-72 hours');
                break;
            }

            // get order
            $this->searchCriteriaBuilder->addFilter('status', 'payment_review', 'eq');
            $this->searchCriteriaBuilder->addFilter('updated_at', $from, 'lteq');
            $searchCriteria = $this->searchCriteriaBuilder->create();
            $orders = $this->orderRepository->getList($searchCriteria)->getItems();

            $ordersList = [];
            foreach ($orders as $order) {
                $payment = $order->getPayment();
                if (!$payment || $payment->getMethod() !== Config::CODE) {
                    continue;
                }
                array_push($ordersList, $order);
            }

            $this->logger->addCron("No. of pending orders: " . count($ordersList));

            $this->orderUpdater->closePendingTransactions($ordersList);
        } catch (\Exception $e) {
            $this->logger->addCron($e->getMessage());
        }
    }

    /**
     * Execute cron
     *
     * @return void
     */
    public function execute()
    {
        $gateway = $this->config->getGateway();
        switch ($gateway) {
        case GatewayName::PROCESSOR_ADYEN:
            $this->adyen->execute();
            break;
        case GatewayName::PROCESSOR_BRAINTREE:
            $this->braintree->execute();
            break;
        case GatewayName::PROCESSOR_STRIPE:
            $this->stripe->execute();
            break;
        }
    }
}
