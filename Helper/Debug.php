<?php

namespace Swissup\Core\Helper;

class Debug extends \Magento\Framework\App\Helper\AbstractHelper
{
    const POPUP_NAME = 'swissup_debug_popup';

    /**
     * Escaper
     *
     * @var \Magento\Framework\Escaper
     */
    protected $escaper;

    /**
     * @var \Magento\Framework\View\Layout
     */
    protected $layout;

    public function __construct(
        \Magento\Framework\App\Helper\Context $context,
        \Magento\Framework\Escaper $escaper,
        \Magento\Framework\View\Layout $layout
    ) {
        $this->escaper = $escaper;
        $this->layout = $layout;
        parent::__construct($context);
    }

    public function preparePopup($text, $title = 'Debug Information')
    {
        $block = $this->layout
            ->createBlock('Magento\Framework\View\Element\Text', self::POPUP_NAME)
            ->setNameInLayout(self::POPUP_NAME)
            ->setText(<<<HTML
<div id="swissup_popup" style="display:none">
    <pre>{$this->escaper->escapeHtml($text)}</pre>
</div>
<script>
    function showSwissupDebug() {
        require(['Magento_Ui/js/modal/alert'], function(alert) {
            alert({
                title: '{$this->escaper->escapeHtml($title)}',
                content: document.getElementById('swissup_popup').innerHTML
            });
        });
    }
</script>
HTML
            );

        return sprintf(
            "<a href='#' onclick=\"%s\">%s</a>",
            "showSwissupDebug()",
            __('Show response')
        );
    }
}
