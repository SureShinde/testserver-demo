<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Test\Unit\Gateway\Command;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\SearchCriteria;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Command\GatewayCommand;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use UnboundCommerce\GooglePay\Gateway\Command\CaptureStrategyCommand;

/**
 * Tests \UnboundCommerce\GooglePay\Command\CaptureStrategyCommand
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class CaptureStrategyCommandTest extends TestCase
{
    /**
     * @var CommandPoolInterface|MockObject
     */
    private $commandPoolMock;

    /**
     * @var TransactionRepositoryInterface|MockObject
     */
    private $transactionRepositoryMock;

    /**
     * @var FilterBuilder|MockObject
     */
    private $filterBuilderMock;

    /**
     * @var SearchCriteriaBuilder|MockObject
     */
    private $searchCriteriaBuilderMock;

    /**
     * @var CaptureStrategyCommand
     */
    private $strategyCommand;

    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    /**
     * @var GatewayCommand|MockObject
     */
    private $commandMock;

    protected function setUp()
    {
        $this->commandPoolMock = $this->getMockBuilder(CommandPoolInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['get', '__wakeup'])
            ->getMock();

        $this->commandMock = $this->getMockBuilder(GatewayCommand::class)
            ->disableOriginalConstructor()
            ->setMethods(['execute'])
            ->getMock();

        $this->transactionRepositoryMock = $this->getMockBuilder(TransactionRepositoryInterface::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'get', 'getList', 'getTotalCount', 'save', 'delete', '__wakeup'])
            ->getMock();

        $this->filterBuilderMock = $this->getMockBuilder(FilterBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'setField', 'setValue', '__wakeup'])
            ->getMock();

        $this->searchCriteriaBuilderMock = $this->getMockBuilder(SearchCriteriaBuilder::class)
            ->disableOriginalConstructor()
            ->setMethods(['create', 'addFilters', '__wakeup'])
            ->getMock();

        $this->commandMock->expects(static::once())
            ->method('execute')
            ->willReturn([]);

        $this->strategyCommand = new CaptureStrategyCommand(
            $this->commandPoolMock,
            $this->transactionRepositoryMock,
            $this->filterBuilderMock,
            $this->searchCriteriaBuilderMock
        );
    }

    /**
     * @covers \UnboundCommerce\GooglePay\Gateway\Command\CaptureStrategyCommand::execute
     */
    public function testCaptureExecute()
    {
        $paymentData = $this->getPaymentDataObjectMock();
        $commandSubject['payment'] = $paymentData;

        $this->paymentMock->expects(static::once())
            ->method('getAuthorizationTransaction')
            ->willReturn(true);

        $this->paymentMock->expects(static::once())
            ->method('getId')
            ->willReturn(1);

        $this->buildSearchCriteria();

        $this->transactionRepositoryMock->expects(static::once())
            ->method('getTotalCount')
            ->willReturn(0);

        $this->commandPoolMock->expects(static::once())
            ->method('get')
            ->with(CaptureStrategyCommand::CAPTURE)
            ->willReturn($this->commandMock);

        $this->strategyCommand->execute($commandSubject);
    }

    /**
     * @covers \UnboundCommerce\GooglePay\Gateway\Command\CaptureStrategyCommand::execute
     */
    public function testSaleExecute()
    {
        $paymentData = $this->getPaymentDataObjectMock();
        $commandSubject['payment'] = $paymentData;

        $this->paymentMock->expects(static::once())
            ->method('getAuthorizationTransaction')
            ->willReturn(false);

        $this->paymentMock->expects(static::once())
            ->method('getId')
            ->willReturn(1);

        $this->buildSearchCriteria();

        $this->transactionRepositoryMock->expects(static::once())
            ->method('getTotalCount')
            ->willReturn(0);

        $this->commandPoolMock->expects(static::once())
            ->method('get')
            ->with(CaptureStrategyCommand::SALE)
            ->willReturn($this->commandMock);

        $this->strategyCommand->execute($commandSubject);
    }

    /**
     * Creates mock for payment data object and order payment
     *
     * @return MockObject
     */
    private function getPaymentDataObjectMock()
    {
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDataObjectMock = $this->getMockBuilder(PaymentDataObject::class)
            ->disableOriginalConstructor()
            ->setMethods(['getPayment'])
            ->getMock();

        $paymentDataObjectMock->expects(static::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        return $paymentDataObjectMock;
    }

    /**
     * Builds search criteria
     */
    private function buildSearchCriteria()
    {
        $this->filterBuilderMock->expects(static::exactly(2))
            ->method('setField')
            ->willReturnSelf();
        $this->filterBuilderMock->expects(static::exactly(2))
            ->method('setValue')
            ->willReturnSelf();

        $searchCriteria = new SearchCriteria();
        $this->searchCriteriaBuilderMock->expects(static::exactly(2))
            ->method('addFilters')
            ->willReturnSelf();
        $this->searchCriteriaBuilderMock->expects(static::once())
            ->method('create')
            ->willReturn($searchCriteria);

        $this->transactionRepositoryMock->expects(static::once())
            ->method('getList')
            ->with($searchCriteria)
            ->willReturnSelf();
    }
}
