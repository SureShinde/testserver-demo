<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\stripe;

use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\ClientInterface;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\RequestAdapter;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;
use UnboundCommerce\GooglePay\Service\Gateway\HttpClient;

/**
 * Class Stripe Client
 */
class Client implements ClientInterface
{
    const URLS = ['authorize' => 'https://api.stripe.com/v1/charges',
                  'capture' => ['https://api.stripe.com/v1/charges/','/capture'],
                  'refund' => 'https://api.stripe.com/v1/refunds',
                  'void' => 'https://api.stripe.com/v1/refunds',
                  'retrieveCharge' =>'https://api.stripe.com/v1/charges/',
                  'retrieveRefund' =>'https://api.stripe.com/v1/refunds/'
                ];

    const API_KEY = "gatewayCredentials.secret_key";

    const GATEWAY_REQUIRED_PARAMS = ['gatewayCredentials.environment', 'gatewayCredentials.secret_key'];

    const AUTHORIZE_REQUEST_PARAMS = ['amount', 'currency', 'orderId', 'paymentToken'];

    const AUTHORIZE_REQUEST_MAPPER = ['amount' => 'amount',
                                    'currency' => 'currency',
                                    'capture' => 'capture',
                                    'source' => 'paymentTokenId',
                                    'description' => 'description',
                                    'metadata' => [
                                        'orderId' => 'orderId'
                                        ]
                                    ];

    const CAPTURE_REQUEST_PARAMS = ['originalReference'];

    const REFUND_REQUEST_PARAMS = ['originalReference', 'amount', 'currency', 'orderId'];

    const REFUND_REQUEST_MAPPER = ['charge' => 'originalReference',
                                    'amount' => 'amount',
                                    'metadata' => [
                                        'orderId' => 'orderId'
                                        ]
                                    ];

    const VOID_REQUEST_PARAMS = ['originalReference', 'orderId'];

    const VOID_REQUEST_MAPPER = ['charge' => 'originalReference',
                                    'metadata' => [
                                        'orderId' => 'orderId'
                                        ]
                                ];

    const RETRIEVE_TRANSACTION_PARAMS = ['transactionId'];

    const STATUS_MAPPER = ['authorise' => [
                                    'succeeded' => Status::AUTH_SUCCEEDED,
                                    'pending' => Status::AUTH_PENDING,
                                    'failed' => Status::AUTH_FAILED
                                ],
                            'capture' => [
                                'succeeded' => Status::CAPTURE_SUCCEEDED,
                                'pending' => Status::CAPTURE_PENDING,
                                'failed' => Status::CAPTURE_FAILED
                            ],
                            'sale' => [
                                'succeeded' => Status::SALE_SUCCEEDED,
                                'pending' => Status::SALE_PENDING,
                                'failed' => Status::SALE_FAILED
                            ],
                            'refund' => [
                                'succeeded' => Status::REFUND_SUCCEEDED,
                                'pending' => Status::REFUND_PENDING,
                                'failed' => Status::REFUND_FAILED,
                                'canceled' => Status::REFUND_FAILED,
                            ],
                            'void' => [
                                'succeeded' => Status::VOID_SUCCEEDED,
                                'pending' => Status::VOID_PENDING,
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
     * Gets message based on the status code provided
     *
     * @param  integer $statusCode
     * @return string
     */
    protected function getStatusMessage($statusCode)
    {
        switch ($statusCode) {
        case 200:
            $message = "Everything worked as expected.";
            break;
        case 400:
            $message = "The request was unacceptable, often due to missing a required parameter.";
            break;
        case 401:
            $message = "No valid API key provided.";
            break;
        case 402:
            $message = "The parameters were valid but the request failed.";
            break;
        case 404:
            $message = "The requested resource doesn't exist.";
            break;
        case 409:
            $message = "The request conflicts with another request (perhaps due to using the same idempotent key).";
            break;
        case 429:
            $message = "Too many requests hit the API too quickly. We recommend an exponential backoff of your requests.";
            break;
        case 500:
        case 502:
        case 503:
        case 504:
            $message = "Something went wrong on Stripe's end.";
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
     * @param  array          $data
     * @param  string|'false' $capturePayment
     * @return array
     */
    public function authorize($data, $capturePayment = 'false')
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        RequestAdapter::validateArray(self::AUTHORIZE_REQUEST_PARAMS, $data);
        $requestUrl = $this->getRequestUrl('authorize', $data);
        $requestHeaders = $this->getRequestHeaders($data);

        $data['amount'] =  RequestAdapter::formatAmount($data['amount'], $data['currency']);
        $data['currency'] = strtolower($data['currency']);
        $data['capture'] = $capturePayment;
        $data['description'] = "Order Id: " . $data['orderId'];
        $data['paymentTokenId'] = $this->getPaymentTokenId($data['paymentToken']);

        $action = ($capturePayment === 'true') ? 'sale' : 'authorise';
        $requestBody = RequestAdapter::assignValue(self::AUTHORIZE_REQUEST_MAPPER, $data);
        $queryString = http_build_query($requestBody);

        $this->logger->addDebug("STRIPE " . strtoupper($action) . " REQUEST:");
        $this->logger->addDebug($queryString);

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $queryString);

        return $this->handleChargeResponse($response, $action);
    }

    /**
     * Processes capture request
     *
     * @param  array $data
     * @return array
     */
    public function capture($data)
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        RequestAdapter::validateArray(self::CAPTURE_REQUEST_PARAMS, $data);
        $requestUrl = $this->getRequestUrl('capture', $data);
        $requestHeaders = $this->getRequestHeaders($data);

        $this->logger->addDebug("STRIPE CAPTURE REQUEST:");
        $this->logger->addDebug($requestUrl);

        $response = $this->httpClient->post($requestUrl, $requestHeaders);

        return $this->handleChargeResponse($response, 'capture');
    }

    /**
     * Processes sale request
     *
     * @param  array $data
     * @return array
     */
    public function sale($data)
    {
        return $this->authorize($data, 'true');
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
        $requestUrl = $this->getRequestUrl('refund', $data);
        $requestHeaders = $this->getRequestHeaders($data);

        $data['amount'] =  RequestAdapter::formatAmount($data['amount'], $data['currency']);

        $requestBody = RequestAdapter::assignValue(self::REFUND_REQUEST_MAPPER, $data);
        $queryString = http_build_query($requestBody);

        $this->logger->addDebug("STRIPE REFUND REQUEST:");
        $this->logger->addDebug($queryString);

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $queryString);

        return $this->handleRefundResponse($response, 'refund');
    }

    /**
     * Processes void request
     *
     * @param  array $data
     * @return array
     */
    public function void($data)
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        RequestAdapter::validateArray(self::VOID_REQUEST_PARAMS, $data);
        $requestUrl = $this->getRequestUrl('void', $data);
        $requestHeaders = $this->getRequestHeaders($data);

        $requestBody = RequestAdapter::assignValue(self::VOID_REQUEST_MAPPER, $data);
        $queryString = http_build_query($requestBody);

        $this->logger->addDebug("STRIPE VOID REQUEST:");
        $this->logger->addDebug($queryString);

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $queryString);

        return $this->handleRefundResponse($response, 'void');
    }

    /**
     * Retrieves a transaction by transaction id
     *
     * @param  array   $data
     * @param  boolean $isRefund
     * @return array
     */
    public function retrieveTransaction($data, $isRefund)
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        RequestAdapter::validateArray(self::RETRIEVE_TRANSACTION_PARAMS, $data);

        if ($isRefund) {
            $requestUrl = $this->getRequestUrl('retrieveRefund', $data);
        } else {
            $requestUrl = $this->getRequestUrl('retrieveCharge', $data);
        }
        $requestHeaders = $this->getRequestHeaders($data);
        $this->logger->addDebug("RETRIEVING STRIPE TRANSACTION: " . $data['transactionId']);

        $response = $this->httpClient->get($requestUrl, $requestHeaders);
        $this->logger->addDebug(print_r($response, true));

        return $this->validateResponse($response);
    }

    /**
     * Gets request url
     *
     * @param  string $action
     * @param  array  $data
     * @return string
     */
    protected function getRequestUrl($action, $data)
    {
        if ($action == "capture") {
            return self::URLS[$action][0] . $data['originalReference'] . self::URLS[$action][1];
        } elseif ($action == "retrieveCharge" || $action == "retrieveRefund") {
            return self::URLS[$action] . $data['transactionId'];
        }
        return self::URLS[$action];
    }

    /**
     * Gets headers for request
     *
     * @param  array $data
     * @return array
     */
    protected function getRequestHeaders($data)
    {
        return ["content-type: application/x-www-form-urlencoded",
                "authorization: Bearer " . $data[self::API_KEY]
                ];
    }

    /**
     * Gets Stripe payment token id
     *
     * @param  string $paymentToken
     * @return string
     */
    protected function getPaymentTokenId($paymentToken)
    {
        $paymentTokenArray = json_decode($paymentToken, true);
        return $paymentTokenArray['id'];
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
        if ($status != 200) {
            if (isset($response['error']['message'])) {
                return ['isValid' => false, 'errorMessage' => $response['error']['message']];
            } elseif (isset($response['error']['code'])) {
                return ['isValid' => false, 'errorMessage' => $response['error']['code']];
            } else {
                return ['isValid' => false, 'errorMessage' => $this->getStatusMessage($status)];
            }
        }
        return ['isValid' => true, 'body' => $response];
    }

    /**
     * Handles authorization/capture/sale response
     *
     * @param  array  $param
     * @param  string $action
     * @return array
     */
    protected function handleChargeResponse($param, $action)
    {
        $this->logger->addDebug("STRIPE " . strtoupper($action) . " RESPONSE:");
        $this->logger->addDebug(print_r($param, true));

        $validationResponse = $this->validateResponse($param);
        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }

        $httpResponse = $validationResponse['body'];
        $result['isValid'] = true;
        $result['transactionId'] = $httpResponse['id'];
        if ($action === "capture") {
            $result['transactionId'] = "capture_" . $result['transactionId'];
        }

        $result['status'] = $this->getStatus($action, $httpResponse['status']);

        if (!$result['status']) {
            $result['isValid'] = false;
            $result['errorMessage'] = "Stripe response status currently not supported: " . $httpResponse['status'];
        }
        if ($httpResponse['outcome']['network_status'] !== "approved_by_network") {
            $result['errorMessage'] = $httpResponse['seller_message'];
        }
        return $result;
    }

    /**
     * Handles refund/void response
     *
     * @param  array  $param
     * @param  string $action
     * @return array
     */
    protected function handleRefundResponse($param, $action)
    {
        $this->logger->addDebug("STRIPE " . strtoupper($action) . " RESPONSE:");
        $this->logger->addDebug(print_r($param, true));

        $validationResponse = $this->validateResponse($param);
        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }

        $httpResponse = $validationResponse['body'];
        $result['isValid'] = true;
        $result['transactionId'] = $httpResponse['id'];
        $result['status'] = $this->getStatus($action, $httpResponse['status']);
        if (!$result['status']) {
            $result['isValid'] = false;
            $result['errorMessage'] = "Stripe response status currently not supported: " . $httpResponse['status'];
        }
        if (isset($httpResponse['failure_reason'])) {
            $result['errorMessage'] = $httpResponse['failure_reason'];
        }

        return $result;
    }
}
