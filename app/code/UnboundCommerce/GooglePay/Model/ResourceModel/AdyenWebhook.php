<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use UnboundCommerce\GooglePay\Api\Data\AdyenWebhookInterface;

/**
 * Class AdyenWebhook
 */
class AdyenWebhook extends AbstractDb
{
    const TABLE_NAME = 'googlepay_adyen_webhook';

    /**
     * Constructor
     */
    public function _construct()
    {
        $this->_init(static::TABLE_NAME, AdyenWebhookInterface::WEBHOOK_ID);
    }

    /**
     * Retrieve Adyen webhook
     *
     * @param  string $pspReference
     * @param  string $eventCode
     * @param  string $success
     * @param  string $originalReference
     * @return array
     */
    public function getWebhook($pspReference, $eventCode, $success, $originalReference)
    {
        $select = $this->getConnection()->select()
            ->from(['webhook' => $this->getTable(static::TABLE_NAME)])
            ->where('webhook.psp_reference=?', $pspReference)
            ->where('webhook.event_code=?', $eventCode)
            ->where('webhook.success=?', $success);

        if ($originalReference) {
            $select->where('webhook.original_reference=?', $originalReference);
        }

        return $this->getConnection()->fetchAll($select);
    }
}
