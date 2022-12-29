<?php
namespace Swissup\Core\Block\Adminhtml\Config\Form\Field;

use Magento\Framework\Data\Form\Element\AbstractElement;

class FixAll extends \Magento\Config\Block\System\Config\Form\Field
{

    public function render(AbstractElement $element)
    {
        $buttonText = __("Fix All");
        return <<<HTML
<tr>
    <td colspan="100">
        <div class="button-container">
            <button id="fix-all-themes" class="button action-configure" type="button"><span>$buttonText</span></button>
        </div>
        <script type="text/javascript">

        </script>
    </td>
</tr>
HTML;
    }
}
