<?php

namespace Swissup\Core\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Swissup\Core\Model\ModuleFactory;

class ModuleActions extends \Magento\Ui\Component\Listing\Columns\Column
{
    const URL_PATH_INSTALL = 'swissup/installer/form';
    const URL_PATH_UPGRADE = 'swissup/installer/upgrade';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Swissup\Core\Model\ModuleFactory
     */
    protected $moduleFactory;

    /**
     * Constructor
     *
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UrlInterface $urlBuilder
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UrlInterface $urlBuilder,
        ModuleFactory $moduleFactory,
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->moduleFactory = $moduleFactory;
        parent::__construct($context, $uiComponentFactory, $components, $data);
    }

    /**
     * Prepare Data Source
     *
     * @param array $dataSource
     * @return array
     */
    public function prepareDataSource(array $dataSource)
    {
        if (!isset($dataSource['data']['items'])) {
            return $dataSource;
        }

        foreach ($dataSource['data']['items'] as & $item) {
            // add external links
            foreach ($this->getData('links') as $link) {
                if (empty($item[$link['key']])) {
                    continue;
                }

                $item[$this->getData('name')][$link['key']] = [
                    'href'  => $item[$link['key']],
                    'label' => __($link['label'])
                ];
            }
        }
        return $dataSource;
    }
}
