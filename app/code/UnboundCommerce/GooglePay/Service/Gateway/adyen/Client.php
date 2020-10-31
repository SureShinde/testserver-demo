<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\adyen;

use BadFunctionCallException;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\ClientInterface;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\RequestAdapter;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;
use UnboundCommerce\GooglePay\Service\Gateway\HttpClient;

/**
 * Class Adyen Client
 */
class Client implements ClientInterface
{
    const GATEWAY_REQUIRED_PARAMS = ['gatewayCredentials.environment', 'gatewayCredentials.gateway_merchant_id', 'gatewayCredentials.api_key'];

    const AUTHORIZE_REQUEST_PARAMS = ['amount', 'currency', 'orderId', 'paymentToken', 'userAgent'];

    const AUTHORIZE_REQUEST_MAPPER = ['amount' => ['value' => 'amount','currency' => 'currency'],
                                    'additionalData' => ['type' => 'raw.paywithgoogle', 'paywithgoogle.token' => 'paymentToken', 'executeThreeD' => 'enableThreeDSecure'],
                                    'browserInfo' => 'userAgent',
                                    'reference' => 'orderId',
                                    'captureDelayHours' => 'captureDelay',
                                    'merchantAccount' => 'gatewayCredentials.gateway_merchant_id'
                                    ];

    const MODIFICATION_REQUEST_PARAMS = ['amount', 'currency', 'originalReference', 'orderId'];

    const MODIFICATION_REQUEST_MAPPER = ['modificationAmount' => ['value' => 'amount','currency' => 'currency'],
                                        'originalReference' => 'originalReference',
                                        'reference' => 'orderId',
                                        'merchantAccount' => 'gatewayCredentials.gateway_merchant_id'
                                        ];

    const VOID_REQUEST_PARAMS = ['originalReference', 'orderId'];

    const VOID_REQUEST_MAPPER = ['originalReference' => 'originalReference',
                                'reference' => 'orderId',
                                'merchantAccount' => 'gatewayCredentials.gateway_merchant_id'
                                ];

    const SALE_CAPTURE_DELAY = 0;

    const SANDBOX_URLS = ['authorize' => 'https://pal-test.adyen.com/pal/servlet/Payment/v40/authorise',
                        'capture' => 'https://pal-test.adyen.com/pal/servlet/Payment/v40/capture',
                        'refund' => 'https://pal-test.adyen.com/pal/servlet/Payment/v40/refund',
                        'void' => 'https://pal-test.adyen.com/pal/servlet/Payment/v40/cancel'
                        ];

    const PRODUCTION_URLS = ['authorize' => '-pal-live.adyenpayments.com/pal/servlet/Payment/authorise',
                            'authorise3d' => '-pal-live.adyenpayments.com/pal/servlet/Payment/authorise3d',
                            'capture' => '-pal-live.adyenpayments.com/pal/servlet/Payment/capture',
                            'refund' => '-pal-live.adyenpayments.com/pal/servlet/Payment/refund',
                            'void' => '-pal-live.adyenpayments.com/pal/servlet/Payment/cancel'
                            ];

    const STATUS_MAPPER = ['authorise' => [
                                'Authorised' => Status::AUTH_SUCCEEDED,
                                'Refused' => Status::AUTH_FAILED,
                                'Error' => Status::AUTH_FAILED,
                                'Cancelled' => Status::AUTH_FAILED,
                                'RedirectShopper' => Status::AUTH_PENDING,
                                'Pending' => Status::AUTH_PENDING,
                                'Received' => Status::AUTH_RECEIVED
                            ],
                            'sale' => [
                                'Authorised' => Status::SALE_RECEIVED,
                                'Refused' => Status::SALE_FAILED,
                                'Error' => Status::SALE_FAILED,
                                'Cancelled' => Status::SALE_FAILED,
                                'RedirectShopper' => Status::SALE_PENDING,
                                'Pending' => Status::SALE_PENDING,
                                'Received' => Status::SALE_RECEIVED
                            ],
                            'capture' => Status::CAPTURE_RECEIVED,
                            'refund' => Status::REFUND_RECEIVED,
                            'void' => Status::VOID_RECEIVED
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
            $message = "Request processed normally.";
            break;
        case 400:
            $message = "Problem reading or understanding request.";
            break;
        case 401:
            $message = "Authentication required.";
            break;
        case 403:
            $message = "Insufficient permission to process request.";
            break;
        case 404:
            $message = "File Not Found.";
            break;
        case 422:
            $message = "Request validation error.";
            break;
        case 500:
            $message = "Server could not process request.";
            break;
        default:
            $message = "Request Failed.";
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
        $requestUrl = $this->getRequestUrl($data, 'authorize');
        $requestHeaders = $this->getRequestHeaders($data);
        $data['amount'] = RequestAdapter::formatAmount($data['amount'], $data['currency']);
        $data['enableThreeDSecure'] = false;
        /**
        if ($data['gatewayCredentials.three_d_secure_type'] !== "manual") {
            unset($data['gatewayCredentials.three_d_secure_value']);
        } elseif ($data['gatewayCredentials.three_d_secure_value'] == 1) {
            $data['gatewayCredentials.three_d_secure_value'] = true;
        } else {
            $data['gatewayCredentials.three_d_secure_value'] = false;
        }
*/

        if ($capturePayment) {
            $data['captureDelay'] = self::SALE_CAPTURE_DELAY;
        }

        $action = $capturePayment ? 'sale' : 'authorise';
        $requestBody = json_encode(RequestAdapter::assignValue(self::AUTHORIZE_REQUEST_MAPPER, $data));

        $this->logger->addDebug("ADYEN " . strtoupper($action) . " REQUEST:");
        $this->logger->addDebug(print_r($requestBody, true));

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $requestBody);

        return $this->handleAuthorizeResponse($response, $action);
    }

    /**
     * Processes capture request
     *
     * @param  array $data
     * @return array
     */
    public function capture($data)
    {
        return $this->modificationRequest($data, 'capture');
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
        return $this->modificationRequest($data, 'refund');
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
        $requestUrl = $this->getRequestUrl($data, 'void');
        $requestHeaders = $this->getRequestHeaders($data);
        $data['amount'] =  RequestAdapter::formatAmount($data['amount'], $data['currency']);

        $requestBody = json_encode(RequestAdapter::assignValue(self::VOID_REQUEST_MAPPER, $data));

        $this->logger->addDebug("ADYEN VOID REQUEST:");
        $this->logger->addDebug(print_r($requestBody, true));

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $requestBody);

        return $this->handleModificationResponse($response, 'void');
    }

    /**
     * Handles capture/refund request
     *
     * @param  array  $data
     * @param  string $action
     * @return array
     */
    protected function modificationRequest($data, $action)
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        RequestAdapter::validateArray(self::MODIFICATION_REQUEST_PARAMS, $data);
        $requestUrl = $this->getRequestUrl($data, $action);
        $requestHeaders = $this->getRequestHeaders($data);
        $data['amount'] =  RequestAdapter::formatAmount($data['amount'], $data['currency']);

        $requestBody = json_encode(RequestAdapter::assignValue(self::MODIFICATION_REQUEST_MAPPER, $data));

        $this->logger->addDebug("ADYEN " . strtoupper($action) . " REQUEST:");
        $this->logger->addDebug(print_r($requestBody, true));

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $requestBody);

        return $this->handleModificationResponse($response, $action);
    }

    /**
     * Gets request url based on the request type and environment
     *
     * @param  array  $data
     * @param  string $action
     * @return string
     */
    protected function getRequestUrl($data, $action)
    {
        $requestUrl = self::SANDBOX_URLS[$action];
        if ($data['gatewayCredentials.environment'] == 'production') {
            if (!isset($data['gatewayCredentials.live_endpoint_url_prefix'])) {
                throw new BadFunctionCallException("Live endpoint url prefix not provided");
            }
            $requestUrl = "https://" . $data['gatewayCredentials.live_endpoint_url_prefix'] . self::PRODUCTION_URLS[$action];
        }
        return $requestUrl;
    }

    /**
     * Gets headers for request
     *
     * @param  array $data
     * @return array
     */
    protected function getRequestHeaders($data)
    {
        return ["content-type: application/json",
                "x-api-key: " . $data["gatewayCredentials.api_key"]
                ];
    }

    /**
     * Gets message based on the status code provided
     *
     * @param  string      $action
     * @param  string|null $status
     * @return string
     */
    public function getStatus($action, $status = null)
    {
        if ($status) {
            if (!isset(self::STATUS_MAPPER[$action][$status])) {
                return null;
            }
            return self::STATUS_MAPPER[$action][$status];
        }
        if (!isset(self::STATUS_MAPPER[$action])) {
            return null;
        }

        return self::STATUS_MAPPER[$action];
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
        if ($status < 200 || $status > 299) {
            if (empty($response) || !isset($response['message'])) {
                return ['isValid' => false, 'errorMessage' => $this->getStatusMessage($status)];
            } else {
                return ['isValid' => false, 'errorMessage' => $response['message']];
            }
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
        $this->logger->addDebug("ADYEN " . strtoupper($action) . " RESPONSE:");
        $this->logger->addDebug(print_r($param, true));
        $validationResponse = $this->validateResponse($param);
        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }

        $httpResponse = $validationResponse['body'];

        $result['isValid'] = true;
        $result['transactionId'] = $httpResponse['pspReference'];
        $status = $httpResponse['resultCode'];
        if (isset($httpResponse['refusalReason'])) {
            $result['isValid'] = false;
            $result['errorMessage'] = 'Transaction ' . $status . ': ' . $httpResponse['refusalReason'];
        }
        $result['status'] = $this->getStatus($action, $status);
        if (!$result['status']) {
            $result['isValid'] = false;
            $result['errorMessage'] = "Adyen response status currently not supported: " . $status;
        }
        if ($status === "RedirectShopper") {
            if (!isset($result['paReq']) || !isset($result['md']) || !isset($result['issuerUrl'])) {
                return ['isValid' => false, 'errorMessage' => "3D Secure params missing in redirect response"];
            }
            $result['threeDSInfo']['paReq'] = $httpResponse['PaReq'];
            $result['threeDSInfo']['md'] = $httpResponse['MD'];
            $result['threeDSInfo']['issuerUrl'] = $httpResponse['issuerUrl'];
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
        $this->logger->addDebug("ADYEN " . strtoupper($action) . " RESPONSE:");
        $this->logger->addDebug(print_r($param, true));

        $validationResponse = $this->validateResponse($param);

        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }

        $httpResponse = $validationResponse['body'];
        $result['isValid'] = true;
        if (isset($httpResponse['pspReference'])) {
            $result['transactionId'] = $httpResponse['pspReference'];
        }
        $result['status'] = $this->getStatus($action);

        return $result;
    }
}
