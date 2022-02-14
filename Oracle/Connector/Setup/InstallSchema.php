<?php
/*
 * Copyright © 2021, 2022 Oracle and/or its affiliates.
 *
 * Licensed under the Universal Permissive License v 1.0 as shown at https://oss.oracle.com/licenses/upl.
 */

namespace Oracle\Connector\Setup;

use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class InstallSchema implements InstallSchemaInterface
{
    const REGISTRATION_TABLE = 'oracle_connector_registration';
    const EVENT_QUEUE_TABLE = 'oracle_connector_event_queue';
    const ONE_MEGABYTE = 1048576;

    const TID_TABLE = 'oracle_connector_tid';

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $table = $installer->getConnection()
          ->newTable($installer->getTable(self::REGISTRATION_TABLE))
          ->addColumn(
              'entity_id',
              Table::TYPE_INTEGER,
              null,
              [
                  'identity' => true,
                  'unsigned' => true,
                  'nullable' => false,
                  'primary' => true
              ],
              'Registration Id'
          )
          ->addColumn(
              'name',
              Table::TYPE_TEXT,
              150,
              ['nullable' => false],
              'Registration Name'
          )
          ->addColumn(
              'environment',
              Table::TYPE_TEXT,
              50,
              ['nullable' => false, 'default' => 'Development'],
              'Registration Environment'
          )
          ->addColumn(
              'connector_key',
              Table::TYPE_TEXT,
              255,
              ['nullable' => false],
              'Registration Connector Key'
          )
          ->addColumn(
              'scope',
              Table::TYPE_TEXT,
              8,
              ['nullable' => false, 'default' => 'default'],
              'Registration Scope'
          )
          ->addColumn(
              'scope_id',
              Table::TYPE_INTEGER,
              null,
              ['nullable' => false, 'default' => '0', 'unsigned' => true],
              'Registration Scope Id'
          )
          ->addColumn(
              'scope_code',
              Table::TYPE_TEXT,
              32,
              ['nullable' => false, 'default' => ''],
              'Registration Scope Code'
          )
          ->addColumn(
              'is_active',
              Table::TYPE_SMALLINT,
              null,
              ['nullable' => false, 'default' => '0'],
              'Registration Active Flag'
          )
          ->addColumn(
              'is_protected',
              Table::TYPE_SMALLINT,
              null,
              ['nullable' => false, 'default' => '0'],
              'Registration Admin Protected Flag'
          )
          ->addColumn(
              'username',
              Table::TYPE_TEXT,
              '255',
              ['nullable' => true, 'default' => ''],
              'Registration Basic Auth Username'
          )
          ->addColumn(
              'password',
              Table::TYPE_TEXT,
              '255',
              ['nullable' => true, 'default' => ''],
              'Registration Basic Auth Password'
          )
          ->addColumn(
              'updated_at',
              Table::TYPE_TIMESTAMP,
              null,
              [],
              'Registration Updated Timestamp'
          )
          ->addIndex(
              $installer->getIdxName(self::REGISTRATION_TABLE, ['is_active']),
              ['is_active']
          )
          ->addIndex(
              $installer->getIdxName(self::REGISTRATION_TABLE, ['environment']),
              ['environment']
          )
          ->addIndex(
              $installer->getIdxName(self::REGISTRATION_TABLE, ['updated_at']),
              ['updated_at']
          )
          ->addIndex(
              $installer->getIdxName(
                  self::REGISTRATION_TABLE,
                  ['scope', 'scope_id'],
                  AdapterInterface::INDEX_TYPE_UNIQUE
              ),
              ['scope', 'scope_id'],
              ['type' => AdapterInterface::INDEX_TYPE_UNIQUE]
          )
          ->setComment('Oracle Magento Connector Registration');
        $installer->getConnection()->createTable($table);

        $table = $installer->getConnection()
          ->newTable($installer->getTable(self::EVENT_QUEUE_TABLE))
          ->addColumn(
              'entity_id',
              Table::TYPE_INTEGER,
              null,
              [
                  'identity' => true,
                  'unsigned' => true,
                  'nullable' => false,
                  'primary' => true
              ],
              'Queue Id'
          )
          ->addColumn(
              'site_id',
              Table::TYPE_TEXT,
              '120',
              ['nullable' => false],
              'Registration Connector Key'
          )
          ->addColumn(
              'event_data',
              TABLE::TYPE_TEXT,
              self::ONE_MEGABYTE,
              ['nullable' => false, 'default' => ''],
              'Queue Event Data'
          )
          ->addColumn(
              'created_at',
              TABLE::TYPE_TIMESTAMP,
              null,
              ['nullable' => false, 'default' => ''],
              'Original event trigger timestamp'
          )
          ->addColumn(
              'event_type',
              TABLE::TYPE_TEXT,
              32,
              ['nullable' => false, 'default' => ''],
              'Event trigger module'
          )
          ->addIndex(
              $installer->getIdxName(self::EVENT_QUEUE_TABLE, ['site_id', 'created_at']),
              ['site_id', 'created_at']
          )
          ->addIndex(
              $installer->getIdxName(self::EVENT_QUEUE_TABLE, ['site_id', 'event_type']),
              ['site_id', 'event_type']
          )
          ->setComment('Oracle Event Queue Table');
        $installer->getConnection()->createTable($table);

        self::createTidTable($setup);
        
        $installer->endSetup();
    }

    /**
     * Creates TID table.
     *
     * Does not run pre and post DB preparations
     *
     * @param SchemaSetupInterface $setup
     */
    public static function createTidTable(SchemaSetupInterface $setup)
    {
        if (!$setup->tableExists(self::TID_TABLE)) {
            $table = $setup->getConnection()
                ->newTable($setup->getTable(self::TID_TABLE))
                ->addColumn(
                    'id',
                    Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'unsigned' => true,
                        'nullable' => false,
                        'primary' => true
                    ],
                    'Tid Id'
                )
                ->addColumn(
                    'cart_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => false, 'unsigned' => true],
                    'Cart ID'
                )
                ->addColumn(
                    'order_id',
                    Table::TYPE_INTEGER,
                    null,
                    ['nullable' => true, 'unsigned' => true],
                    'Order ID'
                )
                ->addColumn(
                    'value',
                    Table::TYPE_TEXT,
                    255,
                    ['nullable' => false],
                    'TID Value'
                )
                ->addColumn(
                    'created_at',
                    Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => ''],
                    'Initial Cart add timestamp'
                )
                ->addIndex(
                    $setup->getIdxName(self::TID_TABLE, ['cart_id']),
                    ['cart_id']
                )
                ->addIndex(
                    $setup->getIdxName(self::TID_TABLE, ['order_id']),
                    ['order_id']
                )
                ->setComment('Oracle TID Table');
            $setup->getConnection()->createTable($table);
        }
    }
}
