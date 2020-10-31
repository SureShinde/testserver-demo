<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use UnboundCommerce\GooglePay\Gateway\Config\Config;

class GatewayCredentialsBuilder implements BuilderInterface
{
    /**
     * @var Config
     */
    private $config;

    /**
     * Constructor for GatewayCredentialsBuilder
     *
     * @param Config $config
     */
    public function __construct(
        Config $config
    ) {
        $this->config = $config;
    }

    /**
     * Builds ENV request for merchant gateway credentials
     *
     * @param  array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $res = $this->config->getMerchantGatewayCredentials();

        return $res;
    }
}
