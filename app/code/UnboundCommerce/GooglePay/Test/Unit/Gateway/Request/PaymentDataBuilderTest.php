<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */
namespace UnboundCommerce\GooglePay\Test\Unit\Gateway\Request;

use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use UnboundCommerce\GooglePay\Gateway\Request\PaymentDataBuilder;

/**
 * Tests UnboundCommerce\GooglePay\Gateway\Request\PaymentDataBuilder
 */
class PaymentDataBuilderTest extends TestCase
{
    const PAYMENT_METHOD_NONCE = 'nonce';

    /**
     * @var PaymentDataBuilder
     */
    private $builder;

    /**
     * @var Payment|MockObject
     */
    private $paymentMock;

    /**
     * @var PaymentDataObjectInterface|MockObject
     */
    private $paymentDOMock;

    /**
     * @var SubjectReader|MockObject
     */
    private $subjectReaderMock;

    /**
     * @var OrderAdapterInterface|MockObject
     */
    private $orderMock;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->paymentDOMock = $this->createMock(PaymentDataObjectInterface::class);
        $this->paymentMock = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subjectReaderMock = $this->getMockBuilder(SubjectReader::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->orderMock = $this->createMock(OrderAdapterInterface::class);
        $this->builder = new PaymentDataBuilder();
    }

    /**
     * @return            void
     * @expectedException \InvalidArgumentException
     */
    public function testBuildPaymentException()
    {
        $buildSubject = [];

        $this->builder->build($buildSubject);
    }

    /**
     * @param        string $amount
     * @param        array  $orderData
     * @param        array  $addressData
     * @param        array  $additionalInfo
     * @param        array  $expected
     * @dataProvider buildDataProvider
     */
    public function testBuild($amount, $orderData, $addressData, $additionalInfo, $expected)
    {
        $buildSubject = [
            'payment' => $this->paymentDOMock,
            'amount' => $amount
        ];
        $this->paymentMock->expects(self::once())
            ->method('getAdditionalInformation')
            ->willReturn($additionalInfo);

        $this->paymentDOMock->expects(self::once())
            ->method('getPayment')
            ->willReturn($this->paymentMock);

        $this->paymentDOMock->expects(self::once())
            ->method('getOrder')
            ->willReturn($this->orderMock);

        $addressMock = $this->getAddressMock($addressData);

        $this->orderMock->expects(self::any())
            ->method('getGrandTotalAmount')
            ->willReturn($orderData['grandTotalAmount']);

        $this->orderMock->expects(self::once())
            ->method('getBillingAddress')
            ->willReturn($addressMock);

        $this->orderMock->expects(self::once())
            ->method('getCurrencyCode')
            ->willReturn($orderData['currencyCode']);

        $this->orderMock->expects(self::once())
            ->method('getOrderIncrementId')
            ->willReturn($orderData['incrementId']);

        self::assertEquals(
            $expected,
            $this->builder->build($buildSubject)
        );
    }

    /**
     * @return array
     */
    public function buildDataProvider()
    {
        $orderData = [
            'currencyCode' => 'USD',
            'incrementId' => '001',
            'grandTotalAmount' => 50.00
        ];

        $addressData = [
            'email' => 'johndoe@google.com',
            'firstName' => 'John',
            'lastName' => 'Doe',
            'streetLine1' => 'c/o Google LLC',
            'streetLine2' => '1600 Amphitheatre Pkwy',
            'city' => 'Mountain View',
            'regionCode' => 'CA',
            'countryId' => 'US',
            'postalCode' => '94043'
        ];

        return [
            [
                10.00,
                $orderData,
                $addressData,
                [],
                [
                    'amount' => 10.00,
                    'currency' => $orderData['currencyCode'],
                    'orderId' => $orderData['incrementId'],
                    'customerEmail' => $addressData['email'],
                    'firstName' => $addressData['firstName'],
                    'lastName' => $addressData['lastName'],
                    'streetLine1' => $addressData['streetLine1'],
                    'streetLine2' => $addressData['streetLine2'],
                    'city' => $addressData['city'],
                    'region' => $addressData['regionCode'],
                    'country' => $addressData['countryId'],
                    'postalCode' => $addressData['postalCode']
                ]
            ],
            [
                null,
                $orderData,
                $addressData,
                ['originalReference' => '12345'],
                [
                    'amount' => $orderData['grandTotalAmount'],
                    'currency' => $orderData['currencyCode'],
                    'orderId' => $orderData['incrementId'],
                    'customerEmail' => $addressData['email'],
                    'firstName' => $addressData['firstName'],
                    'lastName' => $addressData['lastName'],
                    'streetLine1' => $addressData['streetLine1'],
                    'streetLine2' => $addressData['streetLine2'],
                    'city' => $addressData['city'],
                    'region' => $addressData['regionCode'],
                    'country' => $addressData['countryId'],
                    'postalCode' => $addressData['postalCode'],
                    'originalReference' => '12345'
                ]
            ],
        ];
    }

    /**
     * @param  array $addressData
     * @return AddressAdapterInterface|MockObject
     */
    private function getAddressMock($addressData)
    {
        $addressMock = $this->createMock(AddressAdapterInterface::class);

        $addressMock->expects(self::exactly(1))
            ->method('getEmail')
            ->willReturn($addressData['email']);
        $addressMock->expects(self::exactly(1))
            ->method('getFirstname')
            ->willReturn($addressData['firstName']);
        $addressMock->expects(self::exactly(1))
            ->method('getLastname')
            ->willReturn($addressData['lastName']);
        $addressMock->expects(self::exactly(1))
            ->method('getStreetLine1')
            ->willReturn($addressData['streetLine1']);
        $addressMock->expects(self::exactly(1))
            ->method('getStreetLine2')
            ->willReturn($addressData['streetLine2']);
        $addressMock->expects(self::exactly(1))
            ->method('getCity')
            ->willReturn($addressData['city']);
        $addressMock->expects(self::exactly(1))
            ->method('getRegionCode')
            ->willReturn($addressData['regionCode']);
        $addressMock->expects(self::exactly(1))
            ->method('getCountryId')
            ->willReturn($addressData['countryId']);
        $addressMock->expects(self::exactly(1))
            ->method('getPostcode')
            ->willReturn($addressData['postalCode']);

        return $addressMock;
    }
}
