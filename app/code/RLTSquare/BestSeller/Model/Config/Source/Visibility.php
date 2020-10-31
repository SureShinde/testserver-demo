<?php
/**
 * NOTICE OF LICENSE
 * You may not sell, distribute, sub-license, rent, lease or lend complete or portion of software to anyone.
 *
 * DISCLAIMER
 * Do not edit or add to this file if you wish to upgrade to newer
 * versions in the future.
 *
 * @package   RLTSquare_BestSeller
 * @copyright Copyright (c) 2017 RLTSquare (https://www.rltsquare.com)
 * @contacts  support@rltsquare.com
 * @license  See the LICENSE.md file in module root directory
 */

namespace RLTSquare\BestSeller\Model\Config\Source;

/**
 * Class Visibility
 * @package RLTSquare\BestSeller\Model\Config\Source
 */
class Visibility implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        $dropdown = [
            ['value' => 'Product Page', 'label' => __('Product Page')],
            ['value' => 'Category Page', 'label' => __('Category Page')],
            ['value' => 'Home Page', 'label' => __('Home Page')]
        ];
        return $dropdown;
    }
}
