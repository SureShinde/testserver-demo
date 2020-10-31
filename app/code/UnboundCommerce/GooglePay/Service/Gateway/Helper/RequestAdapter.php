<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\Helper;

use BadFunctionCallException;

/**
 * Class RequestAdapter
 */
class RequestAdapter
{

    /**
     * Validates $data based on requested fields given in $signature array
     *
     * @param  array $signature
     * @param  array $data
     * @throws BadFunctionCallException
     */
    public static function validateArray($signature, $data)
    {
        $missingArgs = [];
        foreach ($signature as $element) {
            if (!isset($data[$element])) {
                array_push($missingArgs, $element);
            }
        }
        if (!empty($missingArgs)) {
            $exceptionString = implode(", ", $missingArgs);
            throw new BadFunctionCallException("Missing argument(s): " . $exceptionString);
        }
    }

    /**
     * Maps values in $data array based on keys given in $mapper array
     *
     * @param  array $mapper
     * @param  array $data
     * @return array
     */
    public static function assignValue($mapper, $data)
    {
        $res = [];
        foreach ($mapper as $key => $value) {
            if (is_array($value)) {
                $res[$key] = self::assignValue($value, $data);
            } else {
                $element = explode(".", $value, 2);
                if (count($element)>1 && $element[0] === "raw") {
                    $res[$key] = $element[1];
                } elseif (isset($data[$value])) {
                    $res[$key] = $data[$value];
                }
            }
        }
        return $res;
    }

    /**
     * Gets currency format
     *
     * @param  string $currency
     * @return int
     */
    public static function getCurrencyFormat($currency)
    {
        switch ($currency) {
        case "BYR":
        case "CVE":
        case "DJF":
        case "GHC":
        case "GNF":
        case "IDR":
        case "JPY":
        case "KMF":
        case "KRW":
        case "PYG":
        case "RWF":
        case "UGX":
        case "VND":
        case "VUV":
        case "XAF":
        case "XOF":
        case "XPF":
            $format = 0;
            break;
        case "MRO":
            $format = 1;
            break;
        case "BHD":
        case "JOD":
        case "KWD":
        case "LYD":
        case "OMR":
        case "TND":
            $format = 3;
            break;
        default:
            $format = 2;
            break;
        }
        return $format;
    }

    /**
     * Formats amount by currency code
     *
     * @param  int     $amount
     * @param  string  $currency
     * @param  boolean $withDecimal
     * @return string
     */
    public static function formatAmount($amount, $currency, $withDecimal = false)
    {
        $format = self::getCurrencyFormat($currency);
        $decimalPoint = $withDecimal ? '.' : '';
        return number_format($amount, $format, $decimalPoint, '');
    }
}
