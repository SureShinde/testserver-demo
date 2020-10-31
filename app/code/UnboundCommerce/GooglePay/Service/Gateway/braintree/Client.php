<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\braintree;

use Braintree\Gateway;
use Braintree\PaymentInstrumentType;
use Braintree\Transaction;
use Braintree\TransactionSearch;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\ClientInterface;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\RequestAdapter;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Class Braintree Client
 */
class Client implements ClientInterface
{
    const GATEWAY_REQUIRED_PARAMS = ['gatewayCredentials.environment', 'gatewayCredentials.gateway_merchant_id', 'gatewayCredentials.public_key', 'gatewayCredentials.private_key'];

    const AUTHORIZE_REQUEST_PARAMS = ['amount', 'paymentToken', 'orderId'];

    const AUTHORIZE_REQUEST_MAPPER = ['amount' => 'amount',
                                    'paymentMethodNonce' => 'nonceFromTheClient',
                                    'orderId' => 'orderId',
                                    'options' => [
                                        'submitForSettlement' => 'capturePayment'
                                    ]];

    const CAPTURE_REQUEST_PARAMS = ['originalReference'];

    const REFUND_REQUEST_PARAMS = ['originalReference', 'amount'];

    const VOID_REQUEST_PARAMS = ['originalReference'];

    const FIND_TRANSACTION_REQUEST_PARAMS = ['transactionId'];

    const SEARCH_REQUEST_PARAMS = ['from', 'to'];

    const STATUS_MAPPER = ['authorise' => [
                                Transaction::AUTHORIZATION_EXPIRED => Status::AUTH_FAILED,
                                Transaction::AUTHORIZED => Status::AUTH_SUCCEEDED,
                                Transaction::AUTHORIZING => Status::AUTH_RECEIVED,
                                Transaction::FAILED => Status::AUTH_FAILED,
                                Transaction::GATEWAY_REJECTED => Status::AUTH_FAILED,
                                Transaction::VOIDED => Status::AUTH_FAILED,
                                Transaction::PROCESSOR_DECLINED => Status::AUTH_FAILED,
                            ],
                            'sale' => [
                                Transaction::AUTHORIZATION_EXPIRED => Status::SALE_FAILED,
                                Transaction::AUTHORIZED => Status::SALE_RECEIVED,
                                Transaction::AUTHORIZING => Status::SALE_RECEIVED,
                                Transaction::SETTLED => Status::SALE_SUCCEEDED,
                                Transaction::SETTLING => Status::SALE_RECEIVED,
                                Transaction::SUBMITTED_FOR_SETTLEMENT => Status::SALE_RECEIVED,
                                Transaction::SETTLEMENT_PENDING => Status::SALE_PENDING,
                                Transaction::SETTLEMENT_CONFIRMED => Status::SALE_SUCCEEDED,
                                Transaction::SETTLEMENT_DECLINED => Status::SALE_FAILED,
                                Transaction::FAILED => Status::SALE_FAILED,
                                Transaction::VOIDED => Status::SALE_FAILED,
                                Transaction::GATEWAY_REJECTED => Status::SALE_FAILED,
                                Transaction::PROCESSOR_DECLINED => Status::SALE_FAILED
                            ],
                            'capture' => [
                                Transaction::SETTLED => Status::CAPTURE_SUCCEEDED,
                                Transaction::SETTLING => Status::CAPTURE_RECEIVED,
                                Transaction::SUBMITTED_FOR_SETTLEMENT => Status::CAPTURE_RECEIVED,
                                Transaction::SETTLEMENT_PENDING => Status::CAPTURE_PENDING,
                                Transaction::SETTLEMENT_CONFIRMED => Status::CAPTURE_SUCCEEDED,
                                Transaction::SETTLEMENT_DECLINED => Status::CAPTURE_FAILED,
                                Transaction::FAILED => Status::CAPTURE_FAILED,
                                Transaction::VOIDED => Status::CAPTURE_FAILED,
                                Transaction::GATEWAY_REJECTED => Status::CAPTURE_FAILED,
                                Transaction::PROCESSOR_DECLINED => Status::CAPTURE_FAILED,

                            ],
                            'refund' => [
                                Transaction::SETTLED => Status::REFUND_SUCCEEDED,
                                Transaction::SETTLING => Status::REFUND_RECEIVED,
                                Transaction::SUBMITTED_FOR_SETTLEMENT => Status::REFUND_RECEIVED,
                                Transaction::SETTLEMENT_PENDING => Status::REFUND_PENDING,
                                Transaction::SETTLEMENT_CONFIRMED => Status::REFUND_SUCCEEDED,
                                Transaction::SETTLEMENT_DECLINED => Status::REFUND_FAILED,
                                Transaction::VOIDED => Status::REFUND_FAILED,
                                Transaction::FAILED => Status::REFUND_FAILED,
                                Transaction::GATEWAY_REJECTED => Status::REFUND_FAILED,
                                Transaction::PROCESSOR_DECLINED => Status::REFUND_FAILED,

                            ],
                            'void' => [
                                Transaction::VOIDED => Status::VOID_SUCCEEDED,
                                Transaction::FAILED => Status::VOID_FAILED
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
        $data['nonceFromTheClient'] = $this->getNonce($data['paymentToken']);
        //        $data['nonceFromTheClient'] = "a9eff8be-2b17-5555-7b78-6b3783911626";
        $data['capturePayment'] = $capturePayment;
        $action = $capturePayment ? 'sale' : 'authorise';
        $requestBody = RequestAdapter::assignValue(self::AUTHORIZE_REQUEST_MAPPER, $data);

        $this->logger->addDebug("BRAINTREE " . strtoupper($action) . " REQUEST:");
        $this->logger->addDebug(print_r($requestBody, true));

        $response = $this->getGateway($data)->transaction()->sale($requestBody);

        return $this->handleResponse($response, $action, true);
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

        $this->logger->addDebug("BRAINTREE CAPTURE REQUEST:");
        $this->logger->addDebug("Transaction ID: " . $data['originalReference']);

        $response = $this->getGateway($data)->transaction()->submitForSettlement($data['originalReference']);

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

        $this->logger->addDebug("BRAINTREE REFUND REQUEST:");
        $this->logger->addDebug("Transaction ID: " . $data['originalReference']);
        $this->logger->addDebug("Amount: " . $data['amount']);

        $response = $this->getGateway($data)->transaction()->refund($data['originalReference'], $data['amount']);

        return $this->handleResponse($response, 'refund', true);
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

        $this->logger->addDebug("BRAINTREE VOID REQUEST:");
        $this->logger->addDebug("Transaction ID: " . $data['originalReference']);

        $response = $this->getGateway($data)->transaction()->void($data['originalReference']);

        return $this->handleResponse($response, 'void');
    }

    /**
     * Creates Braintree gateway
     *
     * @param  array $data
     * @return Gateway
     */
    protected function getGateway($data)
    {
        RequestAdapter::validateArray(self::GATEWAY_REQUIRED_PARAMS, $data);
        $gateway = new Gateway(
            [
            'environment' => $data['gatewayCredentials.environment'],
            'merchantId' => $data['gatewayCredentials.gateway_merchant_id'],
            'publicKey' => $data['gatewayCredentials.public_key'],
            'privateKey' => $data['gatewayCredentials.private_key']
            ]
        );
        return $gateway;
    }

    /**
     * Gets Nonce
     *
     * @param  string $paymentToken
     * @return string
     */
    protected function getNonce($paymentToken)
    {
        $paymentTokenArray = json_decode($paymentToken, true);
        return $paymentTokenArray['androidPayCards'][0]['nonce'];
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
     * @param  \Braintree\Result\Successful|\Braintree\Result\Error $param
     * @return array
     */
    protected function validateResponse($param)
    {
        $result = ['isValid' => $param->success];
        if ($param->success) {
            return $result;
        }

        if ($param->errors->deepSize() > 0) {
            $message = "";
            foreach ($param->errors->deepAll() as $error) {
                $message = $message . " " . print_r($error->attribute . ": " . $error->code . " " . $error->message . "\n", true);
            }
            $result['errorMessage'] = $message;
        } elseif ($param->transaction->status == Transaction::SETTLEMENT_DECLINED) {
            $result['errorMessage'] = $param->transaction->processorSettlementResponseText;
        } elseif ($param->transaction->status == Transaction::PROCESSOR_DECLINED) {
            $result['errorMessage'] = $param->transaction->processorResponseText;
        } elseif ($param->transaction->status == Transaction::GATEWAY_REJECTED) {
            $result['errorMessage'] = 'RejectionReason: ' . $param->transaction->gatewayRejectionReason;
        } else {
            $result['errorMessage'] = 'Request failed';
        }
        return $result;
    }

    /**
     * Handles response
     *
     * @param  \Braintree\Result\Successful|\Braintree\Result\Error $param
     * @param  string                                               $action
     * @param  boolean|false                                        $authorizeOrRefund
     * @return array
     */
    protected function handleResponse($param, $action, $authorizeOrRefund = false)
    {
        $this->logger->addDebug("BRAINTREE " . strtoupper($action) . " RESPONSE:");
        $this->logger->addDebug(print_r($param, true));
        $validationResponse = $this->validateResponse($param);
        if (!$validationResponse['isValid']) {
            return $validationResponse;
        }

        $result['isValid'] = true;
        $result['transactionId'] = $param->transaction->id;
        $response['transactionInfo'] = $param->transaction->status;
        if (!$authorizeOrRefund) {
            $result['transactionId'] = $result['transactionId'] . "_" . time();
        }
        $result['status'] = $this->getStatus($action, $param->transaction->status);

        if (!$result['status']) {
            $result['isValid'] = false;
            $result['errorMessage'] = "Braintree response status currently not supported: " . $param->transaction->status;
        }
        return $result;
    }

    /**
     * Retrieves a Braintree transaction
     *
     * @param  array $data
     * @return array
     */
    public function retrieveTransaction($data)
    {
        try {
            RequestAdapter::validateArray(self::FIND_TRANSACTION_REQUEST_PARAMS, $data);
            $this->logger->addDebug("RETRIEVING BRAINTREE TRANSACTION: " . $data['transactionId']);
            $transaction = $this->getGateway($data)->transaction()->find($data['transactionId']);
            $this->logger->addDebug(print_r($transaction, true));
            $result['isValid'] = true;
            $result['transaction'] = $transaction;
        } catch (\Exception $e) {
            $result['isValid'] = false;
            if ($e->getMessage()) {
                $this->logger->addError($e->getMessage());
            }
            $this->logger->addError("Could not retrieve braintree transaction");
        }
        return $result;
    }

    /**
     * Gets Braintree transaction report based on transaction type and date range
     *
     * @param  array  $data
     * @param  string $transactionType
     * @return \Braintree\ResourceCollection
     */
    public function getTransactionReport($data, $transactionType)
    {
        RequestAdapter::validateArray(self::SEARCH_REQUEST_PARAMS, $data);

        switch ($transactionType) {
        case Transaction::AUTHORIZED:
            $constraintList = [TransactionSearch::authorizedAt()->between($data['from'], $data['to']),
                                TransactionSearch::status()->is(Transaction::AUTHORIZED)
                                ];
            break;
        case Transaction::AUTHORIZATION_EXPIRED:
            $constraintList = [TransactionSearch::authorizationExpiredAt()->between($data['from'], $data['to'])];
            break;
        case Transaction::SUBMITTED_FOR_SETTLEMENT:
            $constraintList = [TransactionSearch::submittedForSettlementAt()->between($data['from'], $data['to'])];
            break;
        case Transaction::SETTLED:
            $constraintList = [TransactionSearch::settledAt()->between($data['from'], $data['to'])];
            break;
        case Transaction::VOIDED:
            $constraintList = [TransactionSearch::voidedAt()->between($data['from'], $data['to'])];
            break;
        case Transaction::FAILED:
            $constraintList = [TransactionSearch::failedAt()->between($data['from'], $data['to'])];
            break;
        case Transaction::GATEWAY_REJECTED:
            $constraintList = [TransactionSearch::gatewayRejectedAt()->between($data['from'], $data['to'])];
            break;
        case Transaction::PROCESSOR_DECLINED:
            $constraintList = [TransactionSearch::processorDeclinedAt()->between($data['from'], $data['to'])];
            break;
        default:
            return null;
        }

        $paymentInstrumentConstraint = TransactionSearch::paymentInstrumentType()->is(PaymentInstrumentType::ANDROID_PAY_CARD);
        array_push($constraintList, $paymentInstrumentConstraint);
        $transactions = $this->getGateway($data)->transaction()->search($constraintList);

        return $transactions;
    }
}
