<?php
/**
 * PHP version 7
 * Copyright © Mobegic Inc. DBA Unbound Commerce.
 * All rights reserved.
 */

namespace UnboundCommerce\GooglePay\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 *  Class InstallSchema installs database schema needed for the GooglePay extension.
 */
class InstallSchema implements InstallSchemaInterface
{
    /**
     * Installs database schema
     *
     * @param  SchemaSetupInterface   $setup
     * @param  ModuleContextInterface $context
     * @return void
     * @throws \Zend_Db_Exception
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $adyenTable = $setup->getConnection()
            ->newTable($setup->getTable('googlepay_adyen_webhook'))
            ->addColumn(
                'webhook_id', Table::TYPE_INTEGER, null, ['identity' => true,
                'primary' => true, 'nullable' => false], 'GooglePay Adyen Webhook Id'
            )
            ->addColumn('psp_reference', Table::TYPE_TEXT, 255, ['nullable' => true], 'Psp Reference')
            ->addColumn('original_reference', Table::TYPE_TEXT, 255, ['nullable' => true], 'Original Reference')
            ->addColumn('merchant_reference', Table::TYPE_TEXT, 255, ['nullable' => true], 'Merchant Reference')
            ->addColumn('event_code', Table::TYPE_TEXT, 255, ['nullable' => true], 'Event Code')
            ->addColumn('success', Table::TYPE_TEXT, 255, ['nullable' => true], 'Success')
            ->addColumn('payment_method', Table::TYPE_TEXT, 255, ['nullable' => true], 'Payment Method')
            ->addColumn('amount_value', Table::TYPE_TEXT, 255, ['nullable' => true], 'Amount - Value')
            ->addColumn('amount_currency', Table::TYPE_TEXT, 255, ['nullable' => true], 'Amount - Currency')
            ->addColumn('reason', Table::TYPE_TEXT, 255, ['nullable' => true], 'Reason')
            ->addColumn('additional_data', Table::TYPE_TEXT, null, ['nullable' => true], 'AdditionalData')
            ->addColumn(
              'created_at',
              Table::TYPE_TIMESTAMP,
              null,
              ['nullable' => false,
              'default' => Table::TIMESTAMP_INIT],
              'Created At')
            ->addColumn(
                'updated_at',
                Table::TYPE_TIMESTAMP,
                null,
                ['nullable' => false,
                'default' => Table::TIMESTAMP_INIT_UPDATE],
                'Updated At')
            ->addColumn('processed', Table::TYPE_BOOLEAN, null, ['nullable' => false, 'default' => 0], 'Processed Webhook')
            ->addColumn(
              'is_production',
              Table::TYPE_BOOLEAN,
              null,
              ['nullable' => false,
              'default' => 0],
              'Is in Production Environment')
            ->addIndex($setup->getIdxName('googlepay_adyen_webhook', ['psp_reference']), ['psp_reference'])
            ->addIndex($setup->getIdxName('googlepay_adyen_webhook', ['event_code']), ['event_code'])
            ->addIndex(
                $setup->getIdxName('googlepay_adyen_webhook', ['psp_reference', 'event_code'], AdapterInterface::INDEX_TYPE_INDEX),
                ['psp_reference', 'event_code'],
                ['type' => AdapterInterface::INDEX_TYPE_INDEX]
            )

            ->setComment('GooglePay Adyen Webhook');

        $setup->getConnection()->createTable($adyenTable);

        $setup->endSetup();
    }
}
