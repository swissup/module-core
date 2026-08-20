<?php

namespace Swissup\Core\Controller\Adminhtml\Theme;

use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;
use Swissup\Core\Model\Theme\SourceFiles;

class FixVirtualThemes extends \Magento\Backend\App\Action
{
    private CollectionFactory $collectionFactory;

    private SourceFiles $sourceFiles;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        SourceFiles $sourceFiles
    ) {
        parent::__construct($context);
        $this->collectionFactory = $collectionFactory;
        $this->sourceFiles = $sourceFiles;
    }

    public function execute()
    {
        $virtualThemes = $this->collectionFactory->create()
            ->addFieldToFilter('type', ThemeInterface::TYPE_VIRTUAL);

        $fixed = 0;
        $skipped = [];
        foreach ($virtualThemes as $theme) {
            // Physical theme without source files breaks the storefront with
            // "Required parameter 'theme_dir' was not passed"
            if (!$this->sourceFiles->makeReadable($theme)) {
                $skipped[] = $theme->getThemeTitle();
                continue;
            }

            $theme->setType(ThemeInterface::TYPE_PHYSICAL)->save();
            $fixed++;
        }

        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);
        $resultJson->setData([
            'fixed' => $fixed,
            'skipped' => count($skipped),
            'message' => $this->getMessage($fixed, $skipped),
        ]);

        return $resultJson;
    }

    private function getMessage(int $fixed, array $skipped): string
    {
        if (!$skipped) {
            return (string) __('Virtual themes fixed.');
        }

        $skippedList = implode(', ', $skipped);

        if (!$fixed) {
            return (string) __('Nothing was fixed. Magento is unable to read the source files of: %1', $skippedList);
        }

        return (string) __(
            '%1 theme(s) fixed. Magento is unable to read the source files of: %2',
            $fixed,
            $skippedList
        );
    }
}
