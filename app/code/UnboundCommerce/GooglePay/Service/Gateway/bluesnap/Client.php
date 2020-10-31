<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\bluesnap;

use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\ClientInterface;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\RequestAdapter;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;
use UnboundCommerce\GooglePay\Service\Gateway\HttpClient;

/**
 * Class Bluesnap Client
 */
class Client implements ClientInterface
{
    const GATEWAY_REQUIRED_PARAMS = ['gatewayCredentials.environment', 'gatewayCredentials.username', 'gatewayCredentials.password'];

    const AUTHORIZE_REQUEST_PARAMS = ['amount', 'currency', 'jsonEncodedPaymentData'];

    const AUTHORIZE_REQUEST_MAPPER = ['cardTransactionType' => 'action',
                                        'amount' => 'amount','currency' => 'currency',
                                        'wallet' => [
                                            'walletType' => 'raw.GOOGLE_PAY',
                                            'encodedPaymentToken' => 'encodedPaymentToken'
                                        ]
                                    ];

    const MODIFICATION_REQUEST_PARAMS = ['amount', 'originalReference', 'action'];

    const REFUND_REQUEST_PARAMS = ['amount', 'originalReference'];

    const MODIFICATION_REQUEST_MAPPER = ['cardTransactionType' => 'action',
                                            'amount' => 'amount',
                                            'transactionId' => 'originalReference'
                                        ];

    const SANDBOX_URL = 'https://sandbox.bluesnap.com/services/2/transactions';

    const PRODUCTION_URL = 'https://ws.bluesnap.com/services/2/transactions';

    const KEY_AUTHORIZE = 'AUTH_ONLY';

    const KEY_CAPTURE = 'CAPTURE';

    const KEY_SALE = 'AUTH_CAPTURE';

    const KEY_CANCEL = 'AUTH_REVERSAL';

    const STATUS_MAPPER = ['authorise' => Status::AUTH_SUCCEEDED,
                            'capture' => Status::CAPTURE_SUCCEEDED,
                            'sale' => Status::SALE_SUCCEEDED,
                            'refund' => Status::REFUND_SUCCEEDED,
                            'void' => Status::VOID_SUCCEEDED
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
     * Gets message based on the status code provided
     *
     * @param  integer $statusCode
     * @return string
     */
    protected function getStatusMessage($statusCode)
    {
        switch ($statusCode) {
        case 200:
            $message = "Request processed normally";
            break;
        case 400:
            $message = "Problem reading or understanding request";
            break;
        case 403:
            $message = "Insufficient permission to process request";
            break;
        case 404:
            $message = "File Not Found";
            break;
        case 500:
            $message = "Bluesnap could not process request";
            break;
        default:
            $message = "Request Failed";
            break;
        }
        return $message;
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
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        RequestAdapter::validateArray(self::AUTHORIZE_REQUEST_PARAMS, $data);
        $requestUrl = $this->getRequestUrl($data);
        $requestHeaders = $this->getRequestHeaders();
        $requestAuth = $this->getRequestAuth($data);
        $data['encodedPaymentToken'] = self::createBlsTokenFromGooglePayPaymentData($data['jsonEncodedPaymentData']);
        if ($capturePayment) {
            $data['action'] = self::KEY_SALE;
        } else {
            $data['action'] = self::KEY_AUTHORIZE;
        }
        $requestArray = RequestAdapter::assignValue(self::AUTHORIZE_REQUEST_MAPPER, $data);

        $action = $capturePayment ? 'sale' : 'authorise';
        $requestBody = json_encode($requestArray);

        $this->logger->addDebug("BLUESNAP " . strtoupper($action) . " REQUEST:");
        $this->logger->addDebug(print_r($requestBody, true));

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $requestBody, $requestAuth);

        return $this->handleResponse($response, $action, false);
    }

    /**
     * Processes capture request
     *
     * @param  array $data
     * @return array
     */
    public function capture($data)
    {
        $data['action'] = self::KEY_CAPTURE;
        return $this->modificationRequest($data);
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
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        RequestAdapter::validateArray(self::REFUND_REQUEST_PARAMS, $data);
        $requestUrl = $this->getRequestUrl($data) . '/' . $data['originalReference'] . '/refund?amount=' . $data['amount'];
        $requestHeaders = $this->getRequestHeaders();
        $requestAuth = $this->getRequestAuth($data);

        $this->logger->addDebug("BLUESNAP REFUND REQUEST:");
        $this->logger->addDebug($requestUrl);
        $response = $this->httpClient->put($requestUrl, $requestHeaders, null, $requestAuth);

        return $this->handleRefundResponse($response, $data['originalReference']);
    }

    /**
     * Processes void request
     *
     * @param  array $data
     * @return array
     */
    public function void($data)
    {
        $data['action'] = self::KEY_CANCEL;
        return $this->modificationRequest($data);
    }

    /**
     * Handles capture/void request
     *
     * @param  array $data
     * @return array
     */
    protected function modificationRequest($data)
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        RequestAdapter::validateArray(self::MODIFICATION_REQUEST_PARAMS, $data);
        $requestUrl = $this->getRequestUrl($data);
        $requestHeaders = $this->getRequestHeaders();
        $requestAuth = $this->getRequestAuth($data);
        $requestBody = json_encode(RequestAdapter::assignValue(self::MODIFICATION_REQUEST_MAPPER, $data));
        $action = 'capture';
        if ($data['action'] == self::KEY_CANCEL) {
            $action = 'void';
        }
        $this->logger->addDebug("BLUESNAP " . strtoupper($action) . " REQUEST:");
        $this->logger->addDebug(print_r($requestBody, true));

        $response = $this->httpClient->put($requestUrl, $requestHeaders, $requestBody, $requestAuth);

        return $this->handleResponse($response, $action, true);
    }

    /**
     * Gets request url based on the environment
     *
     * @param  array $data
     * @return string
     */
    protected function getRequestUrl($data)
    {
        $requestUrl = self::SANDBOX_URL;
        if ($data['gatewayCredentials.environment'] == 'production') {
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
     * Creates bls token from GooglePay payment data
     *
     * @param  string $paymentData
     * @return string|false
     */
    protected function createBlsTokenFromGooglePayPaymentData($paymentData)
    {
        return base64_encode($paymentData);
    }

    /**
     * Gets authentication data for request
     *
     * @param  array $data
     * @return string
     */
    protected function getRequestAuth($data)
    {
        return $data['gatewayCredentials.username'] . ":" . $data['gatewayCredentials.password'];
    }

    /**
     * Validates response
     *
     * @param  array $param
     * @return array
     */
    protected function validateResponse($param)
    {
        $status = $param['statusCode'];
        $response    = null;
        if (!empty($param['httpResponse'])) {
            $response = json_decode($param['httpResponse'], true);
        }
        if (!in_array($status, [200, 201, 204])) {
            if (empty($response) || !isset($response['message'])) {
                return ['isValid' => false, 'errorMessage' => $this->getStatusMessage($status)];
            } else {
                $errorMessage = "";
                foreach ($response['message'] as $message) {
                    if (isset($message['description'])) {
                        $errorMessage = $errorMessage . $message['description'] . " ";
                    }
                }
                return ['isValid' => false, 'errorMessage' => $errorMessage];
            }
        }
        return ['isValid' => true, 'body' => $response];
    }

    /**
     * Handles authorize/sale/capture/void response
     *
     * @param  array   $param
     * @param  string  $action
     * @param  boolean $isModification
     * @return array
     */
    protected function handleResponse($param, $action, $isModification)
    {
        $this->logger->addDebug("BLUESNAP " . strtoupper($action) . " RESPONSE:");
        $this->logger->addDebug(print_r($param, true));
        $validationResponse = $this->validateResponse($param);
        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }

        $httpResponse = $validationResponse['body'];
        $result['isValid'] = true;
        $result['transactionId'] = $httpResponse['transactionId'];
        if ($isModification) {
            $result['transactionId'] = $result['transactionId'] . "_" . time();
        }

        $result['status'] = self::STATUS_MAPPER[$action];

        return $result;
    }

    /**
     * Handles refund response
     *
     * @param  array  $param
     * @param  string $originalReference
     * @return array
     */
    protected function handleRefundResponse($param, $originalReference)
    {
        $this->logger->addDebug("BLUESNAP REFUND RESPONSE:");
        $this->logger->addDebug(print_r($param, true));
        $validationResponse = $this->validateResponse($param);
        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }

        $result['isValid'] = true;
        $result['transactionId'] = $originalReference . "_" . time();
        $result['status'] = self::STATUS_MAPPER['refund'];
        return $result;
    }
}
