<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service;

use InvalidArgumentException;

/**
 *  Class GatewayFactory
 */
class GatewayFactory
{
    /**
     * Creates gateway client based on the gateway id provided
     *
     * @param  array $data
     * @return ClientInterface
     */
    public static function create($data)
    {
        if (!isset($data['gatewayCredentials.gateway_id'])) {
            throw new InvalidArgumentException("Gateway Id required");
        }
        $path = "UnboundCommerce\GooglePay\Service\Gateway\\" . $data['gatewayCredentials.gateway_id'] . "\Client";
        if (!class_exists($path)) {
            throw new InvalidArgumentException("Invalid Gateway Id: " . $data['gatewayCredentials.gateway_id']);
        }

        return new $path();
    }
}
