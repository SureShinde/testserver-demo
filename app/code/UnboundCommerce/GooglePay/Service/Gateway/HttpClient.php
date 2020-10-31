<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway;

/**
 *  Class HttpClient
 */
class HttpClient
{
    /**
     * Processes http post request
     *
     * @param  string      $url
     * @param  array|null  $headers
     * @param  mixed|null  $body
     * @param  string|null $basicAuth
     * @return array
     */
    public function post($url, $headers = null, $body = null, $basicAuth = null)
    {
        return $this->sendRequest("POST", $url, $headers, $body, $basicAuth);
    }

    /**
     * Processes http put request
     *
     * @param  string      $url
     * @param  array|null  $headers
     * @param  mixed|null  $body
     * @param  string|null $basicAuth
     * @return array
     */
    public function put($url, $headers = null, $body = null, $basicAuth = null)
    {
        return $this->sendRequest("PUT", $url, $headers, $body, $basicAuth);
    }

    /**
     * Processes http get request
     *
     * @param  string      $url
     * @param  array|null  $headers
     * @param  string|null $basicAuth
     * @return array
     */
    public function get($url, $headers = null, $basicAuth = null)
    {
        return $this->sendRequest("GET", $url, $headers, null, $basicAuth);
    }

    /**
     * Sends http request
     *
     * @param  string      $command
     * @param  string      $url
     * @param  array|[]    $requestHeaders
     * @param  mixed|null  $requestBody
     * @param  string|null $basicAuth
     * @return array
     */
    public function sendRequest($command, $url, $requestHeaders = [], $requestBody = null, $basicAuth = null)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $command);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $requestHeaders);
        if (!empty($basicAuth)) {
            curl_setopt($curl, CURLOPT_USERPWD, $basicAuth);
        }
        if (!empty($requestBody)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $requestBody);
        }

        $response = curl_exec($curl);
        $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        curl_close($curl);

        return ['statusCode' => $httpStatusCode, 'contentType' => $contentType, 'httpResponse' => $response];
    }
}
