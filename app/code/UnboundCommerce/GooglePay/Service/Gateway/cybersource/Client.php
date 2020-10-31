<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\cybersource;

use CyberSource\Api\CaptureApi;
use CyberSource\Api\PaymentsApi;
use CyberSource\Api\RefundApi;
use CyberSource\Api\ReportDownloadsApi;
use CyberSource\Api\ReversalApi;
use CyberSource\Api\TransactionDetailsApi;
use CyberSource\ApiClient;
use Cybersource\ApiException;
use CyberSource\Model\AuthReversalRequest;
use CyberSource\Model\CapturePaymentRequest;
use CyberSource\Model\CreatePaymentRequest;
use CyberSource\Model\Ptsv2paymentsClientReferenceInformation;
use CyberSource\Model\Ptsv2paymentsidreversalsReversalInformation;
use CyberSource\Model\Ptsv2paymentsidreversalsReversalInformationAmountDetails;
use CyberSource\Model\Ptsv2paymentsOrderInformation;
use CyberSource\Model\Ptsv2paymentsOrderInformationAmountDetails;
use CyberSource\Model\Ptsv2paymentsOrderInformationBillTo;
use CyberSource\Model\Ptsv2paymentsPaymentInformation;
use CyberSource\Model\Ptsv2paymentsPaymentInformationFluidData;
use CyberSource\Model\Ptsv2paymentsProcessingInformation;
use CyberSource\Model\RefundPaymentRequest;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\ClientInterface;
use UnboundCommerce\GooglePay\Service\Gateway\cybersource\Resources\ExternalConfiguration;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\RequestAdapter;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Class CyberSource Client
 */
class Client implements ClientInterface
{
    const GATEWAY_REQUIRED_PARAMS = ['gatewayCredentials.environment', 'gatewayCredentials.gateway_merchant_id', 'gatewayCredentials.api_key', 'gatewayCredentials.secret_key'];

    const AUTHORIZE_REQUEST_PARAMS = ['amount', 'currency', 'orderId', 'paymentToken', 'lastName', 'customerEmail', 'streetLine1', 'city', 'region', 'country'];

    const MODIFY_REQUEST_PARAMS = ['amount', 'currency', 'orderId', 'originalReference'];

    const RETRIEVE_TRANSACTION_PARAMS = ['transactionId'];

    const STATUS_MAPPER = ['authorise' => [
                                'AUTHORIZED' => Status::AUTH_SUCCEEDED,
                                'PARTIAL_AUTHORIZED' => Status::AUTH_SUCCEEDED,
                                'AUTHORIZEDPENDINGREVIEW' => Status::AUTH_PENDING,
                                'PENDING' => Status::AUTH_PENDING,
                                'DECLINED' => Status::AUTH_FAILED,
                                'INVALID_REQUEST' => Status::AUTH_FAILED
                                ],
                            'sale' => [
                                'AUTHORIZED' => Status::SALE_RECEIVED,
                                'PARTIAL_AUTHORIZED' => Status::SALE_RECEIVED,
                                'AUTHORIZEDPENDINGREVIEW' => Status::SALE_PENDING,
                                'PENDING' => Status::SALE_PENDING,
                                'DECLINED' => Status::SALE_FAILED,
                                'INVALID_REQUEST' => Status::SALE_FAILED,
                                'BATCHED' => Status::SALE_SUCCEEDED,
                                'BATCH_ERROR' => Status::SALE_FAILED,
                                'BATCH_RESET' => Status::SALE_SUCCEEDED,
                                'TRANSMITTED' => Status::SALE_SUCCEEDED,
                                'CANCELLED' => Status::SALE_FAILED,
                                'DENIED' => Status::SALE_FAILED,
                                'FAILED' => Status::SALE_FAILED,
                                'TRXN_ERROR' => Status::SALE_FAILED,
                                'ERROR' => Status::SALE_FAILED
                            ],
                            'capture' => [
                                'PENDING' => Status::CAPTURE_PENDING,
                                'BATCHED' => Status::CAPTURE_SUCCEEDED,
                                'BATCH_ERROR' => Status::CAPTURE_FAILED,
                                'BATCH_RESET' => Status::CAPTURE_SUCCEEDED,
                                'TRANSMITTED' => Status::CAPTURE_SUCCEEDED,
                                'CANCELLED' => Status::CAPTURE_FAILED,
                                'DENIED' => Status::CAPTURE_FAILED,
                                'FAILED' => Status::CAPTURE_FAILED,
                                'TRXN_ERROR' => Status::CAPTURE_FAILED,
                                'ERROR' => Status::CAPTURE_FAILED
                            ],
                            'refund' => [
                                'PENDING' => Status::REFUND_PENDING,
                                'BATCHED' => Status::REFUND_SUCCEEDED,
                                'BATCH_ERROR' => Status::REFUND_FAILED,
                                'BATCH_RESET' => Status::REFUND_SUCCEEDED,
                                'TRANSMITTED' => Status::REFUND_SUCCEEDED,
                                'CANCELLED' => Status::REFUND_FAILED,
                                'DENIED' => Status::REFUND_FAILED,
                                'FAILED' => Status::REFUND_FAILED,
                                'TRXN_ERROR' => Status::REFUND_FAILED,
                                'ERROR' => Status::REFUND_FAILED
                            ],
                            'void' => [
                                'REVERSED' => Status::VOID_SUCCEEDED
                            ]
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
        $apiClient = $this->getApiClient($data);
        $apiInstance = new PaymentsApi($apiClient);
        $clientRef = ['code' => $data['orderId']];
        $clientReferenceInformation = new Ptsv2paymentsClientReferenceInformation($clientRef);

        $amountDetails = [
            'totalAmount' => $data['amount'],
            'currency' => $data['currency']
        ];

        $amountDetailsInfo = new Ptsv2paymentsOrderInformationAmountDetails($amountDetails);

        $billToArray['email'] = $data['customerEmail'];
        $billToArray['lastName'] = $data['lastName'];
        $billToArray['address1'] = $data['streetLine1'];
        $billToArray['locality'] = $data['city'];
        $billToArray['administrativeArea'] = $data['region'];
        $billToArray['country'] = $data['country'];

        if (isset($data['firstName'])) {
            $billToArray['firstName'] = $data['firstName'];
        }
        if (isset($data['streetLine2'])) {
            $billToArray['address2'] = $data['streetLine2'];
        }
        if (isset($data['postalCode'])) {
            $billToArray['postalCode'] = $data['postalCode'];
        }

        $billTo = new Ptsv2paymentsOrderInformationBillTo($billToArray);

        $orderInfoArray = [
            "amountDetails" => $amountDetailsInfo,
            "billTo" => $billTo
        ];
        $orderInformation = new Ptsv2paymentsOrderInformation($orderInfoArray);

        $fluidDataInfo = [
            "value" => base64_encode($data['paymentToken'])
        ];

        $fluidData = new Ptsv2paymentsPaymentInformationFluidData($fluidDataInfo);

        $paymentInfoArray = [
            "fluidData" => $fluidData
        ];

        $processingInformationArray = [
            "capture" => $capturePayment,
            "paymentSolution" => "012"
        ];

        $processingInformation = new Ptsv2paymentsProcessingInformation($processingInformationArray);

        $paymentInformation = new Ptsv2paymentsPaymentInformation($paymentInfoArray);
        $paymentRequestArray = [
            "clientReferenceInformation" => $clientReferenceInformation,
            "orderInformation" => $orderInformation,
            "paymentInformation" => $paymentInformation,
            "processingInformation" => $processingInformation
        ];

        $action = $capturePayment ? 'sale' : 'authorise';
        $paymentRequest = new CreatePaymentRequest($paymentRequestArray);

        $this->logger->addDebug("CYBERSOURCE " . strtoupper($action) . " REQUEST:");
        $this->logger->addDebug(print_r($paymentRequest, true));

        try {
            $apiResponse = $apiInstance->createPayment($paymentRequest);
        } catch (ApiException $e) {
            $result = ['isValid' => false, 'errorMessage' => $e->getMessage()];
            $this->logger->addDebug("CYBERSOURCE " . strtoupper($action) . " RESPONSE: ");
            $this->logger->addDebug(print_r($e->getResponseBody(), true));
            return $result;
        }

        return $this->handleResponse($apiResponse, $action);
    }

    /**
     * Processes capture request
     *
     * @param  array $data
     * @return array
     */
    public function capture($data)
    {
        RequestAdapter::validateArray(self::MODIFY_REQUEST_PARAMS, $data);
        $apiClient = $this->getApiClient($data);
        $apiInstance = new CaptureApi($apiClient);
        $clientRef = ['code' => $data['orderId']];
        $clientReferenceInformation = new Ptsv2paymentsClientReferenceInformation($clientRef);

        $amountDetails = [
            'totalAmount' => $data['amount'],
            'currency' => $data['currency']
        ];

        $amountDetailsInfo = new Ptsv2paymentsOrderInformationAmountDetails($amountDetails);

        $orderInfoArray = [
            "amountDetails" => $amountDetailsInfo
        ];

        $orderInformation = new Ptsv2paymentsOrderInformation($orderInfoArray);

        $requestArray = [
            "clientReferenceInformation" => $clientReferenceInformation,
            "orderInformation" => $orderInformation
        ];
        //Creating model
        $request = new CapturePaymentRequest($requestArray);
        $this->logger->addDebug("CYBERSOURCE CAPTURE REQUEST:");
        $this->logger->addDebug(print_r($request, true));
        try {
            //Calling the Api
            $apiResponse = $apiInstance->capturePayment($request, $data['originalReference']);
        } catch (ApiException $e) {
            $result = ['isValid' => false, 'errorMessage' => $e->getMessage()];
            $this->logger->addDebug("CYBERSOURCE CAPTURE RESPONSE: ");
            $this->logger->addDebug(print_r($e->getResponseBody(), true));
            return $result;
        }
        return $this->handleResponse($apiResponse, 'capture');
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
        $apiClient = $this->getApiClient($data);
        $apiInstance = new RefundApi($apiClient);
        $clientRef = ['code' => $data['orderId']];
        $clientReferenceInformation = new Ptsv2paymentsClientReferenceInformation($clientRef);

        $amountDetails = [
            'totalAmount' => $data['amount'],
            'currency' => $data['currency']
        ];

        $amountDetailsInfo = new Ptsv2paymentsOrderInformationAmountDetails($amountDetails);

        $orderInfoArray = [
            "amountDetails" => $amountDetailsInfo
        ];

        $orderInformation = new Ptsv2paymentsOrderInformation($orderInfoArray);

        $requestArray = [
            "clientReferenceInformation" => $clientReferenceInformation,
            "orderInformation" => $orderInformation
        ];
        //Creating model
        $request = new RefundPaymentRequest($requestArray);
        $this->logger->addDebug("CYBERSOURCE REFUND REQUEST:");
        $this->logger->addDebug(print_r($request, true));
        try {
            //Calling the Api
            $apiResponse = $apiInstance->refundPayment($request, $data['originalReference']);
        } catch (ApiException $e) {
            $result = ['isValid' => false, 'errorMessage' => $e->getMessage()];
            $this->logger->addDebug("CYBERSOURCE REFUND RESPONSE: ");
            $this->logger->addDebug(print_r($e->getResponseBody(), true));
            return $result;
        }
        return $this->handleResponse($apiResponse, 'refund');
    }

    /**
     * Processes void request
     *
     * @param  array $data
     * @return array
     */
    public function void($data)
    {
        //Creating model
        RequestAdapter::validateArray(self::MODIFY_REQUEST_PARAMS, $data);
        $apiClient = $this->getApiClient($data);
        $apiInstance = new ReversalApi($apiClient);
        $clientRef = ['code' => $data['orderId']];
        $clientReferenceInformation = new Ptsv2paymentsClientReferenceInformation($clientRef);

        $amountDetails = [
            'totalAmount' => $data['amount'],
            'currency' => $data['currency']
        ];

        $amountDetailsInfo = new Ptsv2paymentsidreversalsReversalInformationAmountDetails($amountDetails);
        $reversalInfoArray = [
            "amountDetails" => $amountDetailsInfo,
            "reason" => "Void authorization"
        ];
        $reversalInformation = new Ptsv2paymentsidreversalsReversalInformation($reversalInfoArray);
        $requestArray = [
            "clientReferenceInformation" => $clientReferenceInformation,
            "reversalInformation" => $reversalInformation
        ];

        //Creating model
        $request = new AuthReversalRequest($requestArray);
        $this->logger->addDebug("CYBERSOURCE VOID REQUEST:");
        $this->logger->addDebug(print_r($request, true));
        try {
            //Calling the Api
            $apiResponse = $apiInstance->authReversal($data['originalReference'], $request);
        } catch (ApiException $e) {
            $result = ['isValid' => false, 'errorMessage' => $e->getMessage()];
            $this->logger->addDebug("CYBERSOURCE VOID RESPONSE:");
            $this->logger->addDebug(print_r($e->getResponseBody(), true));
            return $result;
        }
        return $this->handleResponse($apiResponse, 'void');
    }

    /**
     * Gets Cybersource payment batch detail report
     *
     * @param  array $data
     * @return array
     */
    public function getPaymentBatchDetailReport($data)
    {
        $apiClient = $this->getApiClient($data);
        $apiInstance = new ReportDownloadsApi($apiClient);
        $organizationId = $data['gatewayCredentials.gateway_merchant_id'];
        $reportDate = $data['date'];
        $reportName="PaymentBatchDetailReport";

        try {
            //Calling the Api
            $apiResponse = $apiInstance->downloadReport($reportDate->format('Y-m-d'), $reportName, $organizationId);
            if (count($apiResponse) < 2 || $apiResponse[1] < 200 || $apiResponse[1] > 299) {
                return ['isValid' => false];
            }
            $result =  ['isValid' => true, 'paymentBatchDetailReport' => $apiResponse[0]];
        } catch (ApiException $e) {
            $result = ['isValid' => false, 'errorMessage' => $e->getMessage()];
            $this->logger->addDebug("Could not retrieve Cybersource Payment Batch Detail Report");
            $this->logger->addDebug(print_r($e->getResponseBody(), true));
        }
        return $result;
    }

    /**
     * Retrieves a transaction by transaction id
     *
     * @param  array $data
     * @return array
     */
    public function retrieveTransaction($data)
    {
        try {
            $this->logger->addDebug("RETRIEVING CYBERSOURCE TRANSACTION: " . $data['transactionId']);
            $apiClient = $this->getApiClient($data);
            $apiInstance = new TransactionDetailsApi($apiClient);
            RequestAdapter::validateArray(self::RETRIEVE_TRANSACTION_PARAMS, $data);
            $apiResponse = $apiInstance->getTransaction($data['transactionId']);
            //            $apiResponse = $apiInstance->getTransaction('5588006279316826804005');
            $this->logger->addDebug(print_r($apiResponse, true));
            if (count($apiResponse) < 2 || $apiResponse[1] < 200 || $apiResponse[1] > 299) {
                return ['isValid' => false];
            }
            $result =  ['isValid' => true, 'transaction' => $apiResponse[0]];
        } catch (ApiException $e) {
            $result = ['isValid' => false, 'errorMessage' => $e->getMessage()];
            $this->logger->addDebug("Could not retrieve Cybersource transaction");
            $this->logger->addDebug(print_r($e->getResponseBody(), true));
            return $result;
        }
        return $result;
    }

    /**
     * Creates Cybersource API client
     *
     * @param  array $data
     * @return ApiClient
     */
    protected function getApiClient($data)
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        $externalConfig = new ExternalConfiguration();
        $externalConfig->merchantID = $data['gatewayCredentials.gateway_merchant_id'];
        $externalConfig->apiKeyID = $data['gatewayCredentials.api_key'];
        $externalConfig->secretKey = $data['gatewayCredentials.secret_key'];
        $environment = "SANDBOX";
        if ($data['gatewayCredentials.environment'] == 'production') {
            $environment = "PRODUCTION";
        }
        $externalConfig->runEnv = "cyberSource.environment." . $environment;
        $config = $externalConfig->ConnectionHost();
        $merchantConfig = $externalConfig->merchantConfigObject();
        $apiClient = new ApiClient($config, $merchantConfig);
        return $apiClient;
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
        $response = $param[0];
        $statusCode = $param[1];
        $result['isValid'] = true;
        if (isset($response['errorInformation'])) {
            $result['isValid'] = false;
            $errorInfo = $response['errorInformation'];
            $result['errorMessage'] = "";
            if (isset($errorInfo['message'])) {
                $result['errorMessage'] = $errorInfo['message'];
            }
            if (isset($errorInfo['reason'])) {
                $result['errorMessage'] = $result['errorMessage'] . ' ' . $errorInfo['reason'];
            }
            return $result;
        }
        if ($statusCode < 200 || $statusCode > 299) {
            $result['isValid'] = false;
            if (isset($response['message'])) {
                $result['errorMessage'] = $response['message'];
            }
        }
        return $result;
    }

    /**
     * Handles response
     *
     * @param  array       $param
     * @param  string|null $action
     * @return array
     */
    protected function handleResponse($param, $action = null)
    {
        $debugMessage = $action ?? '';
        $this->logger->addDebug("CYBERSOURCE " . strtoupper($debugMessage) . " RESPONSE:");
        $this->logger->addDebug(print_r($param, true));

        $validationResponse = $this->validateResponse($param);
        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }

        $response = $param[0];
        $result['isValid'] = true;
        $result['transactionId'] = $response['id'];
        $status = $response['status'];
        $result['status'] = $status;
        if ($action) {
            $result['status'] = $this->getStatus($action, $response['status']);
            if (!$result['status']) {
                $result['isValid'] = false;
                $result['errorMessage'] = "Cybersource response status currently not supported: " . $response['status'];
            }
        } else {
            $result['status'] = $response['status'];
        }

        return $result;
    }
}
