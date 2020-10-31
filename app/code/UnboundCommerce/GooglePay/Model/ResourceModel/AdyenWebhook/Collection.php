<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model\ResourceModel\AdyenWebhook;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use UnboundCommerce\GooglePay\Model\ResourceModel\AdyenWebhook as AdyenWebhookResourceModel;
use UnboundCommerce\GooglePay\Model\PaymentGateway\AdyenWebhook as AdyenWebhookModel;

/**
 * Class Collection
 */
class Collection extends AbstractCollection
{
    /**
     * Constructor
     */
    public function _construct()
    {
        $this->_init(AdyenWebhookModel::class, AdyenWebhookResourceModel::class);
    }

    /**
     * Filter Adyen webhooks table to get unprocessed webhooks created before 10 minutes
     *
     * @return $this
     * @throws \Exception
     */
    public function unprocessedWebhooksFilter()
    {
        $dateEnd = new \DateTime();
        $dateEnd->modify('-10 minute');
        $dateRange = ['to' => $dateEnd, 'datetime' => true];
        $this->addFieldToFilter('processed', 0);
        $this->addFieldToFilter('created_at', $dateRange);
        return $this;
    }
}
