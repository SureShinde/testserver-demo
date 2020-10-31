<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\cybersource\Resources;

use CyberSource\Authentication\Core\MerchantConfiguration;
use CyberSource\Configuration;

/*
* Purpose : passing Authentication config object to the configuration
*/
class ExternalConfiguration
{
    //initialize variable on constructor
    public function __construct()
    {
        $this->authType = "http_signature";//http_signature/jwt
        $this->enableLog = false;
        $this->logSize = "1048576";
        $this->logFile = "./Log/";
        $this->logFilename = "Cybs.log";
        $this->merchantID = "test";
        $this->apiKeyID = "test";
        $this->secretKey = "test";
        $this->keyAlias = "test";
        $this->keyPass = "test";
        $this->keyFilename = "test";
        $this->keyDirectory = "./Resources/";
        $this->runEnv = "cyberSource.environment.SANDBOX";
        $this->merchantConfigObject();
    }
    //creating merchant config object
    public function merchantConfigObject()
    {
        $config = new MerchantConfiguration();
        if (is_bool($this->enableLog)) {
            $confiData = $config->setDebug($this->enableLog);
        }

        $confiData = $config->setLogSize(trim($this->logSize));
        $confiData = $config->setDebugFile(trim(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $this->logFile));
        $confiData = $config->setLogFileName(trim($this->logFilename));
        $confiData = $config->setauthenticationType(strtoupper(trim($this->authType)));
        $confiData = $config->setMerchantID(trim($this->merchantID));
        $confiData = $config->setApiKeyID($this->apiKeyID);
        $confiData = $config->setSecretKey($this->secretKey);
        $confiData = $config->setKeyFileName(trim($this->keyFilename));
        $confiData = $config->setKeyAlias($this->keyAlias);
        $confiData = $config->setKeyPassword($this->keyPass);
        $confiData = $config->setKeysDirectory(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . $this->keyDirectory);
        $confiData = $config->setRunEnvironment($this->runEnv);
        $config->validateMerchantData($confiData);
        return $config;
    }

    public function ConnectionHost()
    {
        $merchantConfig = $this->merchantConfigObject();
        $config = new Configuration();
        $config = $config->setHost($merchantConfig->getHost());
        $config = $config->setDebug($merchantConfig->getDebug());
        $config = $config->setDebugFile($merchantConfig->getDebugFile() . DIRECTORY_SEPARATOR . $merchantConfig->getLogFileName());
        return $config;
    }

    public function FutureDate($format)
    {
        if ($format) {
            $rdate = date("Y-m-d", strtotime("+7 days"));
            $retDate = date($format, strtotime($rdate));
        } else {
            $retDate = date("Y-m", strtotime("+7 days"));
        }
        return $retDate;
    }

    public function CallTestLogging($testId, $apiName, $responseMessage)
    {
        $runtime = date('d-m-Y H:i:s');
        $file = fopen("./CSV_Files/TestReport/TestResults.csv", "a+");
        fputcsv($file, [$testId, $runtime, $apiName, $responseMessage]);
        fclose($file);
    }

    public function downloadReport($downloadData, $fileName)
    {
        $filePathName = __DIR__ . DIRECTORY_SEPARATOR . $fileName;
        $file = fopen($filePathName, "w");
        fwrite($file, $downloadData);
        fclose($file);
        return __DIR__ . '\\' . $fileName;
    }
}
