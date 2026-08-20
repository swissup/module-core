<?php

namespace Swissup\Core\Block\Adminhtml\Config\Form\Fieldset;

use Magento\Backend\Block\Context;
use Magento\Backend\Block\Widget\Button;
use Magento\Backend\Model\Auth\Session;
use Magento\Framework\View\Helper\Js;
use Swissup\Core\Model\ComponentList\Loader;

class Modules extends \Magento\Config\Block\System\Config\Form\Fieldset
{
    private Loader $loader;

    public function __construct(
        Context $context,
        Session $authSession,
        Js $jsHelper,
        Loader $loader,
        array $data = []
    ) {
        parent::__construct($context, $authSession, $jsHelper, $data);
        $this->loader = $loader;
    }

    protected function _getHeaderTitleHtml($element)
    {
        return parent::_getHeaderTitleHtml($element)
            . '<span class="swissup-modules-summary">'
            . $this->getSummary()
            . '</span>'
            . '<span class="swissup-modules-meta">'
            . $this->getLastCheckHtml()
            . $this->getButtonHtml()
            . '</span>';
    }

    public function getSummary()
    {
        // The remote source is never queried here - it would slow down every
        // config page. The counters are refreshed by the js component.
        $installed = count($this->loader->setOfflineMode()->getInstalledItems());
        $outdated = count($this->loader->getOutdatedItems());

        $summary = [$this->escapeHtml(__('%1 installed', $installed))];
        if ($outdated) {
            $summary[] =
                '<span class="outdated">' .
                    $this->escapeHtml(__('%1 updates available', $outdated)) .
                '</span>';
        }

        return implode(' &middot; ', $summary);
    }

    public function getLastCheckHtml()
    {
        // the label is kept up to date by Swissup_Core/js/system-config/modules-refresh,
        // hence the timestamp and the always rendered span
        $time = $this->loader->getLastCheckTime();

        return '<span class="swissup-modules-lastcheck" data-time="' . (int) $time . '">'
            . ($time ? $this->getLastCheckLabel($time) : '')
            . '</span>';
    }

    // keep the wording in sync with Swissup_Core/js/system-config/modules-refresh
    public function getLastCheckLabel($time)
    {
        $seconds = max(0, time() - $time);
        $minutes = (int) floor($seconds / 60);

        if ($seconds < 20) {
            $ago = __('just now');
        } elseif ($minutes < 1) {
            $ago = __('less than a minute ago');
        } elseif ($minutes < 60) {
            $ago = __('%1 min ago', $minutes);
        } else {
            $ago = __('%1 h ago', (int) floor($minutes / 60));
        }

        return $this->escapeHtml(__('Last checked %1', $ago));
    }

    public function getButtonHtml()
    {
        return $this->getLayout()
            ->createBlock(Button::class)
            ->setLabel(__('Check for Updates'))
            ->setId('swissup-modules-refresh')
            ->setDataAttribute([
                'mage-init' => [
                    'Swissup_Core/js/system-config/modules-refresh' => [
                        'url' => $this->getUrl('swissup/module/refresh'),
                    ],
                ],
            ])
            ->toHtml();
    }
}
