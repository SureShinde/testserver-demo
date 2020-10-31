<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model\Adminhtml\Source;

use Magento\Framework\Option\ArrayInterface;

/**
 * Class ButtonColor
 */
class ButtonColor implements ArrayInterface
{
    /**
     *  GooglePay button color
     */
    const COLOR_DEFAULT = 'default';
    const COLOR_BLACK = 'black';
    const COLOR_WHITE = 'white';

    /**
     * Possible button colors
     *
     * @return array
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => self::COLOR_DEFAULT,
                'label' => 'Default'
            ],
            [
                'value' => self::COLOR_BLACK,
                'label' => 'Black'
            ],
            [
                'value' => self::COLOR_WHITE,
                'label' => 'White'
            ]
        ];
    }
}
