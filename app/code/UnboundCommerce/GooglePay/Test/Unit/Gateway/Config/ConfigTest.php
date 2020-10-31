<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Test\Unit\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager;
use Magento\Store\Model\ScopeInterface;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_MockObject_MockObject as MockObject;
use UnboundCommerce\GooglePay\Gateway\Config\Config;

/**
 * Tests \UnboundCommerce\GooglePay\Test\Unit\Gateway\Config
 */
class ConfigTest extends TestCase
{
    const METHOD_CODE = 'googlepay';

    /**
     * @var Config
     */
    private $model;

    /**
     * @var ScopeConfigInterface|MockObject
     */
    private $scopeConfigMock;

    /**
     * @var EncryptorInterface|MockObject
     */
    private $encryptorMock;

    protected function setUp()
    {
        $this->scopeConfigMock = $this->createMock(ScopeConfigInterface::class);
        $this->encryptorMock = $this->createMock(EncryptorInterface::class);

        $objectManager = new ObjectManager($this);
        $this->model = $objectManager->getObject(
            Config::class,
            [
                'scopeConfig' => $this->scopeConfigMock,
                'encryptor' => $this->encryptorMock,
                'methodCode' => self::METHOD_CODE
            ]
        );
    }

    /**
     * @covers \UnboundCommerce\GooglePay\Gateway\Config\Config::isActive
     */
    public function testIsActive()
    {
        $this->scopeConfigMock->expects(static::any())
            ->method('getValue')
            ->with($this->getPath(Config::KEY_ACTIVE), ScopeInterface::SCOPE_STORE, null)
            ->willReturn(1);

        static::assertEquals(true, $this->model->isActive());
    }

    /**
     * @param        string $value
     * @param        array  $expected
     * @dataProvider getAvailableCardTypesDataProvider
     */
    public function testGetAvailableCardTypes($value, $expected)
    {
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with($this->getPath(Config::KEY_CC_TYPES), ScopeInterface::SCOPE_STORE, null)
            ->willReturn($value);

        static::assertEquals(
            $expected,
            $this->model->getAvailableCardTypes()
        );
    }

    /**
     * @return array
     */
    public function getAvailableCardTypesDataProvider()
    {
        return [
            [
                'AE,DI,IC,JCB,MC,VI',
                ['AMEX', 'DISCOVER', 'INTERAC', 'JCB', 'MASTERCARD', 'VISA']
            ],
            [
                '',
                []
            ]
        ];
    }

    /**
     * @param        string $gatewayId
     * @param        string $key
     * @param        string $pathKey
     * @param        string $value
     * @param        array  $expected
     * @dataProvider getGatewayDataDataProvider
     */
    public function testGetGatewayData($gatewayId, $key, $pathKey, $value, $expected)
    {
        $path = 'payment/' . self::METHOD_CODE . "_" . $gatewayId . "/" . $pathKey;
        $this->scopeConfigMock->expects(static::once())
            ->method('getValue')
            ->with($path, ScopeInterface::SCOPE_STORE, null)
            ->willReturn($value);

        $this->encryptorMock->expects(static::any())
            ->method('decrypt')
            ->with($value)
            ->willReturn('DECRYPTED_' . $value);

        static::assertEquals(
            $expected,
            $this->model->getGatewayData($gatewayId, $key)
        );
    }

    /**
     * @return array
     */
    public function getGatewayDataDataProvider()
    {
        return [
            [
                'adyen', 'username', 'username', 'user_one',
                'expected' => [
                    'key' => 'username', 'value' => 'user_one'
                ]
            ],
            [
                'adyen', 'username', 'username', 'user.two',
                'expected' => [
                    'key' => 'username', 'value' => 'user.two'
                ]
            ],
            [
                'braintree', 'ENC.password_one', 'password_one', "one",
                'expected' => [
                    'key' => 'password_one', 'value' => 'DECRYPTED_one'
                ]
            ],
            [
                'bluesnap', 'ENC.password.two', 'password.two', 'two',
                'expected' => [
                    'key' => 'password.two', 'value' => 'DECRYPTED_two'
                ]
            ]
        ];
    }

    /**
     * Return config path
     *
     * @param  string $field
     * @return string
     */
    private function getPath($field)
    {
        return sprintf(Config::DEFAULT_PATH_PATTERN, self::METHOD_CODE, $field);
    }
}
