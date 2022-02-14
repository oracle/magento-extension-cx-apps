<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Email\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    const TRIGGER_TABLE = 'oracle_email_trigger_queue';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $table = $installer->getConnection()
          ->newTable($installer->getTable(self::TRIGGER_TABLE))
          ->addColumn(
              'trigger_id',
              Table::TYPE_INTEGER,
              null,
              [
                  'identity' => true,
                  'unsigned' => true,
                  'nullable' => false,
                  'primary' => true
              ],
              'Trigger Id'
          )
          ->addColumn(
              'site_id',
              Table::TYPE_TEXT,
              '120',
              [ 'nullable' => false ],
              'Registration Connector Key'
          )
          ->addColumn(
              'store_id',
              Table::TYPE_INTEGER,
              null,
              [ 'nullable' => false ],
              'Store View'
          )
          ->addColumn(
              'message_id',
              Table::TYPE_TEXT,
              36,
              ['nullable' => false],
              'Message Id'
          )
          ->addColumn(
              'message_type',
              Table::TYPE_TEXT,
              36,
              ['nullable' => false],
              'Message Type'
          )
          ->addColumn(
              'model_type',
              Table::TYPE_TEXT,
              32,
              ['nullable' => false],
              'Model Type'
          )
          ->addColumn(
              'model_id',
              Table::TYPE_INTEGER,
              null,
              ['nullable' => false, 'unsigned' => 'true'],
              'Model Id'
          )
          ->addColumn(
              'customer_email',
              Table::TYPE_TEXT,
              255,
              ['nullable' => false],
              'Customer Email'
          )
          ->addColumn(
              'sent_message',
              Table::TYPE_SMALLINT,
              null,
              ['nullable' => false, 'default' => '0', 'unsigned' => true],
              'Sent Message'
          )
          ->addColumn(
              'triggered_at',
              Table::TYPE_TIMESTAMP,
              null,
              ['nullable' => false, 'default' => 'CURRENT_TIMESTAMP'],
              'Triggered Date'
          )
          ->addIndex(
              $installer->getIdxName(self::TRIGGER_TABLE, ['site_id', 'model_type', 'model_id']),
              ['site_id', 'model_type', 'model_id']
          )
          ->addIndex(
              $installer->getIdxName(self::TRIGGER_TABLE, ['site_id', 'sent_message', 'customer_email']),
              ['site_id', 'sent_message', 'customer_email']
          )
          ->addIndex(
              $installer->getIdxName(self::TRIGGER_TABLE, ['site_id', 'sent_message', 'triggered_at']),
              ['site_id', 'sent_message', 'triggered_at']
          )
          ->setComment('Oracle Magento Email Trigger Queue');
        $installer->getConnection()->createTable($table);
        $installer->endSetup();
    }
}
