<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service;

use UnboundCommerce\GooglePay\Logger\Logger;

/**
 *  Class Processor
 */
class Processor
{

    /**
     * @var Logger
     */
    public $logger;

    /**
     * Constructor
     *
     * @param Logger $logger
     */
    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Places authorization request to payment gateway
     *
     * @param  array $data
     * @return array
     */
    public function authorize($data)
    {
        return $this->createGatewayClient($data)->authorize($data);
    }

    /**
     * Places capture request to payment gateway
     *
     * @param  array $data
     * @return array
     */
    public function capture($data)
    {
        return $this->createGatewayClient($data)->capture($data);
    }

    /**
     * Places sale request to payment gateway
     *
     * @param  array $data
     * @return array
     */
    public function sale($data)
    {
        return $this->createGatewayClient($data)->sale($data);
    }

    /**
     * Places refund request to payment gateway
     *
     * @param  array $data
     * @return array
     */
    public function refund($data)
    {
        return $this->createGatewayClient($data)->refund($data);
    }

    /**
     * Places void request to payment gateway
     *
     * @param  array $data
     * @return array
     */
    public function void($data)
    {
        return $this->createGatewayClient($data)->void($data);
    }

    /**
     * Creates gateway client
     *
     * @param  array $data
     * @return ClientInterface
     */
    public function createGatewayClient($data)
    {
        $gateway = GatewayFactory::create($data);
        if ($gateway) {
            $gateway->setLogger($this->logger);
        }
        return $gateway;
    }
}
