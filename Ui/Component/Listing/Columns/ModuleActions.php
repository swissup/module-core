<?php

namespace Swissup\Core\Ui\Component\Listing\Columns;

use Magento\Framework\UrlInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;

class ModuleActions extends \Magento\Ui\Component\Listing\Columns\Column
{
    /**
     * Url path
     */
    const URL_PATH_EDIT = 'cms/block/edit';
    const URL_PATH_DELETE = 'cms/block/delete';
    const URL_PATH_DETAILS = 'cms/block/details';

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

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
        array $components = [],
        array $data = []
    ) {
        $this->urlBuilder = $urlBuilder;
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
            foreach ($this->getData('links') as $link) {
                if (empty($item[$link['key']])) {
                    continue;
                }

                $item[$this->getData('name')][$link['key']] = [
                    '_target' => 'blank',
                    'href'  => $item[$link['key']],
                    'label' => __($link['label'])
                ];
            }

            // $item[$this->getData('name')] = [
            //     'edit' => [
            //         'href' => $this->urlBuilder->getUrl(
            //             static::URL_PATH_EDIT,
            //             [
            //                 'code' => $item['code']
            //             ]
            //         ),
            //         'label' => __('Edit')
            //     ],
            //     'details' => [
            //         'href' => $this->urlBuilder->getUrl(
            //             static::URL_PATH_DETAILS,
            //             [
            //                 'code' => $item['code']
            //             ]
            //         ),
            //         'label' => __('Details')
            //     ]
            // ];
        }
        return $dataSource;
    }
}
