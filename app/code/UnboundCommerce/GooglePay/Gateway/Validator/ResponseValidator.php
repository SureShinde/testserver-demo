<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Gateway\Validator;

use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

class ResponseValidator extends AbstractValidator
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * Constructor for ResponseValidator
     *
     * @param ResultInterfaceFactory $resultFactory
     * @param Logger                 $logger
     */
    public function __construct(
        ResultInterfaceFactory $resultFactory,
        Logger $logger
    ) {
        $this->logger = $logger;
        parent::__construct($resultFactory);
    }

    /**
     * Validates authorize or sale response
     *
     * @param  array $validationSubject
     * @return \Magento\Payment\Gateway\Validator\ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = $validationSubject['response'];

        $isValid = $response['isValid'] ?? false;
        $messages = [];

        if (isset($response['errorMessage'])) {
            $messages[] = $response['errorMessage'];
        } elseif (!isset($response['status'])) {
            $messages[] = "Invalid response";
        } elseif (Status::transactionFailed($response['status'])) {
            $messages[] = Status::STATUS_COMMENT_MAPPER[$response['status']];
        } elseif (!isset($response['transactionId'])) {
            $messages[] = "Invalid response";
        }

        if (count($messages) > 0) {
            $isValid = false;
            $errorMessage = is_string($messages[0]) ? $messages[0] : print_r($messages[0], true);
            if (isset($response['transactionId'])) {
                $errorMessage = $errorMessage . " Transaction ID: " . $response['transactionId'];
            }
            $this->logger->addError($errorMessage);
        }

        return $this->createResult($isValid, $messages);
    }
}
