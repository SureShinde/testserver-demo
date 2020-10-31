<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Controller\Webhook;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http as HttpRequest;
use UnboundCommerce\GooglePay\Cron\Adyen;
use UnboundCommerce\GooglePay\Cron\Braintree;
use UnboundCommerce\GooglePay\Cron\Cybersource;
use UnboundCommerce\GooglePay\Cron\Stripe;
use UnboundCommerce\GooglePay\Gateway\Config\Config;
use UnboundCommerce\GooglePay\Model\Adminhtml\Source\GatewayName;

class Crontest extends Action
{
    /**
     * @var Config
     */
    protected $config;

    /**
     * @var Adyen
     */
    protected $adyen;

    /**
     * @var Braintree
     */
    protected $braintree;

    /**
     * @var Cybersource
     */
    protected $cybersource;

    /**
     * @var Stripe
     */
    protected $stripe;

    public function __construct(
        Context $context,
        Config $config,
        Adyen $adyen,
        Braintree $braintree,
        Cybersource $cybersource,
        Stripe $stripe
    ) {
        parent::__construct($context);
        $this->config = $config;
        $this->adyen = $adyen;
        $this->braintree = $braintree;
        $this->cybersource = $cybersource;
        $this->stripe = $stripe;

        $request = $this->getRequest();
        if ($request instanceof HttpRequest && $request->isPost()) {
            $request->setParam('isAjax', true);
        }
    }

    public function execute()
    {
        $gateway = $this->config->getGateway();
        switch ($gateway) {
            case GatewayName::PROCESSOR_ADYEN:
                $this->adyen->execute();
                break;
            case GatewayName::PROCESSOR_BRAINTREE:
                $this->braintree->execute();
                break;
            case GatewayName::PROCESSOR_STRIPE:
                $this->stripe->execute();
                break;
            case GatewayName::PROCESSOR_CYBERSOURCE:
                $this->cybersource->execute();
                break;
        }
        //return;
    }
}
