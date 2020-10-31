<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\worldpay;

use UnboundCommerce\GooglePay\Service\Gateway\Helper\Array2XML;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\RequestAdapter;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\XML2Array;
use UnboundCommerce\GooglePay\Service\Gateway\HttpClient;

class Client
{
    const GATEWAY_REQUIRED_PARAMS = ['gatewayCredentials.environment', 'gatewayCredentials.gateway_merchant_id', 'gatewayCredentials.username', 'gatewayCredentials.password'];

    const AUTHORIZE_REQUEST_PARAMS = ['orderId', 'amount', 'paymentToken', 'currency', 'customerEmail'];

    const MODIFY_REQUEST_PARAMS = ['originalReference', 'orderId'];

    const AMOUNT_REQUIRED_PARAMS = ['amount', 'currency'];

    const SANDBOX_URL = "https://secure-test.worldpay.com/jsp/merchant/xml/paymentService.jsp";

    const PRODUCTION_URL = "https://secure.worldpay.com/jsp/merchant/xml/paymentService.jsp";

    const SALE_CAPTURE_DELAY = 0;

    const KEY_CAPTURE = 'capture';

    const KEY_REFUND = 'refund';

    const KEY_CANCEL = 'cancel';

    const STATUS_MAPPER = ['AUTHORISED' => Status::AUTH_FAILED,
        'REFUSED' => Status::AUTH_FAILED,
        'ERROR' => Status::AUTH_FAILED,
        'SENT_FOR_AUTHORISATION' => Status::AUTH_FAILED,
        'CAPTURED' => Status::AUTH_FAILED,
        'SETTLED_BY_MERCHANT' => Status::AUTH_FAILED,
        'CANCELLED' => Status::AUTH_FAILED,
        'EXPIRED' => Status::AUTH_FAILED,
        'SENT_FOR_REFUND' => Status::AUTH_FAILED,
        'REFUNDED_BY_MERCHANT' => Status::AUTH_FAILED,
        'REFUND_FAILED' => Status::AUTH_FAILED,
        'IN_PROCESS_AUTHORISED' => Status::AUTH_FAILED,
        'IN_PROCESS_CAPTURED' => Status::AUTH_FAILED,
        'SETTLED' => Status::AUTH_FAILED,
        'REFUNDED' => Status::AUTH_FAILED,
        'INFORMATION_REQUESTED' => Status::AUTH_FAILED,
        'INFORMATION_SUPPLIED' => Status::AUTH_FAILED,
        'CHARGED_BACK' => Status::AUTH_FAILED,
        'DISPUTE_EXPIRED' => Status::AUTH_FAILED,
        'DISPUTE_RESERVE_RELEASED' => Status::AUTH_FAILED,
        'CHARGEBACK_REVERSED' => Status::AUTH_FAILED
    ];

    private $httpClient;

    private $authorizeRequest = [
                                            '@attributes' => [
                                                'version' => '1.4',
                                                'merchantCode' => null,
                                            ],
                                            'submit' => [
                                                'order' => [
                                                    '@attributes' => [
                                                        'orderCode' => null,
                                                        'shopperLanguageCode' => 'en',
                                                        'captureDelay' => 'OFF'
                                                    ],
                                                    'description' => null,
                                                    'amount' => [
                                                        '@attributes' => [],
                                                    ],
                                                    'paymentDetails' => [
                                                        'PAYWITHGOOGLE-SSL' => [
                                                            'protocolVersion'=> null,
                                                            'signature'=> null,
                                                            'signedMessage'=> null
                                                        ],
                                                    ],
                                                    'shopper' => [
                                                        'shopperEmailAddress' => null
                                                    ]
                                                ]
                                            ]
                                        ];

    private $modifyRequest = [
                                        '@attributes' => [
                                            'version' => '1.4',
                                            'merchantCode' => '',
                                        ],
                                        'modify' => [
                                            'orderModification' => [
                                                '@attributes' => [
                                                    'orderCode' => ''
                                                ]
                                            ],
                                        ]
                                    ];

    private $orderModificationArray = [
    //                                        '@attributes' => [
    //                                            'reference'=> '',
    //                                        ],
                                        'amount' => [
                                            '@attributes' => [],
                                        ],
                                    ];

    private $amountAttributes = ['value'=> null,
                                'currencyCode'=> null,
                                'exponent'=> null,
                                'debitCreditIndicator' => 'credit'
                                ];

    private function getStatusMessage($statusCode)
    {
        switch ($statusCode) {
        case 200:
            $message = "Request processed normally";
            break;
        case 400:
            $message = "Problem reading or understanding request";
            break;
        case 401:
            $message = "Authentication error";
            break;
        case 403:
            $message = "Insufficient permission to process request";
            break;
        case 404:
            $message = "File Not Found";
            break;
        case 422:
            $message = "Request validation error";
            break;
        case 500:
            $message = "Server could not process request";
            break;
        default:
            $message = "Request Failed";
            break;
        }
        return $message;
    }

    private function getErrorMessage($errorCode)
    {
        switch ($errorCode) {
        case "1":
            $message = "The request couldn’t be processed - please contact Worldpay for further assistance.";
            break;
        case "2":
            $message = "Parse error, invalid XML";
            break;
        case "3":
            $message = "The amount is invalid.";
            break;
        case "4":
            $message = "Security violation";
            break;
        case "5":
            $message = "The contents of the order element are invalid.";
            break;
        case "7":
            $message = "The contents of the paymentDetails element are invalid.";
            break;
        case "8":
            $message = "Service not available.";
            break;
        default:
            $message = "Request Failed";
            break;
        }
        return $message;
    }

    public function __construct()
    {
        $this->httpClient = new HttpClient();
    }

    public function authorize($data, $capturePayment = false)
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        RequestAdapter::validateArray(self::AUTHORIZE_REQUEST_PARAMS, $data);
        $requestUrl = $this->getRequestUrl($data['gatewayCredentials.environment']);
        $requestHeaders = $this->getRequestHeaders();
        $requestAuth = $this->getRequestAuth($data);
        $this->authorizeRequest['@attributes']['merchantCode'] = $data['gatewayCredentials.gateway_merchant_id'];
        $this->authorizeRequest['submit']['order']['@attributes']['orderCode'] = $data['orderId'] . "_A" . rand(0, 99);
        if ($capturePayment) {
            $this->authorizeRequest['submit']['order']['@attributes']['captureDelay'] = self::SALE_CAPTURE_DELAY;
        }
        $this->authorizeRequest['submit']['order']['description'] = "GooglePay Order " . $data['orderId'];
        $this->authorizeRequest['submit']['order']['amount']['@attributes'] = $this->getAmountAttributes($data);
        $this->authorizeRequest['submit']['order']['paymentDetails']['PAYWITHGOOGLE-SSL'] = $this->getPaymentToken($data['paymentToken']);
        $this->authorizeRequest['submit']['order']['shopper']['shopperEmailAddress'] = $data['customerEmail'];

        $requestBody = Array2XML::createXML('paymentService', $this->authorizeRequest);

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $requestBody, $requestAuth);

        return $this->handleAuthorizeResponse($response);
    }

    public function modify($data, $action)
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        RequestAdapter::validateArray(self::MODIFY_REQUEST_PARAMS, $data);
        $requestUrl = $this->getRequestUrl($data['gatewayCredentials.environment']);
        $requestHeaders = $this->getRequestHeaders();
        $requestAuth = $this->getRequestAuth($data);
        $this->modifyRequest['@attributes']['merchantCode'] = $data['gatewayCredentials.gateway_merchant_id'];
        $this->modifyRequest['modify']['orderModification']['@attributes']['orderCode'] = $data['originalReference'];

        if ($action != self::KEY_CANCEL) {
            RequestAdapter::validateArray(self::AMOUNT_REQUIRED_PARAMS, $data);
            $this->orderModificationArray['amount']['@attributes'] = $this->getAmountAttributes($data);
            $this->modifyRequest['modify']['orderModification'][$action] = $this->orderModificationArray;
        } else {
            $this->modifyRequest['modify']['orderModification'][$action] = null;
        }

        $requestBody = Array2XML::createXML('paymentService', $this->modifyRequest);

        $response = $this->httpClient->post($requestUrl, $requestHeaders, $requestBody, $requestAuth);

        return $this->handleModifyResponse($response, $action);
    }

    public function capture($data)
    {
        return $this->modify($data, self::KEY_CAPTURE);
    }

    public function sale($data)
    {
        return $this->authorize($data, true);
    }

    public function refund($data)
    {
        return $this->modify($data, self::KEY_REFUND);
    }

    public function void($data)
    {
        return $this->modify($data, self::KEY_CANCEL);
    }

    private function getRequestUrl($environment)
    {
        $requestUrl = self::SANDBOX_URL;
        if ($environment == 'production') {
            $requestUrl = self::PRODUCTION_URL;
        }
        return $requestUrl;
    }

    private function getRequestHeaders()
    {
        return ["content-type: text/xml"];
    }

    private function getRequestAuth($data)
    {
        return $data['gatewayCredentials.username'] . ":" . $data['gatewayCredentials.password'];
    }

    private function getAmountAttributes($data)
    {
        $this->amountAttributes['value'] = str_replace(["."], "", $data['amount']);
        $valueArray = explode(".", $data['amount'], 2);
        if (count($valueArray)>1) {
            $this->amountAttributes['exponent'] = strlen($valueArray[1]);
        } else {
            $this->amountAttributes['exponent'] = 0;
        }
        $this->amountAttributes['currencyCode'] = $data['currency'];
        return $this->amountAttributes;
    }

    private function getPaymentToken($tokenJson)
    {
        $paymentToken = [];
        $tokenArray = json_decode($tokenJson, true);
        if (isset($tokenArray['protocolVersion'])) {
            $paymentToken['protocolVersion'] = $tokenArray['protocolVersion'];
        }
        if (isset($tokenArray['signature'])) {
            $paymentToken['signature'] = $tokenArray['signature'];
        }
        if (isset($tokenArray['signedMessage'])) {
            $paymentToken['signedMessage'] = $tokenArray['signedMessage'];
        }
        return $paymentToken;
    }

    private function validateResponse($param)
    {
        $status = $param['statusCode'];
        $response = null;
        if ($param['contentType'] == 'text/xml') {
            $response = XML2Array::createArray($param['httpResponse']);
        }
        if ($status != 200) {
            if (empty($response) || !isset($response['paymentService']['reply']['orderStatus']['error'])) {
                return ['isValid' => false, 'errorMessage' => $this->getStatusMessage($status)];
            } else {
                $error = $response['paymentService']['reply']['orderStatus']['error'];
                if (isset($error['@cdata'])) {
                    $message = $error['@cdata'];
                } else {
                    $code = $error['@attributes']['code'] ?? "";
                    $message = $this->getErrorMessage($code);
                }
                return ['isValid' => false, 'errorMessage' => $message];
            }
        } elseif ($param['contentType'] != 'text/xml') {
            return ['isValid' => false, 'errorMessage' => "Problem reading or understanding response"];
        }

        return ['isValid' => true, 'body'=>$response];
    }

    private function handleAuthorizeResponse($param)
    {
        $validationResponse = $this->validateResponse($param);
        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }
        $result['isValid'] = true;
        $orderStatus = $param['paymentService']['reply']['orderStatus'];
        $result['transactionId'] = $orderStatus['@attributes']['orderCode'];
        if (isset($orderStatus['payment']['lastEvent'])) {
            $result['status'] = self::STATUS_MAPPER[$orderStatus['payment']['lastEvent']];
        }
        if (isset($orderStatus['payment']['ISO8583ReturnCode']['@attributes']['description'])) {
            $result['refusalReason'] = $orderStatus['payment']['ISO8583ReturnCode']['@attributes']['description'];
        }

        return $result;
    }

    private function handleModifyResponse($param, $action)
    {
        $responseMapper = [
            self::KEY_CAPTURE => 'captureReceived',
            self::KEY_REFUND => 'refundReceived',
            self::KEY_CANCEL => 'cancelReceived'
        ];

        $validationResponse = $this->validateResponse($param);
        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }
        $node = $responseMapper[$action];
        $result['isValid'] = true;

        if (isset($param['paymentService']['reply']['ok'][$node]['@attributes']['orderCode'])) {
            $result['transactionId'] = $param['paymentService']['reply']['ok'][$node]['@attributes']['orderCode'] . "_" . time();
            $result['status'] = 'processing';
        } else {
            $result['status'] = 'canceled';
        }

        return $result;
    }
}
