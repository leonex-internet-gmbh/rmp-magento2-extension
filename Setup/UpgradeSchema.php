<?php

namespace Leonex\RiskManagementPlatform\Setup;

use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * Leonex\RiskManagementPlatform\Setup\UpgradeSchema
 */
class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
	{
		$setup->startSetup();

        if (version_compare($context->getVersion(), '2.0.2', '<')) {
            $this->setupLoggingCapabilities($setup);
        }

        if (version_compare($context->getVersion(), '2.1.0', '<')) {
            $this->setupLoggingCreationIndex($setup);
        }
		
		$setup->endSetup();
	}

    private function setupLoggingCapabilities(SchemaSetupInterface $setup): void
    {
        $table = $setup->getConnection()->newTable($setup->getTable('rmp_log'));

        $table
            ->addColumn('log_id', Table::TYPE_INTEGER, null, [
                'identity' => true,
                'nullable' => false,
                'primary'  => true,
                'unsigned' => true,
            ], 'Log ID')
            ->addColumn('quote_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => true,
            ], 'Associated quote id')
            ->addColumn('order_id', Table::TYPE_INTEGER, null, [
                'unsigned' => true,
                'nullable' => true,
            ], 'Associated order id')
            ->addColumn('level', Table::TYPE_TEXT, 15, [
                'nullable' => false,
            ], 'Log level')
            ->addColumn('tag', Table::TYPE_TEXT, 50, [
                'nullable' => true,
                ], 'Short tag for message')
            ->addColumn('message', Table::TYPE_TEXT, 65536, [
                'nullable' => false,
                ], 'Main message')
            ->addColumn('payload', Table::TYPE_TEXT, 65536, [
                'nullable' => true,
                ], 'JSON formatted payload')
            ->addColumn('created_at', Table::TYPE_TIMESTAMP, null, [
                'nullable' => false,
                'default' => Table::TIMESTAMP_INIT
            ], 'Created at')
            ->addForeignKey(
                $setup->getFkName('rmp_log', 'quote_id', 'quote', 'entity_id'),
                'quote_id',
                $setup->getTable('quote'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_SET_NULL
            )
            ->addForeignKey(
                $setup->getFkName('rmp_log', 'order_id', 'sales_order', 'entity_id'),
                'order_id',
                $setup->getTable('sales_order'),
                'entity_id',
                \Magento\Framework\DB\Ddl\Table::ACTION_RESTRICT
            )
        ;

        $setup->getConnection()->createTable($table);
    }

    private function setupLoggingCreationIndex(SchemaSetupInterface $setup): void
    {
        $setup->getConnection()->addIndex(
            $setup->getTable('rmp_log'),
            $setup->getIdxName(
                $setup->getTable('rmp_log'),
                ['created_at'],
                \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
            ),
            ['created_at'],
            \Magento\Framework\DB\Adapter\AdapterInterface::INDEX_TYPE_INDEX
        );
    }
}
