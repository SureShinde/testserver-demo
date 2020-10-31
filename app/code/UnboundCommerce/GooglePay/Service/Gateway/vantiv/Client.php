<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\vantiv;

use cnp\sdk\CnpOnlineRequest;
use cnp\sdk\UrlMapper;
use cnp\sdk\XmlParser;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\ClientInterface;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\RequestAdapter;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Class Vantiv Client
 */
class Client implements ClientInterface
{
    const GATEWAY_REQUIRED_PARAMS = ['gatewayCredentials.gateway_merchant_id', 'gatewayCredentials.environment', 'gatewayCredentials.username', 'gatewayCredentials.password', 'gatewayCredentials.report_group'];

    const AUTHORIZE_REQUEST_PARAMS = ['orderId', 'amount', 'paymentToken', 'currency'];

    const AUTHORIZE_REQUEST_MAPPER = [  'orderId' => 'orderId',
                                        'id' => 'id',
                                        'amount' => 'amount',
                                        'orderSource' => 'raw.androidpay',
                                        'paypage' => [
                                            'paypageRegistrationId' => 'paymentToken'
                                        ]
                                    ];

    const CAPTURE_REQUEST_PARAMS = ['originalReference', 'orderId'];

    const CAPTURE_REQUEST_MAPPER = ['cnpTxnId' => 'originalReference', 'id' => 'id'];

    const REFUND_REQUEST_PARAMS = ['originalReference', 'orderId', 'amount', 'currency'];

    const REFUND_REQUEST_MAPPER = ['cnpTxnId' => 'originalReference',
                                    'id' => 'id',
                                    'amount' => 'amount'
                                    ];

    const VOID_REQUEST_PARAMS = ['originalReference', 'orderId'];

    const VOID_REQUEST_MAPPER = ['cnpTxnId' => 'originalReference', 'id' => 'id'];

    const STATUS_MAPPER = ['authorise' => Status::AUTH_SUCCEEDED,
                            'capture' => Status::CAPTURE_SUCCEEDED,
                            'sale' => Status::SALE_SUCCEEDED,
                            'refund' => Status::REFUND_SUCCEEDED,
                            'void' => Status::VOID_SUCCEEDED,
                            ];

    const GATEWAY_PARAMS_MAPPER = [ 'url' => 'url',
                                    'user' => 'gatewayCredentials.username',
                                    'password' => 'gatewayCredentials.password',
                                    'merchantId' => 'gatewayCredentials.gateway_merchant_id',
                                    'reportGroup' => 'gatewayCredentials.report_group'
                                    ];

    const CONFIG_PARAMS = [
        "timeout" => "500",
        "proxy" => "",
        "cnp_requests_path" => "",
        "batch_requests_path" => "",
        "sftp_username" => "",
        "sftp_password" => "",
        "batch_url" => "",
        "tcp_port" => "",
        "tcp_ssl" => '1',
        "tcp_timeout" => "",
        "print_xml" => '0',
        "vantivPublicKeyID" => "",
        "gpgPassphrase" => "",
        "useEncryption" => "false",
        "deleteBatchFiles" => "",
        "multiSite" => "false",
        "multiSiteErrorThreshold" => '5',
        "maxHoursWithoutSwitch" => '48',
        "printMultiSiteDebug" => "false",
        "multiSiteUrl1" => "",
        "multiSiteUrl2" => ""
    ];

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Sets logger
     *
     * @param Logger $logger
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Processes authorize request
     *
     * @param  array         $data
     * @param  boolean|false $capturePayment
     * @return array
     */
    public function authorize($data, $capturePayment = false)
    {
        RequestAdapter::validateArray(self::AUTHORIZE_REQUEST_PARAMS, $data);
        $data['id'] = $data['orderId'] . "_A" . rand(0, 99);
        $data['amount'] = RequestAdapter::formatAmount($data['amount'], $data['currency']);
        $action = $capturePayment ? 'sale' : 'authorise';
        $requestArray = $this->getRequestArray($data, self::AUTHORIZE_REQUEST_MAPPER);

        $this->logger->addDebug("VANTIV " . strtoupper($action) . " REQUEST:");
        $this->logger->addDebug(print_r($requestArray, true));

        $cnp = new CnpOnlineRequest();
        try {
            if ($capturePayment) {
                $response = $cnp->saleRequest($requestArray);
            } else {
                $response = $cnp->authorizationRequest($requestArray);
            }
        } catch (\Exception $e) {
            return $this->handleException($e);
        }

        return $this->handleResponse($response, $action);
    }

    /**
     * Processes capture request
     *
     * @param  array $data
     * @return array
     */
    public function capture($data)
    {
        RequestAdapter::validateArray(self::CAPTURE_REQUEST_PARAMS, $data);
        $data['id'] = $data['orderId'] . "_C" . rand(0, 99);
        $requestArray = $this->getRequestArray($data, self::CAPTURE_REQUEST_MAPPER);

        $this->logger->addDebug("VANTIV CAPTURE REQUEST:");
        $this->logger->addDebug(print_r($requestArray, true));

        $cnp = new CnpOnlineRequest();
        try {
            $response = $cnp->captureRequest($requestArray);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }

        return $this->handleResponse($response, 'capture');
    }

    /**
     * Processes sale request
     *
     * @param  array $data
     * @return array
     */
    public function sale($data)
    {
        return $this->authorize($data, true);
    }

    /**
     * Processes refund request
     *
     * @param  array $data
     * @return array
     */
    public function refund($data)
    {
        RequestAdapter::validateArray(self::REFUND_REQUEST_PARAMS, $data);
        $data['id'] = $data['orderId'] . "_R" . rand(0, 99);
        $data['amount'] = RequestAdapter::formatAmount($data['amount'], $data['currency']);
        $requestArray = $this->getRequestArray($data, self::REFUND_REQUEST_MAPPER);

        $this->logger->addDebug("VANTIV REFUND REQUEST:");
        $this->logger->addDebug(print_r($requestArray, true));

        $cnp = new CnpOnlineRequest();
        try {
            $response = $cnp->creditRequest($requestArray);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }

        return $this->handleResponse($response, 'refund');
    }

    /**
     * Processes void request
     *
     * @param  array $data
     * @return array
     */
    public function void($data)
    {
        RequestAdapter::validateArray(self::VOID_REQUEST_PARAMS, $data);
        $data['id'] = $data['orderId'] . "_V" . rand(0, 99);
        $requestArray = $this->getRequestArray($data, self::VOID_REQUEST_MAPPER);

        $this->logger->addDebug("VANTIV VOID REQUEST:");
        $this->logger->addDebug(print_r($requestArray, true));

        $cnp = new CnpOnlineRequest();
        try {
            $response = $cnp->authReversalRequest($requestArray);
        } catch (\Exception $e) {
            return $this->handleException($e);
        }

        return $this->handleResponse($response, 'void');
    }

    /**
     * Gets request array
     *
     * @param  array $data
     * @param  array $mapper
     * @return array
     */
    protected function getRequestArray($data, $mapper)
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        $data['url'] = $this->getRequestUrl($data['gatewayCredentials.environment']);
        $gatewayConfig = RequestAdapter::assignValue(self::GATEWAY_PARAMS_MAPPER, $data);
        $requestData = RequestAdapter::assignValue($mapper, $data);
        $requestArray = array_merge($gatewayConfig, self::CONFIG_PARAMS, $requestData);
        return $requestArray;
    }

    /**
     * Gets request url based on the environment
     *
     * @param  string $environment
     * @return string
     */
    protected function getRequestUrl($environment)
    {
        $urlArray = UrlMapper::getUrl($environment);
        return $urlArray[0];
    }

    /**
     * Validates response
     *
     * @param  \DOMDocument $param
     * @return array
     */
    protected function validateResponse($param)
    {
        $result = ['isValid' => true];
        if (!in_array(XmlParser::getNode($param, 'response'), ['000','010','011','013'])) {
            $message = XmlParser::getNode($param, 'message');
            $result = ['isValid' => false, 'errorMessage' => $message];
        }
        return $result;
    }

    /**
     * Handles Exception
     *
     * @param  \Exception $exc
     * @return array
     */
    protected function handleException($exc)
    {
        $result = ['isValid' => false];
        if ($exc->getMessage()) {
            $result['errorMessage'] = $exc->getMessage();
        }
        return $result;
    }

    /**
     * Handles response
     *
     * @param  \DOMDocument $param
     * @param  string       $action
     * @return array
     */
    protected function handleResponse($param, $action)
    {
        $this->logger->addDebug("VANTIV " . strtoupper($action) . " RESPONSE:");
        $this->logger->addDebug(print_r(XmlParser::getNode($param, 'response'), true));
        $this->logger->addDebug(print_r(XmlParser::getNode($param, 'message'), true));
        $validationResponse = $this->validateResponse($param);
        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }

        $this->logger->addDebug(print_r(XmlParser::getNode($param, 'cnpTxnId'), true));

        $result['isValid'] = true;
        $result['transactionId'] = XmlParser::getNode($param, 'cnpTxnId');
        $result['status'] = self::STATUS_MAPPER[$action];
        return $result;
    }
}
