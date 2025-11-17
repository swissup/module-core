<?php

namespace Swissup\Core\Plugin;

use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\ResultInterface;

class FixHtmlMarkup
{
    /**
     * Newer libxml versions break markup of the widget rendered inside html element:
     * data-mage-init='{".."}' becomes data-mage-init="{".."}"
     * This code revert the broken data-mage-init attributes to data-mage-init='{".."}'
     */
    public function afterRenderResult(
        ResultInterface $subject,
        ResultInterface $result,
        ResponseInterface $httpResponse
    ) {
        $html = $httpResponse->getBody();

        if (strpos($html, 'data-mage-init="{"') === false) {
            return $result;
        }

        $search = 'data-mage-init="{"';
        $replace = "data-mage-init='{\"";
        $offset = 0;

        while (($pos = strpos($html, $search, $offset)) !== false) {
            $closePos = strpos($html, '}"', $pos + strlen($search));
            if ($closePos === false) {
                break;
            }

            $html = substr_replace($html, "}'", $closePos, 2);
            $html = substr_replace($html, $replace, $pos, strlen($search));
            $offset = $closePos + 2;
        }

        $httpResponse->setBody($html);

        return $result;
    }
}
