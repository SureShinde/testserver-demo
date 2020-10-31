<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use UnboundCommerce\GooglePay\Gateway\Response\AuthorizationHandler;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Tests \UnboundCommerce\GooglePay\Gateway\Response\AuthorizationHandler
 */
class AuthorizationHandlerTest extends TestCase
{

    /**
     * @var AuthorizationHandler
     */
    private $authorizationHandler;

    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDOMock;

    /**
     * @var Logger|MockObject
     */
    private $loggerMock;

    protected function setUp()
    {
        $this->loggerMock =  $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->setMethods(['addInfo'])
            ->getMock();

        $this->paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                'setTransactionId',
                'setIsTransactionPending',
                'setAdditionalInformation',
                'addTransactionCommentsToOrder',
                'setIsTransactionClosed'
                ]
            )
            ->getMock();

        $this->paymentDOMock->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->paymentMock->expects(static::once())
            ->method('setTransactionId');
        $this->paymentMock->expects(static::any())
            ->method('setAdditionalInformation');
        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionClosed');

        $this->authorizationHandler = new AuthorizationHandler($this->loggerMock);
    }

    /**
     * @covers AuthorizationHandler::handle
     */
    public function testHandleForSuccess()
    {
        $handlingSubject = ['payment' => $this->paymentDOMock];

        $response = [
            AuthorizationHandler::TRANSACTION_ID => '001',
            AuthorizationHandler::TRANSACTION_STATUS => Status::AUTH_SUCCEEDED,
            AuthorizationHandler::TRANSACTION_INFO => ['testKey' => 'testValue'],
            AuthorizationHandler::COMMENT => 'AuthorizationHandler test call'
        ];

        $this->paymentMock->expects(static::never())
            ->method('setIsTransactionPending');
        $this->paymentMock->expects(static::once())
            ->method('addTransactionCommentsToOrder');

        $this->authorizationHandler->handle($handlingSubject, $response);
    }

    /**
     * @covers AuthorizationHandler::handle
     */
    public function testHandleForPending()
    {
        $handlingSubject = ['payment' => $this->paymentDOMock];

        $response = [
            AuthorizationHandler::TRANSACTION_ID => '002',
            AuthorizationHandler::TRANSACTION_STATUS => Status::AUTH_PENDING
        ];

        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionPending');
        $this->paymentMock->expects(static::never())
            ->method('addTransactionCommentsToOrder');
        $this->authorizationHandler->handle($handlingSubject, $response);
    }
}
