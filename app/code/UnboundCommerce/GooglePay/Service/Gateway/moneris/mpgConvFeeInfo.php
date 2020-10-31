<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\moneris;

// mpgConvFeeInfo ########################################

class mpgConvFeeInfo
{

    var $params;
    var $convFeeTemplate = array('convenience_fee');

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function toXML()
    {
        $xmlString = "";

        foreach ($this->convFeeTemplate as $tag) {
            $xmlString .= "<$tag>". $this->params[$tag] ."</$tag>";
        }

        return "<convfee_info>$xmlString</convfee_info>";
    }
}//end class
