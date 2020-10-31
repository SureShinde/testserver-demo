<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\moneris;

//require_once 'mpgGlobals.php';
/**
*if (!defined('mpgGlobals')) {
*    include 'mpgGlobals.php';
*    define('mpgGlobals', 1);
*}
*/

// curlPost #############################################
class httpsPost
{
    var $url;
    var $dataToSend;
    var $clientTimeOut;
    var $apiVersion;
    var $response;
    var $debug = false; //default is false for production release

    public function __construct($url, $dataToSend)
    {
        $this->url=$url;
        $this->dataToSend=$dataToSend;

        if ($this->debug == true) {
            //echo "DataToSend= ".$this->dataToSend;
            //echo "\n\nPostURL= " . $this->url;
        }

        $g=new mpgGlobals();
        $gArray=$g->getGlobals();
        $connectTimeOut = $gArray['CONNECT_TIMEOUT'];
        $clientTimeOut = $gArray['CLIENT_TIMEOUT'];
        $apiVersion = $gArray['API_VERSION'];

        $ch = curl_init();
        $cacert_path = CacertBundle::getFilePath();
        curl_setopt($ch, CURLOPT_URL, $this->url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $this->dataToSend);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $connectTimeOut);
        curl_setopt($ch, CURLOPT_TIMEOUT, $clientTimeOut);
        curl_setopt($ch, CURLOPT_USERAGENT, $apiVersion);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        curl_setopt($ch, CURLOPT_CAINFO, $cacert_path);

        $this->response=curl_exec($ch);

        curl_close($ch);

        if ($this->debug == true) {
            //echo "\n\nRESPONSE= $this->response\n";
        }
    }

    public function getHttpsResponse()
    {
        return $this->response;
    }
}
