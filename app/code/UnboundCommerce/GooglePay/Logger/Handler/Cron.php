<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use UnboundCommerce\GooglePay\Logger\Logger;

class Cron extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/googlepay/cron.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::GPAY_CRON;

    /**
     * @var int
     */
    protected $level = Logger::GPAY_CRON;

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        return $record['level'] == $this->level;
    }
}
