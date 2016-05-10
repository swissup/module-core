<?php

namespace Swissup\Core\Model\Module;

use Swissup\Core\Api\Data\ModuleUpgradeInterface;

abstract class Upgrade implements ModuleUpgradeInterface
{
    protected $installer;

    /**
     * @var array Store ids, where the module will be installed
     */
    protected $storeIds = array();

    /**
     * Used to guarantee unique backup names in case of duplicate name and date
     *
     * @var int
     */
    protected static $backupIterator = 0;

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $localeDate;

    /**
     * Store manager
     *
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param PageFactory $pageFactory
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $localeDate,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->objectManager = $objectManager;
        $this->localeDate = $localeDate;
        $this->storeManager = $storeManager;
    }

    /**
     * Additional operations could be done from this method
     */
    public function up()
    {
    }

    /**
     * Retrieve the list of operation to run,
     * including module depends.
     *
     * Supported operations:
     *  configuration       @see runConfiguration
     *  cmsblock            @see runCmsblock
     *  cmspage             @see runCmspage
     *  easyslide           @see runEasyslide
     *  easybanner          @see runEasybanner
     *  prolabels           @see runProlabels
     *  productAttribute    @see runProductAttribute
     *
     * @return array
     */
    public function getOperations()
    {
        return [];
    }

    public function upgrade()
    {
        foreach ($this->getOperations() as $operation => $instructions) {
            $method = 'run' . ucfirst($operation);
            if (method_exists($this, $method)) {
                $this->$method($instructions);
            }
        }
        $this->up();
    }

    public function setInstaller($installer)
    {
        $this->installer = $installer;
    }

    /**
     * Set store ids to run the upgrade on
     *
     * @return
     */
    public function setStoreIds(array $ids)
    {
        if ($this->storeManager->isSingleStoreMode()) {
            $ids = [$this->storeManager->getStore()->getId()];
        }
        $this->storeIds = $ids;
        return $this;
    }
    /**
     * Retrieve store ids
     *
     * @return array
     */
    public function getStoreIds()
    {
        return $this->storeIds;
    }

    /**
     * @return \Swissup\Core\Model\Module\MessageLogger
     */
    public function getMessageLogger()
    {
        return $this->installer->getMessageLogger();
    }

    /**
     * Backup and create new cms blocks
     *
     * @param  array $data Array of block data arrays
     * <pre>
     * [
     *     'header_cms_links' => [
     *         'title' => 'Argento Header Cms Links',
     *         'identifier' => 'header_cms_links',
     *         'is_active' => '1',
     *         'content' => ''
     *     ]
     * ]
     * </pre>
     * @return void
     */
    public function runCmsblock($data)
    {
        $isSingleStoreMode = $this->storeManager->isSingleStoreMode();
        foreach ($data as $itemData) {
            // 1. backup existing blocks
            $collection = $this->objectManager
                ->create('Magento\Cms\Model\ResourceModel\Block\Collection')
                ->addStoreFilter($this->getStoreIds())
                ->addFieldToFilter('identifier', $itemData['identifier']);

            foreach ($collection as $block) {
                $block->load($block->getId()); // load stores
                $storesToLeave = array_diff($block->getStoreId(), $this->getStoreIds());
                if (count($storesToLeave) && !$isSingleStoreMode) {
                    // unassign block from stores that will have new blocks
                    $block->setStores($storesToLeave);
                } else {
                    // disable block, because it has not store to assign to
                    $block->setIsActive(0)
                        ->setIdentifier($this->getBackupIdentifier($block->getIdentifier()));
                }

                try {
                    $block->save();
                } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                    // $this->_fault('cmsblock_backup', $e);
                } catch (Exception $e) {
                    // $this->_fault('cmsblock_backup', $e);
                }
            }

            // 2. create new block
            try {
                $this->objectManager->create('Magento\Cms\Model\Block')
                    ->setData($itemData)
                    ->setStores($this->getStoreIds()) // see Magento\Cms\Model\ResourceModel\Block::_afterSave
                    ->save();
            } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                // $this->_fault('cmsblock_save', $e);
            } catch (Exception $e) {
                // $this->_fault('cmsblock_save', $e);
            }
        }
    }

    /**
     * Backup and create new cms pages
     *
     * @param  array $data Array of page data arrays
     * <pre>
     * [
     *     'home' => [
     *         'title' => 'Argento Essence',
     *         'identifier' => 'home',
     *         'page_layout' => '1column'
     *         'content' => '',
     *         'is_active' => 1
     *     ]
     * ]
     * </pre>
     * @return void
     */
    public function runCmspage($data)
    {
        // bugfix: cleanup links in url_rewrite table before creating new pages.
        // Related to https://github.com/magento/magento2/issues/4113
        $this->cleanupUrlRewrites(
            'cms-page',
            array_map(
                function ($item) {
                    return $item['identifier'];
                },
                $data
            )
        );

        $isSingleStoreMode = $this->storeManager->isSingleStoreMode();
        foreach ($data as $itemData) {
            // 1. backup existing pages
            $collection = $this->objectManager
                ->create('Magento\Cms\Model\ResourceModel\Page\Collection')
                ->addStoreFilter($this->getStoreIds())
                ->addFieldToFilter('identifier', $itemData['identifier']);

            foreach ($collection as $page) {
                $page->load($page->getId()); // load stores
                $storesToLeave = array_diff($page->getStoreId(), $this->getStoreIds());
                if (count($storesToLeave) && !$isSingleStoreMode) {
                    // unassign page from stores that will have new pages
                    $page->setStores($storesToLeave);
                } else {
                    // disable page, because it has not store to assign to
                    $page->setIsActive(0)
                        ->setIdentifier($this->getBackupIdentifier($page->getIdentifier()));
                }

                try {
                    $page->save();
                } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                    // $this->_fault('cmspage_backup', $e);
                } catch (Exception $e) {
                    // $this->_fault('cmspage_backup', $e);
                }
            }

            // 2. create new page
            try {
                $this->objectManager->create('Magento\Cms\Model\Page')
                    ->setData($itemData)
                    ->setStores($this->getStoreIds()) // see Magento\Cms\Model\ResourceModel\Page::_afterSave
                    ->save();
            } catch (\Magento\Framework\Exception\AlreadyExistsException $e) {
                // $this->_fault('cmspage_save', $e);
            } catch (Exception $e) {
                // $this->_fault('cmspage_save', $e);
            }
        }
    }

    /**
     * Create new slider.
     * If duplicate is found - do nothing.
     *
     * @param  array $data Array of slider data arrays
     * @return void
     */
    public function runEasyslide($data)
    {
        foreach ($data as $itemData) {
            $slider = $this->objectManager
                ->create('Swissup\EasySlide\Model\Slider')
                ->load($itemData['identifier'], 'identifier');
            if ($slider->getId()) {
                continue;
            }

            try {
                $slider->setData($itemData)->save();
            } catch (Exception $e) {
                // $this->_fault('easyslide_slider_save', $e);
                continue;
            }

            $slideDefaults = array(
                'is_active'   => 1,
                'target'      => '_self',
                'description' => '',
                'slider_id'   => $slider->getId()
            );
            foreach ($itemData['slides'] as $slide) {
                try {
                    $this->objectManager
                        ->create('Swissup\EasySlide\Model\Slides')
                        ->setData(array_merge($slideDefaults, $slide))
                        ->save();
                } catch (Exception $e) {
                    // $this->_fault('easyslide_slide_save', $e);
                    continue;
                }
            }
        }
    }

    /**
     * Removes url rewrite entries, that match requested conditions
     *
     * @param  string $entityType
     * @param  array $requestPaths
     * @return void
     */
    protected function cleanupUrlRewrites($entityType, $requestPaths)
    {
        $urlCollection = $this->objectManager
            ->create('Magento\UrlRewrite\Model\ResourceModel\UrlRewriteCollection')
            ->addFieldToFilter('entity_type', $entityType)
            ->addFieldToFilter('request_path', ['in' => $requestPaths])
            ->addFieldToFilter('store_id', ['in' => $this->getStoreIds()]);

        foreach ($urlCollection as $item) {
            $item->delete();
        }
    }

    /**
     * Returns unique string. Used to backup existing pages, blocks, etc
     * This method is not 100% bullet proof, but there is very low chance to
     * receive duplicate string.
     *
     * @param string $identifier
     * @return string
     */
    protected function getBackupIdentifier($identifier)
    {
        return $identifier
            . '_backup_'
            . self::$backupIterator++
            . '_'
            . $this->localeDate->date()->format('Y-m-d-H-i-s');
    }
}
