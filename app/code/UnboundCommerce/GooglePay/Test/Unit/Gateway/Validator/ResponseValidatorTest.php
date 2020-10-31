<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Test\Unit\Gateway\Validator;

use Magento\Payment\Gateway\Validator\Result;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use UnboundCommerce\GooglePay\Gateway\Validator\ResponseValidator;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Tests \UnboundCommerce\GooglePay\Gateway\Validator\ResponseValidator
 */
class ResponseValidatorTest extends TestCase
{
    /**
     * @var ResponseValidator
     */
    private $responseValidator;

    /**
     * @var ResultInterfaceFactory|MockObject
     */
    private $resultFactoryMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    /**
     * @var ResultInterface|MockObject
     */
    private $resultMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->loggerMock =  $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->setMethods(['addError'])
            ->getMock();

        $this->resultFactoryMock = $this->getMockBuilder(ResultInterfaceFactory::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseValidator = new ResponseValidator(
            $this->resultFactoryMock,
            $this->loggerMock
        );

        $this->resultMock = $this->getMockForAbstractClass(ResultInterface::class);
    }

    /**
     * @param        array   $response
     * @param        boolean $isValid
     * @param        array   $messages
     * @param        string  $loggerMessage
     * @dataProvider validateDataProvider
     */
    public function testValidate($response, $isValid, $messages, $loggerMessage)
    {
        $validationSubject = ['response' => $response];

        $result = new Result($isValid, $messages);

        $this->resultFactoryMock->method('create')
            ->with(
                [
                'isValid' => $isValid,
                'failsDescription' => $messages,
                'errorCodes' => []
                ]
            )
            ->willReturn($result);

        if (count($messages) > 0) {
            $this->loggerMock->expects(static::once())
                ->method('addError')
                ->with($loggerMessage);
        }

        if ($isValid) {
            $this->loggerMock->expects(static::never())
                ->method('addError');
        }

        $validatorResult = $this->responseValidator->validate($validationSubject);

        $this->assertSame($result, $validatorResult);
    }

    /**
     * @return array
     */
    public function validateDataProvider()
    {
        $statusMessage = Status::STATUS_COMMENT_MAPPER[Status::SALE_FAILED];
        return [
            [
                [
                    'isValid' => true,
                    'transactionId' => '001',
                    'status' => Status::SALE_SUCCEEDED
                ],
                true,
                [],
                null
            ],
            [
                [
                    'isValid' => true,
                    'transactionId' => '002',
                    'status' => null
                ],
                false,
                ['Invalid response'],
                'Invalid response Transaction ID: 002'
            ],
            [
                [
                    'isValid' => true,
                    'transactionId' => null,
                    'status' => Status::SALE_FAILED
                ],
                false,
                [$statusMessage],
                $statusMessage
            ],
            [
                [
                    'isValid' => false,
                    'transactionId' => '003',
                    'status' =>  Status::SALE_FAILED,
                    'errorMessage' => 'Authentication failed'
                ],
                false,
                ['Authentication failed'],
                'Authentication failed Transaction ID: 003'
            ]
        ];
    }
}
