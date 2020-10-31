<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Logger;

use Magento\Framework\App\ObjectManager;

class Logger extends \Monolog\Logger
{
    /**
     * Log for cron execution
     */
    const GPAY_CRON = 211;

    /**
     * Log on receiving webhook
     */
    const GPAY_WEBHOOK = 212;

    /**
     * Logging levels
     *
     * @var array
     */
    protected static $levels = [
        self::DEBUG             => 'DEBUG',
        self::INFO              => 'INFO',
        self::NOTICE            => 'NOTICE',
        self::WARNING           => 'WARNING',
        self::ERROR             => 'ERROR',
        self::CRITICAL          => 'CRITICAL',
        self::ALERT             => 'ALERT',
        self::EMERGENCY         => 'EMERGENCY',
        self::GPAY_CRON         => 'GPAY_CRON',
        self::GPAY_WEBHOOK      => 'GPAY_WEBHOOK',
    ];

    /**
     * Adds a log record at the GPAY_CRON level
     *
     * @param  string $message
     * @param  array  $context
     * @return boolean
     */
    public function addCron($message, array $context = [])
    {
        return $this->addRecord(static::GPAY_CRON, $message, $context);
    }

    /**
     * Adds a log record at the GPAY_WEBHOOK level
     *
     * @param  string $message
     * @param  array  $context
     * @return boolean
     */
    public function addWebhook($message, array $context = [])
    {
        return $this->addRecord(static::GPAY_WEBHOOK, $message, $context);
    }

    /**
     * Adds a log record
     *
     * @param  integer $level
     * @param  string  $message
     * @param  array   $context
     * @return boolean
     */
    public function addRecord($level, $message, array $context = [])
    {
        if ($level === static::DEBUG) {
            $logEnabled = (bool) ObjectManager::getInstance()
                ->get('Magento\Framework\App\Config\ScopeConfigInterface')
                ->getValue('payment/googlepay/debug');
            if (!$logEnabled) {
                return false;
            }
        }
        return parent::addRecord($level, $message, $context);
    }
}
