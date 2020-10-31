<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\moneris;

// mpiTransaction ############################################

class MpiTransaction
{
    var $txn;

    public function __construct($txn)
    {
        $this->txn=$txn;
    }

    public function getTransaction()
    {
        return $this->txn;
    }
}//end class MpiTransaction
