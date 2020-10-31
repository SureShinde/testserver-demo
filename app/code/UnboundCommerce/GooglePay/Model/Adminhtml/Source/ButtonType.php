<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ButtonType
 */
class ButtonType implements ArrayInterface
{
    /**
     *  GooglePay button type
     */
    const TYPE_LONG = 'long';
    const TYPE_SHORT = 'short';

    /**
     * Possible button types
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::TYPE_LONG,
                'label' => 'Long',
            ],
            [
                'value' => self::TYPE_SHORT,
                'label' => 'Short'
            ]
        ];
    }
}
