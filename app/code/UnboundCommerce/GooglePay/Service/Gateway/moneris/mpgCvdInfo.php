<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\moneris;

// mpgCvdInfo ############################################

class mpgCvdInfo
{

    var $params;
    var $cvdTemplate = array('cvd_indicator','cvd_value');

    public function __construct($params)
    {
        $this->params = $params;
    }

    public function toXML()
    {
        $xmlString = "";

        foreach ($this->cvdTemplate as $tag) {
            $xmlString .= "<$tag>". $this->params[$tag] ."</$tag>";
        }

        return "<cvd_info>$xmlString</cvd_info>";
    }
}//end class
