<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Logger\Handler;

use Magento\Framework\Logger\Handler\Base;
use UnboundCommerce\GooglePay\Logger\Logger;

class Info extends Base
{
    /**
     * @var string
     */
    protected $fileName = '/var/log/googlepay/info.log';

    /**
     * @var int
     */
    protected $loggerType = Logger::INFO;

    /**
     * @var int
     */
    protected $level = Logger::INFO;

    /**
     * {@inheritdoc}
     */
    public function isHandling(array $record)
    {
        return $record['level'] == $this->level;
    }
}
