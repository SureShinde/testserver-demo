<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\moneris;

// mpgGlobals #############################################

use PhpExtended\RootCacert\CacertBundle;

class mpgGlobals
{
    var $Globals=array(
                    'MONERIS_PROTOCOL' => 'https',
                    'MONERIS_HOST' => 'www3.moneris.com', //default
                    'MONERIS_TEST_HOST' => 'esqa.moneris.com',
                    'MONERIS_US_HOST' => 'esplus.moneris.com',
                    'MONERIS_US_TEST_HOST' => 'esplusqa.moneris.com',
                    'MONERIS_PORT' =>'443',
                    'MONERIS_FILE' => '/gateway2/servlet/MpgRequest',
                    'MONERIS_US_FILE' => '/gateway_us/servlet/MpgRequest',
                    'MONERIS_MPI_FILE' => '/mpi/servlet/MpiServlet',
                    'MONERIS_US_MPI_FILE' => '/mpi/servlet/MpiServlet',
                    'API_VERSION'  =>'PHP NA - 1.0.14',
                    'CONNECT_TIMEOUT' => '20',
                    'CLIENT_TIMEOUT' => '35'
                    );

    public function __construct()
    {
        // default
    }

    public function getGlobals()
    {
        return($this->Globals);
    }
}//end class mpgGlobals
