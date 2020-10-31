<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Service\Gateway\Helper;

/**
 * Class Status
 */
class Status
{
    const AUTH_RECEIVED     =   "auth_received";
    const AUTH_SUCCEEDED    =   "auth_succeeded";
    const AUTH_FAILED       =   "auth_failed";
    const AUTH_PENDING      =   "auth_pending";
    const CAPTURE_RECEIVED  =   "capture_received";
    const CAPTURE_SUCCEEDED =   "capture_succeeded";
    const CAPTURE_FAILED    =   "capture_failed";
    const CAPTURE_PENDING   =   "capture_pending";
    const SALE_RECEIVED     =   "sale_received";
    const SALE_SUCCEEDED    =   "sale_succeeded";
    const SALE_FAILED       =   "sale_failed";
    const SALE_PENDING      =   "sale_pending";
    const REFUND_RECEIVED   =   "refund_received";
    const REFUND_SUCCEEDED  =   "refund_succeeded";
    const REFUND_FAILED     =   "refund_failed";
    const REFUND_PENDING    =   "refund_pending";
    const VOID_RECEIVED     =   "void_received";
    const VOID_SUCCEEDED    =   "void_succeeded";
    const VOID_FAILED       =   "void_failed";
    const VOID_PENDING      =   "void_pending";

    const STATUS_COMMENT_MAPPER = [
        self::AUTH_RECEIVED => "Authorisation transaction received.",
        self::AUTH_SUCCEEDED => "Authorisation transaction succeeded.",
        self::AUTH_FAILED => "Authorisation transaction failed.",
        self::AUTH_PENDING => "Authorisation transaction pending.",
        self::CAPTURE_RECEIVED  => "Capture transaction received.",
        self::CAPTURE_SUCCEEDED => "Capture transaction succeeded.",
        self::CAPTURE_FAILED => "Capture transaction failed.",
        self::CAPTURE_PENDING => "Capture transaction pending.",
        self::SALE_RECEIVED => "Sale transaction received.",
        self::SALE_SUCCEEDED=> "Sale transaction succeeded.",
        self::SALE_FAILED => "Sale transaction failed.",
        self::SALE_PENDING => "Sale transaction pending.",
        self::REFUND_RECEIVED => "Refund transaction received.",
        self::REFUND_SUCCEEDED => "Refund transaction succeeded.",
        self::REFUND_FAILED => "Refund transaction failed.",
        self::REFUND_PENDING => "Refund transaction pending.",
        self::VOID_RECEIVED => "Void transaction received.",
        self::VOID_SUCCEEDED => "Void transaction succeeded.",
        self::VOID_FAILED => "Void transaction failed.",
        self::VOID_PENDING => "Void transaction pending."
    ];

    const FAILED_TRANSACTIONS = [self::AUTH_FAILED, self::CAPTURE_FAILED, self::SALE_FAILED, self::VOID_FAILED, self::REFUND_FAILED];

    const PENDING_TRANSACTIONS = [self::AUTH_PENDING, self::CAPTURE_PENDING, self::SALE_PENDING, self::VOID_PENDING, self::REFUND_PENDING];

    const RECEIVED_TRANSACTIONS = [self::AUTH_RECEIVED, self::CAPTURE_RECEIVED, self::SALE_RECEIVED, self::VOID_RECEIVED, self::REFUND_RECEIVED];

    const SUCCEEDED_TRANSACTIONS = [self::AUTH_SUCCEEDED, self::CAPTURE_SUCCEEDED, self::SALE_SUCCEEDED, self::VOID_SUCCEEDED, self::REFUND_SUCCEEDED];

    const AUTH_TRANSACTIONS = [self::AUTH_RECEIVED, self::AUTH_PENDING, self::AUTH_SUCCEEDED, self::AUTH_FAILED];

    const CAPTURE_TRANSACTIONS = [self::CAPTURE_RECEIVED, self::CAPTURE_PENDING, self::CAPTURE_SUCCEEDED, self::CAPTURE_FAILED];

    const SALE_TRANSACTIONS = [self::SALE_RECEIVED, self::SALE_PENDING, self::SALE_SUCCEEDED, self::SALE_FAILED];

    const VOID_TRANSACTIONS = [self::VOID_RECEIVED, self::VOID_PENDING, self::VOID_SUCCEEDED, self::VOID_FAILED];

    const REFUND_TRANSACTIONS = [self::REFUND_RECEIVED, self::REFUND_PENDING, self::REFUND_SUCCEEDED, self::REFUND_FAILED];

    /**
     * Checks if status represents a failed transaction
     *
     * @param  string $status
     * @return boolean
     */
    public static function transactionFailed($status)
    {
        if (in_array($status, self::FAILED_TRANSACTIONS)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if status represents a successful transaction
     *
     * @param  string $status
     * @return boolean
     */
    public static function transactionSucceeded($status)
    {
        if (in_array($status, self::SUCCEEDED_TRANSACTIONS)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if status represents that the transaction has been received
     *
     * @param  string $status
     * @return boolean
     */
    public static function transactionReceived($status)
    {
        if (in_array($status, self::RECEIVED_TRANSACTIONS)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if status represents a pending transaction
     *
     * @param  string $status
     * @return boolean
     */
    public static function transactionPending($status)
    {
        if (in_array($status, self::PENDING_TRANSACTIONS)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if status represents an authorization transaction
     *
     * @param  string $status
     * @return boolean
     */
    public static function isAuthTransaction($status)
    {
        if (in_array($status, self::AUTH_TRANSACTIONS)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if status represents a capture transaction
     *
     * @param  string $status
     * @return boolean
     */
    public static function isCaptureTransaction($status)
    {
        if (in_array($status, self::CAPTURE_TRANSACTIONS)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if status represents a sale transaction
     *
     * @param  string $status
     * @return boolean
     */
    public static function isSaleTransaction($status)
    {
        if (in_array($status, self::SALE_TRANSACTIONS)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if status represents a void/cancel transaction
     *
     * @param  string $status
     * @return boolean
     */
    public static function isVoidTransaction($status)
    {
        if (in_array($status, self::VOID_TRANSACTIONS)) {
            return true;
        }
        return false;
    }

    /**
     * Checks if status represents a refund transaction
     *
     * @param  string $status
     * @return boolean
     */
    public static function isRefundTransaction($status)
    {
        if (in_array($status, self::REFUND_TRANSACTIONS)) {
            return true;
        }
        return false;
    }
}
