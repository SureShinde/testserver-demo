<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\firstdata;

use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\ClientInterface;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\RequestAdapter;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;
use UnboundCommerce\GooglePay\Service\Gateway\HttpClient;

/**
 * Class Firstdata Client
 */
class Client implements ClientInterface
{
    const GATEWAY_REQUIRED_PARAMS = ['gatewayCredentials.environment', 'gatewayCredentials.gateway_merchant_id', 'gatewayCredentials.api_key', 'gatewayCredentials.api_secret', 'gatewayCredentials.token'];

    const AUTHORIZE_REQUEST_PARAMS = ['amount', 'currency', 'orderId', 'paymentToken'];

    const AUTHORIZE_REQUEST_MAPPER = [  'amount' => 'amount',
                                        'currency_code' => 'currency',
                                        'merchant_ref' => 'orderId',
                                        'transaction_type' => 'action',
                                        'method' => 'raw.3DS',
                                        '3DS' => [
                                            'signature' => 'signature',
                                            'type' => 'raw.G',
                                            'version' => 'protocolVersion',
                                            'data' => 'signedMessage'
                                        ]
                                    ];

    const SALE_CAPTURE_DELAY = 0;

    const MODIFICATION_REQUEST_PARAMS = ['amount', 'currency', 'transactionId', 'transactionTag'];

    const MODIFICATION_REQUEST_MAPPER = [   'amount' => 'amount',
                                            'currency_code' => 'currency',
                                            'transaction_type' => 'action',
                                            'transaction_tag' => 'transactionTag'
                                        ];

    const SANDBOX_URL = 'https://api-cert.payeezy.com/v1/transactions';

    const PRODUCTION_URL = 'https://api.payeezy.com/v1/transactions';

    const STATUS_MAPPER = ['authorise' => [
                                'approved' => Status::AUTH_SUCCEEDED,
                                'not processed' => Status::AUTH_FAILED,
                                'declined' => Status::AUTH_FAILED
                            ],
                            'capture' => [
                                'approved' => Status::CAPTURE_SUCCEEDED,
                                'not processed' => Status::CAPTURE_FAILED,
                                'declined' => Status::CAPTURE_FAILED
                            ],
                            'sale' => [
                                'approved' => Status::SALE_SUCCEEDED,
                                'not processed' => Status::SALE_FAILED,
                                'declined' => Status::SALE_FAILED
                            ],
                            'refund' => [
                                'approved' => Status::REFUND_SUCCEEDED,
                                'not processed' => Status::REFUND_FAILED,
                                'declined' => Status::REFUND_FAILED
                            ],
                            'void' => [
                                'approved' => Status::VOID_SUCCEEDED,
                                'not processed' => Status::VOID_FAILED,
                                'declined' => Status::VOID_FAILED
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
        case 201:
        case 202:
            $message = "Created / Accepted Transaction request - All OK.";
            break;
        case 400:
            $message = "Input Request is invalid or incorrect.";
            break;
        case 401:
            $message = "Invalid API Key and Token.";
            break;
        case 404:
            $message = "The requested resource does not exist.";
            break;
        case 500:
        case 502:
        case 503:
        case 504:
            $message = "Server Error at Payeezy end.";
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
        RequestAdapter::validateArray(self::AUTHORIZE_REQUEST_PARAMS, $data);

        $data['action'] = 'authorize';
        if ($capturePayment) {
            $data['action'] = 'purchase';
        }
        $requestUrl = $this->getRequestUrl($data);
        $tokenDetails = $this->getTokenDetails($data['paymentToken']);
        $data['amount'] = RequestAdapter::formatAmount($data['amount'], $data['currency']);
        $data = array_merge($data, $tokenDetails);

        $action = $capturePayment ? 'sale' : 'authorise';

        $requestBody = json_encode(RequestAdapter::assignValue(self::AUTHORIZE_REQUEST_MAPPER, $data));
        $requestHeaders = $this->getRequestHeaders($data, $requestBody);

        $this->logger->addDebug("FIRSTDATA " . strtoupper($action) . " REQUEST:");
        $this->logger->addDebug(print_r($requestBody, true));

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $requestBody);

        return $this->handleAuthorizeResponse($response, $action);
    }

    /**
     * Gets payment token details
     *
     * @param  string $paymentToken
     * @return array
     */
    protected function getTokenDetails($paymentToken)
    {
        $paymentTokenArray = json_decode($paymentToken, true);
        return $paymentTokenArray;
    }

    /**
     * Processes capture request
     *
     * @param  array $data
     * @return array
     */
    public function capture($data)
    {
        $data['action'] = 'capture';
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
        $data['action'] = 'refund';
        return $this->modificationRequest($data);
    }

    /**
     * Processes void request
     *
     * @param  array $data
     * @return array
     */
    public function void($data)
    {
        $data['action'] = 'void';
        return $this->modificationRequest($data);
    }

    /**
     * Handles capture/refund/void request
     *
     * @param  array $data
     * @return array
     */
    protected function modificationRequest($data)
    {
        RequestAdapter::validateArray(self::MODIFICATION_REQUEST_PARAMS, $data);
        $requestUrl = $this->getRequestUrl($data);

        $data['amount'] =  RequestAdapter::formatAmount($data['amount'], $data['currency']);

        $requestBody = json_encode(RequestAdapter::assignValue(self::MODIFICATION_REQUEST_MAPPER, $data));
        $requestHeaders = $this->getRequestHeaders($data, $requestBody);
        $this->logger->addDebug("FIRSTDATA " . strtoupper($data['action']) . " REQUEST:");
        $this->logger->addDebug(print_r($requestBody, true));

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $requestBody);

        return $this->handleModificationResponse($response, $data['action']);
    }

    /**
     * Gets request url based on environment
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

        if (in_array($data['action'], ["capture", "void","refund"])) {
            $requestUrl = $requestUrl . "/" . $data['transactionId'];
        }
        return $requestUrl;
    }

    /**
     * Gets a keyed hash value using the HMAC method
     *
     * @param  array  $data
     * @param  string $requestBody
     * @return array
     */
    protected function hmacAuthorizationToken($data, $requestBody)
    {
        $nonce = strval(hexdec(bin2hex(openssl_random_pseudo_bytes(4, $cstrong))));
        $timestamp = strval(time()*1000); //time stamp in milli seconds
        $inputData = $data['gatewayCredentials.api_key'] . $nonce . $timestamp . $data['gatewayCredentials.token'] . $requestBody;
        $hashAlgorithm = "sha256";
        $hmac = hash_hmac($hashAlgorithm, $inputData, $data['gatewayCredentials.api_secret'], false);    // HMAC Hash in hex
        $authorization = base64_encode($hmac);

        return ["Authorization:" . $authorization,
                "nonce:" . $nonce,
                "timestamp:" . $timestamp,
                ];
    }

    /**
     * Gets headers for request
     *
     * @param  array  $data
     * @param  string $requestBody
     * @return array
     */
    protected function getRequestHeaders($data, $requestBody)
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        $authToken = $this->hmacAuthorizationToken($data, $requestBody);
        $headers = ["content-type: application/json",
                    "apikey: " . $data['gatewayCredentials.api_key'],
                    "token: " . $data['gatewayCredentials.token'],
                ];
        $headers = array_merge($headers, $authToken);
        return $headers;
    }

    /**
     * Gets message based on the status code provided
     *
     * @param  string $action
     * @param  string $status
     * @return string
     */
    public function getStatus($action, $status)
    {
        if (!isset(self::STATUS_MAPPER[$action][$status])) {
            return null;
        }

        return self::STATUS_MAPPER[$action][$status];
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
        $response = null;

        if (!empty($param['httpResponse'])) {
            $response = json_decode($param['httpResponse'], true);
        }

        if (empty($response) || !in_array($status, [200,201,202]) || (isset($response['validation_status']) && $response['validation_status'] != 'success')) {
            $errorMessage = "";
            if (isset($response['Error']['messages'])) {
                foreach ($response['Error']['messages'] as $message) {
                    if (isset($message['description'])) {
                        $errorMessage = $errorMessage . $message['description'] . " ";
                    }
                }
            } elseif ($response['message']) {
                $errorMessage = is_string($response['message']) ? $response['message'] : print_r($response['message'], true);
            } else {
                $errorMessage = $this->getStatusMessage($status);
            }
            return ['isValid' => false, 'errorMessage' => $errorMessage];
        }
        return ['isValid' => true, 'body' => $response];
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
        $this->logger->addDebug("FIRSTDATA " . strtoupper($action) . " RESPONSE:");
        $this->logger->addDebug(print_r($param, true));

        $validationResponse = $this->validateResponse($param);

        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }

        $responseBody = $validationResponse['body'];
        $result['isValid'] = true;
        $result['transactionId'] = $responseBody['transaction_id'];
        $result['transactionInfo'] = ['transactionId' => $responseBody['transaction_id'],
                                        'transactionTag' => $responseBody['transaction_tag']];
        $result['status'] = $this->getStatus($action, $responseBody['transaction_status']);
        if (!$result['status']) {
            $result['isValid'] = false;
            $result['errorMessage'] = "Firstdata response status currently not supported: " . $responseBody['transaction_status'];
        }

        return $result;
    }

    /**
     * Handles capture/refund/void response
     *
     * @param  array  $param
     * @param  string $action
     * @return array
     */
    protected function handleModificationResponse($param, $action)
    {
        $this->logger->addDebug("FIRSTDATA " . strtoupper($action) . " RESPONSE:");
        $this->logger->addDebug(print_r($param, true));

        $validationResponse = $this->validateResponse($param);

        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }
        $responseBody = $validationResponse['body'];
        $result['isValid'] = true;
        $result['transactionId'] = $responseBody['transaction_id'];
        if ($action == 'capture') {
            $result['transactionInfo'] = ['transactionId' => $responseBody['transaction_id'],
                                            'transactionTag' => $responseBody['transaction_tag']];
        } elseif ($action == 'refund') {
            $result['transactionId'] = $result['transactionId'] . "_" . $responseBody['transaction_tag'];
        }

        $result['status'] = $this->getStatus($action, $responseBody['transaction_status']);
        if (!$result['status']) {
            $result['isValid'] = false;
            $result['errorMessage'] = "Firstdata response status currently not supported: " . $responseBody['transaction_status'];
        }
        return $result;
    }
}
