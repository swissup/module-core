<?php

namespace Swissup\Core\Setup;

use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\DB\Ddl\Table;
use Magento\Framework\DB\Adapter\AdapterInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    /**
     * Installs DB schema for a module
     *
     * @param SchemaSetupInterface $setup
     * @param ModuleContextInterface $context
     * @return void
     */
    public function upgrade(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        if (version_compare($context->getVersion(), '1.0.1', '<')) {
            $this->createSwissupCoreModuleTable($setup);
        }

        $setup->endSetup();
    }

    protected function createSwissupCoreModuleTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getConnection()
            ->newTable($setup->getTable('swissup_core_module'))
            ->addColumn(
                'code',
                Table::TYPE_TEXT,
                50,
                ['nullable' => false, 'primary' => true,]
            )
            ->addColumn('data_version', Table::TYPE_TEXT, 50)
            ->addColumn('identity_key', Table::TYPE_TEXT, 255)
            ->addColumn('store_ids', Table::TYPE_TEXT, 64);
        $setup->getConnection()->createTable($table);
    }
}
