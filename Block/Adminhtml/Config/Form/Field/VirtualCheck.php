<?php

namespace Swissup\Core\Block\Adminhtml\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\View\Design\ThemeInterface;
use Magento\Theme\Model\ResourceModel\Theme\CollectionFactory;
use Swissup\Core\Model\Theme\SourceFiles;

class VirtualCheck extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_template = 'config/field/virtual_check.phtml';

    private CollectionFactory $collectionFactory;

    private SourceFiles $sourceFiles;

    private ?array $themes = null;

    public function __construct(
        Context $context,
        CollectionFactory $collectionFactory,
        SourceFiles $sourceFiles,
        array $data = []
    ) {
        parent::__construct($context, $data);

        $this->collectionFactory = $collectionFactory;
        $this->sourceFiles = $sourceFiles;
    }

    public function render(AbstractElement $element)
    {
        $this->assign('configElement', $element);
        $html = $this->toHtml();

        return $this->_decorateRowHtml($element, "<td class='themes-table' colspan=\"3\">$html</td>");
    }

    public function getVirtualThemes(): array
    {
        if ($this->themes === null) {
            $this->themes = [];

            $collection = $this->collectionFactory->create()
                ->addFieldToFilter('type', ThemeInterface::TYPE_VIRTUAL);

            foreach ($collection as $theme) {
                $this->themes[] = $this->collectThemeData($theme);
            }
        }

        return $this->themes;
    }

    private function collectThemeData(ThemeInterface $theme): array
    {
        $unreadable = $this->sourceFiles->getUnreadable($theme);

        return [
            'title'  => $theme->getThemeTitle(),
            'path'   => $theme->getFullPath(),
            'status' => $unreadable
                ? __('Virtual (Magento can\'t read %1)', implode(', ', $unreadable))
                : __('Virtual'),
        ];
    }
}
