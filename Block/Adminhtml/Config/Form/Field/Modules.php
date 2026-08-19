<?php

namespace Swissup\Core\Block\Adminhtml\Config\Form\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Magento\Framework\Serialize\Serializer\Json;
use Swissup\Core\Model\ComponentList\Loader;

class Modules extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_template = 'Swissup_Core::config/field/modules.phtml';

    private Loader $loader;

    private Json $json;

    public function __construct(
        Context $context,
        Loader $loader,
        Json $json,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->loader = $loader;
        $this->json = $json;
    }

    public function render(AbstractElement $element)
    {
        // the table spans every column of the group, including the label one
        return $this->_decorateRowHtml($element, '<td colspan="100">' . $this->toHtml() . '</td>');
    }

    public function getItems()
    {
        // The remote source is never queried here - it would slow down every
        // config page. The versions are refreshed by the js component.
        $items = $this->loader->setOfflineMode()->getInstalledItems();

        usort($items, function ($a, $b) {
            return [!$a['is_outdated'], $a['name']] <=> [!$b['is_outdated'], $b['name']];
        });

        return $items;
    }

    public function getReleaseTitle(array $item)
    {
        if (empty($item['release_date'])) {
            return '';
        }

        return __('Released on %1', $this->formatDate($item['release_date'], \IntlDateFormatter::MEDIUM));
    }

    public function getLinks(array $item)
    {
        $links = [
            'docs_link' => __('Docs'),
            'changelog_link' => __('Changelog'),
        ];

        $result = [];
        foreach ($links as $key => $label) {
            if (!empty($item[$key])) {
                $result[$item[$key]] = $label;
            }
        }

        return $result;
    }

    public function getModulesUrl()
    {
        return $this->getUrl('swissup/module/installed');
    }

    public function getJsonConfig()
    {
        return $this->json->serialize([
            'url' => $this->getModulesUrl(),
        ]);
    }
}
