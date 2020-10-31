<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\moneris;

// riskTransaction #######################################

class riskTransaction
{

    var $txn;
    var $attributeAccountInfo = null;
    var $sessionAccountInfo = null;

    public function __construct($txn)
    {
        $this->txn=$txn;
    }

    public function getTransaction()
    {
        return $this->txn;
    }

    public function getAttributeAccountInfo()
    {
        return $this->attributeAccountInfo;
    }

    public function setAttributeAccountInfo($attributeAccountInfo)
    {
        $this->attributeAccountInfo = $attributeAccountInfo;
    }

    public function getSessionAccountInfo()
    {
        return $this->sessionAccountInfo;
    }

    public function setSessionAccountInfo($sessionAccountInfo)
    {
        $this->sessionAccountInfo = $sessionAccountInfo;
    }
}//end class RiskTransaction
