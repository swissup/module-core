<?php

namespace Swissup\Core\Block\Adminhtml;

use Magento\Backend\Block\Widget\Button;

class HowToUpdate extends \Magento\Backend\Block\Template
{
    protected $_template = 'Swissup_Core::how-to-update.phtml';

    public function getUpdateCommands()
    {
        return implode("\n", [
            'composer update "swissup/*" -w &&\\',
            'bin/magento setup:upgrade --safe-mode=1 &&\\',
            'bin/magento setup:di:compile &&\\',
            'bin/magento setup:static-content:deploy',
        ]);
    }

    public function getButtonHtml()
    {
        return $this->getLayout()
            ->createBlock(Button::class)
            ->setLabel(__('How to Update'))
            ->setId('swissup-modules-howto')
            ->setDataAttribute([
                'mage-init' => [
                    'Swissup_Core/js/modules-howto' => [],
                ],
            ])
            ->toHtml();
    }
}
