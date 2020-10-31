<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use UnboundCommerce\GooglePay\Service\Processor;

class ClientAuthorize implements ClientInterface
{

    /**
     * @var Processor
     */
    protected $processor;

    /**
     * Http client constructor for authorize command
     *
     * @param Processor $processor
     */
    public function __construct(
        Processor $processor
    ) {
        $this->processor = $processor;
    }

    /**
     * Places request to payment gateway. Returns result as ENV array
     *
     * @param  TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();

        $response = $this->processor->authorize($request);

        return $response;
    }
}
