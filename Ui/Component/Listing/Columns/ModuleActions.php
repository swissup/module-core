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

        $hasSubscription = false;
        if ($subscription = $this->moduleFactory->create()->load('Swissup_SubscriptionChecker')) {
            $hasSubscription = (bool) $subscription->getLocal();
        }

        foreach ($dataSource['data']['items'] as & $item) {

            // add installer links
            $module = $this->moduleFactory->create()->load($item['code']);
            if ($module->getInstaller()->hasUpgradesDir()) {
                $item[$this->getData('name')]['installer'] = [
                    'href' => $this->urlBuilder->getUrl(
                        static::URL_PATH_INSTALL,
                        [
                            'code' => $item['code']
                        ]
                    ),
                    'label' => __('Open Installer')
                ];

                if ($module->isInstalled() &&
                    $module->getInstaller()->getUpgradesToRun()) {

                    $item[$this->getData('name')]['upgrade'] = [
                        'href' => $this->urlBuilder->getUrl(
                            static::URL_PATH_UPGRADE,
                            [
                                'code' => $item['code']
                            ]
                        ),
                        'label' => __('Run Upgrades')
                    ];
                }
            }

            // add external links
            foreach ($this->getData('links') as $link) {
                if (empty($item[$link['key']])) {
                    continue;
                }

                if ($link['key'] === 'download_link' && !$hasSubscription) {
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
