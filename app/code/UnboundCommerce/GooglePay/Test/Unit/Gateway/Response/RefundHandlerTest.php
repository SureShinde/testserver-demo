<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Test\Unit\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order\Creditmemo;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use UnboundCommerce\GooglePay\Gateway\Response\RefundHandler;
use UnboundCommerce\GooglePay\Logger\Logger;
use UnboundCommerce\GooglePay\Service\Gateway\Helper\Status;

/**
 * Tests \UnboundCommerce\GooglePay\Gateway\Response\RefundHandler
 */
class RefundHandlerTest extends TestCase
{

    /**
     * @var RefundHandler
     */
    private $refundHandler;

    /**
     * @var Creditmemo|MockObject
     */
    private $creditmemoMock;

    /**
     * @var Invoice|MockObject
     */
    private $invoiceMock;

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

    private $handlingSubject;

    protected function setUp()
    {
        $this->loggerMock =  $this->getMockBuilder(Logger::class)
            ->disableOriginalConstructor()
            ->setMethods(['addInfo'])
            ->getMock();

        $this->paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->handlingSubject = ['payment' => $this->paymentDOMock];

        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                'setTransactionId',
                'getCreditmemo',
                'setIsTransactionPending',
                'setAdditionalInformation',
                'addTransactionCommentsToOrder',
                'setIsTransactionClosed',
                'setShouldCloseParentTransaction'
                ]
            )
            ->getMock();

        $this->creditmemoMock = $this->getMockBuilder(Creditmemo::class)
            ->disableOriginalConstructor()
            ->setMethods(['getInvoice', 'setState'])
            ->getMock();

        $this->invoiceMock = $this->getMockBuilder(Invoice::class)
            ->disableOriginalConstructor()
            ->setMethods(['canRefund'])
            ->getMock();

        $this->paymentDOMock->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->paymentMock->expects(static::once())
            ->method('setTransactionId');
        $this->paymentMock->expects(static::any())
            ->method('setAdditionalInformation');

        $this->paymentMock->expects(static::once())
            ->method('getCreditmemo')
            ->willReturn($this->creditmemoMock);

        $this->creditmemoMock
            ->method('setState')
            ->willReturn($this->invoiceMock);

        $this->creditmemoMock
            ->method('getInvoice')
            ->willReturn($this->invoiceMock);

        $this->refundHandler = new RefundHandler($this->loggerMock);
    }

    /**
     * @covers RefundHandler::handle
     */
    public function testHandleForCloseParent()
    {
        $response = [
            RefundHandler::TRANSACTION_ID => '001',
            RefundHandler::TRANSACTION_STATUS => Status::REFUND_SUCCEEDED,
        ];

        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionClosed');
        $this->paymentMock->expects(static::never())
            ->method('addTransactionCommentsToOrder');
        $this->creditmemoMock->expects(static::never())
            ->method('setState');
        $this->invoiceMock
            ->method('canRefund')
            ->willReturn(false);
        $this->paymentMock->expects(static::once())
            ->method('setShouldCloseParentTransaction')
            ->with(true);
        $this->refundHandler->handle($this->handlingSubject, $response);
    }

    /**
     * @covers RefundHandler::handle
     */
    public function testHandleForDoNotCloseParent()
    {
        $response = [
            RefundHandler::TRANSACTION_ID => '002',
            RefundHandler::TRANSACTION_STATUS => Status::REFUND_SUCCEEDED,
        ];

        $this->paymentMock->expects(static::once())
            ->method('setIsTransactionClosed');
        $this->paymentMock->expects(static::never())
            ->method('addTransactionCommentsToOrder');
        $this->creditmemoMock->expects(static::never())
            ->method('setState');
        $this->invoiceMock
            ->method('canRefund')
            ->willReturn(true);
        $this->paymentMock->expects(static::once())
            ->method('setShouldCloseParentTransaction')
            ->with(false);
        $this->refundHandler->handle($this->handlingSubject, $response);
    }

    /**
     * @covers RefundHandler::handle
     */
    public function testHandleForPending()
    {
        $response = [
            RefundHandler::TRANSACTION_ID => '003',
            RefundHandler::TRANSACTION_STATUS => Status::REFUND_PENDING,
            RefundHandler::COMMENT => 'authorisationHandler test call'
        ];

        $this->creditmemoMock
            ->method('setState')
            ->willReturn($this->invoiceMock);
        $this->paymentMock->expects(static::once())
            ->method('addTransactionCommentsToOrder');
        $this->creditmemoMock->expects(static::once())
            ->method('setState');
        $this->paymentMock->expects(static::never())
            ->method('setIsTransactionClosed');
        $this->paymentMock->expects(static::never())
            ->method('setShouldCloseParentTransaction')
            ->with(false);
        $this->refundHandler->handle($this->handlingSubject, $response);
    }
}
