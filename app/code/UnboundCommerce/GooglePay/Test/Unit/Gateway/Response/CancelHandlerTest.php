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
use UnboundCommerce\GooglePay\Gateway\Response\CancelHandler;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Tests \UnboundCommerce\GooglePay\Gateway\Response\CancelHandler
 */
class CancelHandlerTest extends TestCase
{
    /**
     * @var CancelHandler
     */
    private $cancelHandler;

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

        $this->cancelHandler = new CancelHandler($this->loggerMock);
    }

    /**
     * @covers CancelHandler::handle
     */
    public function testHandle()
    {
        $handlingSubject = ['payment' => $this->paymentDOMock];

        $response = [
            CancelHandler::TRANSACTION_ID => '001',
            CancelHandler::TRANSACTION_STATUS => Status::VOID_SUCCEEDED,
            CancelHandler::TRANSACTION_INFO => ['testKey' => 'testValue'],
            CancelHandler::COMMENT => 'cancelHandler test call'
        ];

        $this->paymentMock->expects(static::once())
            ->method('addTransactionCommentsToOrder');

        $this->cancelHandler->handle($handlingSubject, $response);
    }
}
