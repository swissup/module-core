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

        if (version_compare($context->getVersion(), '1.1.0', '<')) {
            $this->addComposerColumnsToSwissupCoreModuleTable($setup);
        }

        if (version_compare($context->getVersion(), '1.2.0', '<')) {
            $this->addFullTextSearchIndex($setup);
        }

        if (version_compare($context->getVersion(), '1.2.1', '<')) {
            $this->addLatestVersionColumn($setup);
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

    protected function addComposerColumnsToSwissupCoreModuleTable(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable('swissup_core_module');
        $setup->getConnection()->addColumn(
            $table,
            'name',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 50,
                'after' => 'code',
                'comment' => 'Package Name'
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'description',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'after' => 'name',
                'comment' => 'Package Description'
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'keywords',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'after' => 'description',
                'comment' => 'Keywords'
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'type',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 32,
                'comment' => 'Package Type'
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'version',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 50,
                'comment' => 'Version'
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'release_date',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_DATETIME,
                'comment' => 'Release Date'
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'link',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'default' => null,
                'comment' => 'Module Homepage'
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'download_link',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'default' => null,
                'comment' => 'Module Download Link'
            ]
        );
        $setup->getConnection()->addColumn(
            $table,
            'identity_key_link',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 255,
                'default' => null,
                'comment' => 'Identity Key Link'
            ]
        );
    }

    protected function addFullTextSearchIndex(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable('swissup_core_module');
        $setup->getConnection()->addIndex(
            $table,
            $setup->getConnection()->getIndexName(
                $table,
                ['code', 'name', 'description', 'keywords'],
                AdapterInterface::INDEX_TYPE_FULLTEXT
            ),
            ['code', 'name', 'description', 'keywords'],
            AdapterInterface::INDEX_TYPE_FULLTEXT
        );
    }

    protected function addLatestVersionColumn(SchemaSetupInterface $setup)
    {
        $table = $setup->getTable('swissup_core_module');
        $setup->getConnection()->addColumn(
            $table,
            'latest_version',
            [
                'type' => \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                'length' => 50,
                'after' => 'version',
                'comment' => 'Latest Version'
            ]
        );
    }
}
