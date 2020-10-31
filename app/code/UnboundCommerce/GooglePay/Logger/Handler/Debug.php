<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use UnboundCommerce\GooglePay\Logger\Logger;

class Debug extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/googlepay/debug.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::DEBUG;

    /**
     * @var int
     */
    protected $level = Logger::DEBUG;

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        return $record['level'] == $this->level;
    }
}
