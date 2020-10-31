<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\moneris;

//require_once 'mpgGlobals.php';
/**
*if (!defined('mpgGlobals')) {
*    include 'mpgGlobals.php';
*    define('mpgGlobals', 1);
*}
*/

use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\ClientInterface;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\RequestAdapter;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;
use UnboundCommerce\GooglePay\Service\Gateway\HttpClient;

/**
 * Class Moneris Client
 */
class Client implements ClientInterface
{
    const GATEWAY_AUTHORIZE_REQUEST_PARAMS = ['gatewayCredentials.environment', 'gatewayCredentials.gateway_store_id', 'gatewayCredentials.web_merchant_key'];

    const GATEWAY_MODIFY_REQUEST_PARAMS = ['gatewayCredentials.environment', 'gatewayCredentials.gateway_store_id', 'gatewayCredentials.api_token'];

    const AUTHORIZE_REQUEST_PARAMS = ['amount', 'currency', 'orderId', 'tokenizationData'];

    const AUTHORIZE_REQUEST_MAPPER = ['amount' => 'amount',
                                    'transType' => 'transType',
                                    'payment' => 'tokenizationData',
                                    'orderId' => 'orderId',
                                    'storeId' => 'gatewayCredentials.gateway_store_id',
                                    'webMerchantKey' => 'gatewayCredentials.web_merchant_key'
                                    ];

    const MODIFY_REQUEST_PARAMS = ['amount', 'currency', 'orderId', 'originalReference'];

    const CAPTURE_REQUEST_MAPPER = ['type' => 'transType',
                                    'order_id' => 'orderId',
                                    'comp_amount' => 'amount',
                                    'txn_number' => 'originalReference',
                                    'crypt_type' => 'raw.7',
                                    'dynamic_descriptor' => 'gatewayCredentials.dynamic_descriptor'
                                    ];

    const REFUND_REQUEST_MAPPER = ['type' => 'transType',
                                    'txn_number' => 'originalReference',
                                    'order_id' => 'orderId',
                                    'amount' => 'amount',
                                    'crypt_type' => 'raw.7',
                                    'dynamic_descriptor' => 'gatewayCredentials.dynamic_descriptor'
                                    ];

    const VOID_REQUEST_PARAMS = ['orderId', 'originalReference'];

    const VOID_REQUEST_MAPPER = ['type' => 'transType',
                                'txn_number' => 'originalReference',
                                'order_id' => 'orderId',
                                'crypt_type' => 'raw.7',
                                'dynamic_descriptor' => 'gatewayCredentials.dynamic_descriptor'
                                ];

    const SANDBOX_URL = 'https://esqa.moneris.com/googlepay/extern/googleApi/';

    const PRODUCTION_URL = 'https://www3.moneris.com/gp/extern/googleApi/';

    const STATUS_MAPPER = ['authorise' => [
                                'succeeded' => Status::AUTH_SUCCEEDED,
                                'failed' => Status::AUTH_FAILED
                                ],
                                'capture' => [
                                    'succeeded' => Status::CAPTURE_SUCCEEDED,
                                    'failed' => Status::CAPTURE_FAILED
                                ],
                                'sale' => [
                                    'succeeded' => Status::SALE_SUCCEEDED,
                                    'failed' => Status::SALE_FAILED
                                ],
                                'refund' => [
                                    'succeeded' => Status::REFUND_SUCCEEDED,
                                    'failed' => Status::REFUND_FAILED
                                ],
                                'void' => [
                                    'succeeded' => Status::VOID_SUCCEEDED,
                                    'failed' => Status::VOID_FAILED
                                ]
    ];

    /**
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->httpClient = new HttpClient();
    }

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
        RequestAdapter::validateArray(self::GATEWAY_AUTHORIZE_REQUEST_PARAMS, $data);
        RequestAdapter::validateArray(self::AUTHORIZE_REQUEST_PARAMS, $data);
        $requestUrl = $this->getRequestUrl($data['gatewayCredentials.environment']);
        $requestHeaders = $this->getRequestHeaders();

        $data['amount'] = RequestAdapter::formatAmount($data['amount'], $data['currency'], true);

        if ($capturePayment) {
            $data['transType'] = "purchase";
        } else {
            $data['transType'] = "preauth";
        }

        $action = $capturePayment ? 'sale' : 'authorise';
        $requestBody = json_encode(RequestAdapter::assignValue(self::AUTHORIZE_REQUEST_MAPPER, $data));

        $this->logger->addDebug("MONERIS " . strtoupper($action) . " REQUEST:");
        $this->logger->addDebug(print_r($requestBody, true));

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $requestBody);

        return $this->handleAuthorizeResponse($response, $action);
    }

    /**
     * Processes capture request
     *
     * @param  array         $data
     * @param  boolean|false $isVoid
     * @return array
     */
    public function capture($data, $isVoid = false)
    {
        RequestAdapter::validateArray(self::MODIFY_REQUEST_PARAMS, $data);

        $data['transType'] = "completion";
        if ($isVoid) {
            $data['amount'] ="0.00";
            $action = 'void';
        } else {
            $data['amount'] = RequestAdapter::formatAmount($data['amount'], $data['currency'], true);
            $action = 'capture';
        }

        $requestArray = RequestAdapter::assignValue(self::CAPTURE_REQUEST_MAPPER, $data);

        return $this->modificationRequest($data, $requestArray, $action);
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
        RequestAdapter::validateArray(self::MODIFY_REQUEST_PARAMS, $data);

        $data['transType'] = $action = "refund";
        $data['amount'] = RequestAdapter::formatAmount($data['amount'], $data['currency'], true);

        $requestArray = RequestAdapter::assignValue(self::REFUND_REQUEST_MAPPER, $data);

        return $this->modificationRequest($data, $requestArray, $action);
    }

    /**
     * Processes void request
     *
     * @param  array $data
     * @return array
     */
    public function void($data)
    {
        return $this->capture($data, true);
    }

    /**
     * Handles capture/refund/void request
     *
     * @param  array  $data
     * @param  array  $requestArray
     * @param  string $action
     * @return array
     */
    protected function modificationRequest($data, $requestArray, $action)
    {
        RequestAdapter::validateArray(self::GATEWAY_MODIFY_REQUEST_PARAMS, $data);
        $mpgTxn = new mpgTransaction($requestArray);
        $mpgRequest = new mpgRequest($mpgTxn);
        if ($data['currency'] == "CAD") {
            $mpgRequest->setProcCountryCode("CA"); //sending transaction to Canadian environment
        } else {
            $mpgRequest->setProcCountryCode("US"); //sending transaction to US environment
        }

        if ($data['gatewayCredentials.environment'] == 'sandbox') {
            $mpgRequest->setTestMode(true);
        }

        $this->logger->addDebug("TRANSACTION ARRAY FOR MONERIS " . strtoupper($action) . " REQUEST:");
        $this->logger->addDebug(print_r($mpgTxn, true));

        $mpgHttpPost = new mpgHttpsPost($data['gatewayCredentials.gateway_store_id'], $data['gatewayCredentials.api_token'], $mpgRequest);
        $mpgResponse = $mpgHttpPost->getMpgResponse();
        return $this->handleModificationResponse($mpgResponse, $action);
    }

    /**
     * Gets request url based on environment
     *
     * @param  string $environment
     * @return string
     */
    protected function getRequestUrl($environment)
    {
        $requestUrl = self::SANDBOX_URL;
        if ($environment == 'production') {
            $requestUrl = self::PRODUCTION_URL;
        }
        return $requestUrl;
    }

    /**
     * Gets headers for request
     *
     * @return array
     */
    protected function getRequestHeaders()
    {
        return ["content-type: application/json",
                "accept: application/json"
                ];
    }

    /**
     * Validates response
     *
     * @param  array $param
     * @return array
     */
    protected function validateResponse($param)
    {
        $response = null;
        if (!empty($param['httpResponse'])) {
            $response = json_decode($param['httpResponse'], true);
        }
        if (isset($response['receipt'])) {
            return ['isValid' => true, 'body'=> $response['receipt']];
        }
        return ['isValid' => false];
    }

    /**
     * Handles authorization/sale response
     *
     * @param  array  $param
     * @param  string $action
     * @return array
     */
    protected function handleAuthorizeResponse($param, $action)
    {
        $this->logger->addDebug("MONERIS " . strtoupper($action) . " RESPONSE:");
        $this->logger->addDebug(print_r($param, true));

        $validationResponse = $this->validateResponse($param);
        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }

        $httpResponse = $validationResponse['body'];
        $result['isValid'] = true;

        $responseCode = $httpResponse['ResponseCode'] ?? false;
        $complete = $httpResponse['Complete'] ?? false;
        if (!$responseCode || (int) $responseCode >= 50 || !$complete) {
            $result['isValid'] = false;
            $result['errorMessage'] = $httpResponse['Message'];
            $result['status'] = self::STATUS_MAPPER[$action]['failed'];
            return $result;
        } else {
            $result['status'] = self::STATUS_MAPPER[$action]['succeeded'];
        }
        $result['transactionId'] = $httpResponse['TransID'] ?? null;
        $result['referenceNum'] = $httpResponse['ReferenceNum'] ?? null;
        if ($result['referenceNum']) {
            $result['transactionComment'] = "Transaction Reference Number: " . $result['referenceNum'];
        }
        return $result;
    }

    /**
     * Handles capture/refund/void response
     *
     * @param  mpgResponse $mpgResponse
     * @param  string      $action
     * @return array
     */
    protected function handleModificationResponse($mpgResponse, $action)
    {
        $this->logger->addDebug("MONERIS " . strtoupper($action) . " RESPONSE:");
        $this->logger->addDebug(print_r($mpgResponse, true));

        $result['isValid'] = true;

        $responseCode =  $mpgResponse->getResponseCode();
        $complete = $mpgResponse->getComplete();

        if (!$responseCode || $responseCode == "null" || (int) $responseCode >= 50 || !$complete || $complete == "false") {
            $result['isValid'] = false;
            $result['errorMessage'] = $mpgResponse->getMessage();
            $result['status'] = self::STATUS_MAPPER[$action]['failed'];
            return $result;
        } else {
            $result['status'] = self::STATUS_MAPPER[$action]['succeeded'];
        }

        $transactionId =  $mpgResponse->getTxnNumber();
        $referenceNum =  $mpgResponse->getReferenceNum();

        if (!empty($transactionId)) {
            $result['transactionId'] = $transactionId;
            $result['transactionInfo'] = ['originalReference' => $result['transactionId']];
        }
        if (!empty($referenceNum)) {
            $result['transactionComment'] = "Transaction Reference Number: " . $referenceNum;
        }
        return $result;
    }
}
