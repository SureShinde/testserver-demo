<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Gateway\Request;

use Magento\Framework\HTTP\Header;
use Magento\Payment\Gateway\Request\BuilderInterface;

class AdditionalDataBuilder implements BuilderInterface
{
    /**
     * @var Header
     */
    private $httpHeader;

    /**
     * Constructor for AdditionalDataBuilder
     *
     * @param Header $httpHeader
     */
    public function __construct(
        Header $httpHeader
    ) {
        $this->httpHeader = $httpHeader;
    }

    /**
     * Builds ENV request for additional data
     *
     * @param  array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        $res = ['userAgent' => $this->httpHeader->getHttpUserAgent()];

        return $res;
    }
}
