<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use UnboundCommerce\GooglePay\Gateway\Response\CaptureHandler;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Tests \UnboundCommerce\GooglePay\Gateway\Response\AuthorizationHandler
 */
class CaptureHandlerTest extends TestCase
{

    /**
     * @var CaptureHandler
     */
    private $captureHandler;

    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDOMock;

    /**
     * @var OrderRepositoryInterface|MockObject
     */
    private $orderRepositoryMock;
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
                'setShouldCloseParentTransaction',
                'setAdditionalInformation',
                'addTransactionCommentsToOrder',
                'setIsTransactionClosed'
                ]
            )
            ->getMock();

        $this->orderRepositoryMock = $this->createMock(OrderRepositoryInterface::class);

        $this->paymentDOMock->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->paymentMock->expects(static::once())
            ->method('setTransactionId');
        $this->paymentMock->expects(static::any())
            ->method('setAdditionalInformation');
        $this->paymentMock->expects(static::once())
            ->method('setShouldCloseParentTransaction');
        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionClosed');

        $this->captureHandler = new CaptureHandler($this->orderRepositoryMock, $this->loggerMock);
    }

    /**
     * @covers CaptureHandler::handle
     */
    public function testHandleForSuccess()
    {
        $handlingSubject = ['payment' => $this->paymentDOMock];

        $response = [
            CaptureHandler::TRANSACTION_ID => '001',
            CaptureHandler::TRANSACTION_STATUS => Status::CAPTURE_SUCCEEDED,
            CaptureHandler::TRANSACTION_INFO => ['testKey' => 'testValue']
        ];

        $this->paymentMock->expects(static::never())
            ->method('setIsTransactionPending');
        $this->paymentMock->expects(static::never())
            ->method('addTransactionCommentsToOrder');

        $this->captureHandler->handle($handlingSubject, $response);
    }

    /**
     * @covers CaptureHandler::handle
     */
    public function testHandleForPending()
    {
        $handlingSubject = ['payment' => $this->paymentDOMock];

        $response = [
            CaptureHandler::TRANSACTION_ID => '002',
            CaptureHandler::TRANSACTION_STATUS => Status::CAPTURE_PENDING,
            CaptureHandler::COMMENT => 'CaptureHandler test call'
        ];

        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionPending');
        $this->paymentMock->expects(static::once())
            ->method('addTransactionCommentsToOrder');
        $this->captureHandler->handle($handlingSubject, $response);
    }
}
