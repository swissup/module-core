<?php
namespace Swissup\Core\Block\Adminhtml\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class FixAll extends \Magento\Config\Block\System\Config\Form\Field
{

    public function render(AbstractElement $element)
    {
        $url = $this->_urlBuilder->getUrl('swissup/theme/fixAll');
        $cacheUrl = $this->getUrl('adminhtml/cache/index');;
        $buttonText = __("Fix All");
        return <<<HTML
<tr>
    <td colspan="100">
        <div class="button-container">
            <button id="fix-all-themes" class="button action-configure" type="button"
                onclick="jQuery.ajax({
                    url: '{$url}',
                    method: 'POST',
                    dataType: 'json',
                    showLoader: true,
                    data: {
                        form_key:   window.FORM_KEY
                    },
                    success: function(data) {
                        alert(data.message);
                        jQuery('#row_swissup_core_troubleshooting_virtualcheck .themes-table table').hide();
                        jQuery('#fix-all-themes').hide();
                        jQuery('<tr><td>Please, <a href=&quot;{$cacheUrl}&quot; target=_blank>clear the cache</a></td></tr>').insertAfter('#row_swissup_core_troubleshooting_virtualcheck');

                    },
                    error: function() {
                        alert('An error occurred while updating theme types.');
                    }
                })"><span>$buttonText</span></button>
        </div>
    </td>
</tr>
HTML;
    }
}
