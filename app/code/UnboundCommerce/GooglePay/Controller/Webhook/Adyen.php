<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 **/

namespace UnboundCommerce\GooglePay\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\HTTP\Authentication;
use UnboundCommerce\GooglePay\Gateway\Config\Config;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Model\Adminhtml\Source\Environment;
use UnboundCommerce\GooglePay\Model\PaymentGateway\AdyenWebhook;
use UnboundCommerce\GooglePay\Model\PaymentGateway\AdyenWebhookFactory;
use Magento\Framework\Serialize\SerializerInterface;

/**
 *  Adyen webhook receiver
 */
class Adyen extends Action
{
    /**
     * @var AdyenWebhookFactory
     */
    protected $adyenWebhookFactory;

    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Authentication
     */
    protected $httpAuthentication;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor
     *
     * @param Context             $context
     * @param Authentication      $httpAuthentication
     * @param Config              $config
     * @param AdyenWebhookFactory $adyenWebhookFactory
     * @param Logger              $logger
     */
    public function __construct(
        Context $context,
        Authentication $httpAuthentication,
        Config $config,
        AdyenWebhookFactory $adyenWebhookFactory,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->adyenWebhookFactory = $adyenWebhookFactory;
        $this->httpAuthentication = $httpAuthentication;
        $this->config = $config;
        $this->logger = $logger;

        $request = $this->getRequest();
        if ($request instanceof HttpRequest && $request->isPost()) {
            $request->setParam('isAjax', true);
        }
    }

    /**
     * @throws LocalizedException
     */
    public function execute()
    {
        try {
            $this->logger->addWebhook('Received Adyen webhook');
            $notificationItems = json_decode(file_get_contents('php://input'), true);

            if (!$this->isRequestAuthorized()) {
                return;
            }
            if (!isset($notificationItems['live'])) {
                $this->setInvalidResponse();
                return;
            }

            if ($notificationItems['live'] == "true") {
                $isProduction = true;
            } else {
                $isProduction = false;
            }

            if (!$this->isValidEnvironment($isProduction)) {
                $this->logger->addWebhook('INVALID ENVIRONMENT: Magento and Adyen notification environment mismatch');
                throw new LocalizedException(__('Magento and Adyen notification environment mismatch'));
            }

            foreach ($notificationItems['notificationItems'] as $notificationItem) {
                $status = $this->saveWebhook($notificationItem['NotificationRequestItem'], $isProduction);

                if ($status != true) {
                    $this->setInvalidResponse();
                    return;
                }
            }
            $this->logger->addWebhook('Adyen webhook accepted');

            $this->getResponse()
                ->clearHeader('Content-Type')
                ->setHeader('Content-Type', 'text/html')
                ->setBody("[accepted]");
            return;
        } catch (\Exception $e) {
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @param  boolean $isProduction
     * @return boolean
     */
    protected function isValidEnvironment($isProduction)
    {
        $internalEnvironment = $this->config->getEnvironment();
        if (!$isProduction && $internalEnvironment == Environment::ENVIRONMENT_SANDBOX) {
            return true;
        } elseif ($isProduction && $internalEnvironment == Environment::ENVIRONMENT_PRODUCTION) {
            return true;
        }
        return false;
    }

    /**
     * @param  mixed   $notificationItem
     * @param  boolean $isProduction
     * @return boolean
     * @throws LocalizedException
     */
    protected function saveWebhook($notificationItem, $isProduction)
    {
        $this->logger->addWebhook(print_r($notificationItem, true));

        if ($this->doesExist($notificationItem)) {
            $this->logger->addWebhook('Webhook already exists in database');
            return true;
        }
        try {
            /**
 * @var AdyenWebhook $webhook
*/
            $webhook = $this->adyenWebhookFactory->create();

            if ($isProduction) {
                $webhook->setIsProduction(1);
            } else {
                $webhook->setIsProduction(0);
            }

            if (isset($notificationItem['pspReference'])) {
                $webhook->setPspreference($notificationItem['pspReference']);
            }
            if (isset($notificationItem['originalReference'])) {
                $webhook->setOriginalReference($notificationItem['originalReference']);
            }
            if (isset($notificationItem['merchantReference'])) {
                $webhook->setMerchantReference($notificationItem['merchantReference']);
            }
            if (isset($notificationItem['eventCode'])) {
                $webhook->setEventCode($notificationItem['eventCode']);
            }
            if (isset($notificationItem['success'])) {
                $webhook->setSuccess($notificationItem['success']);
            }
            if (isset($notificationItem['paymentMethod'])) {
                $webhook->setPaymentMethod($notificationItem['paymentMethod']);
            }
            if (isset($notificationItem['amount'])) {
                $webhook->setAmountValue($notificationItem['amount']['value']);
                $webhook->setAmountCurrency($notificationItem['amount']['currency']);
            }
            if (isset($notificationItem['reason'])) {
                $webhook->setReason($notificationItem['reason']);
            }
            if (isset($notificationItem['additionalData'])) {
                $webhook->setAdditionalData(SerializerInterface::serialize($notificationItem['additionalData']));
            }

            $date = new \DateTime();
            $webhook->setCreatedAt($date);
            $webhook->setUpdatedAt($date);

            $webhook->save();

            $this->logger->addWebhook('Saved Adyen webhook');
            return true;
        } catch (\Exception $e) {
            $this->logger->addWebhook($e->getMessage());
            throw new LocalizedException(__($e->getMessage()));
        }
    }

    /**
     * @return boolean
     */
    protected function isRequestAuthorized()
    {
        $webhookCredentials = $this->config->getAdyenWebhookCredentials();
        $internalUsername = $webhookCredentials['webhook_username'];
        $internalPassword = $webhookCredentials['webhook_password'];
        $request = $this->getRequest();
        if ($request instanceof HttpRequest) {
            $authHeader = $request->getHeader('Authorization');

            if (!$authHeader) {
                $this->logger->addWebhook('AUTHENTICATION FAILED: Basic Authentication not set');
                $this->setInvalidResponse();
                return false;
            }

            $encodedAuth = explode(" ", $authHeader);
            $decodedAuth = base64_decode($encodedAuth[1]);
            $decodedAuthList = explode(":", $decodedAuth);
            $username = $decodedAuthList[0];
            $password = $decodedAuthList[1];

            if (empty($username) || empty($password)) {
                $this->logger->addWebhook('AUTHENTICATION FAILED: Empty username or password');
                $this->setInvalidResponse();
                return false;
            }

            if (($internalUsername != $username) || ($internalPassword != $password)) {
                $this->logger->addWebhook('AUTHENTICATION FAILED: Invalid username/password');
                $this->setForbiddenResponse();
                return false;
            }
            return true;
        }

        $this->setInvalidResponse();
        return false;
    }

    /**
     * @param  array $notificationItem
     * @return boolean
     */
    protected function doesExist($notificationItem)
    {
        $pspReference = trim($notificationItem['pspReference']);
        $eventCode = trim($notificationItem['eventCode']);
        $success = trim($notificationItem['success']);
        $originalReference = isset($notificationItem['originalReference']) ? trim($notificationItem['originalReference']) : null;
        /**
 * @var AdyenWebhook $webhook
*/
        $webhook = $this->adyenWebhookFactory->create();
        return $webhook->doesExist($pspReference, $eventCode, $success, $originalReference);
    }

    /**
     * Return 401 invalid response
     */
    protected function setInvalidResponse()
    {
        $this->getResponse()->setHttpResponseCode(401);
    }

    /**
     * Return 403 forbidden response
     */
    protected function setForbiddenResponse()
    {
        $this->getResponse()->setHttpResponseCode(403);
    }
}
