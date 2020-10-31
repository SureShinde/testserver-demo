<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class Environment
 */
class ThreeDSecureType implements ArrayInterface
{
    const THREEDS_MANUAL = 'manual';
    const THREEDS_DYNAMIC = 'dynamic';

    /**
     * Possible environment types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::THREEDS_MANUAL,
                'label' => 'Manual'
            ],
            [
                'value' => self::THREEDS_DYNAMIC,
                'label' => 'Dynamic'
            ]
        ];
    }
}
