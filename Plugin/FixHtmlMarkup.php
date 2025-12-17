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

        $mapping = [
            'data-mage-init="{"' => 'data-mage-init=\'{"',
            'data-post="{"' => 'data-post=\'{"',
            'data-config="{"' => 'data-config=\'{"',
        ];

        foreach ($mapping as $search => $replace) {
            $offset = 0;
            while (($pos = strpos($html, $search, $offset)) !== false) {
                $closePos = strpos($html, '}"', $pos + strlen($search));
                $quotePos = strpos($html, '="', $pos + strlen($search));
                $isMarkupBroken = $quotePos !== false && $quotePos < $closePos;

                if ($closePos === false || $isMarkupBroken) {
                    break;
                }

                $html = substr_replace($html, "}'", $closePos, 2);
                $html = substr_replace($html, $replace, $pos, strlen($search));
                $offset = $closePos + 2;
            }
        }

        $httpResponse->setBody($html);

        return $result;
    }
}
