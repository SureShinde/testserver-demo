<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class GatewayName
 */
class GatewayName implements ArrayInterface
{

    /**
     *  Payment Processors
     */
    const PROCESSOR_ADYEN = 'adyen';
    const PROCESSOR_BLUESNAP = 'bluesnap';
    const PROCESSOR_BRAINTREE = 'braintree';
    const PROCESSOR_PAYEEZY = 'firstdata';
    const PROCESSOR_MONERIS = 'moneris';
    const PROCESSOR_STRIPE = 'stripe';
    const PROCESSOR_VANTIV = 'vantiv';

    /**
     * Available Payment Processors
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::PROCESSOR_ADYEN,
                'label' => 'Adyen'
            ],
            [
                'value' => self::PROCESSOR_BLUESNAP,
                'label' => 'BlueSnap'
            ],
            [
                'value' => self::PROCESSOR_BRAINTREE,
                'label' => 'Braintree'
            ],
            [
                'value' => self::PROCESSOR_PAYEEZY,
                'label' => 'FirstData - Payeezy & Ucom'
            ],
            [
                'value' => self::PROCESSOR_MONERIS,
                'label' => 'Moneris'
            ],
            [
                'value' => self::PROCESSOR_STRIPE,
                'label' => 'Stripe'
            ],
            [
                'value' => self::PROCESSOR_VANTIV,
                'label' => 'Vantiv'
            ]
        ];
    }
}
