<?php

namespace Swissup\Core\Plugin;

use Magento\AdminNotification\Model\System\Message\CacheOutdated;
use Magento\Framework\UrlInterface;

class CacheOutdatedMessage
{
    private $urlBuilder;

    public function __construct(UrlInterface $urlBuilder)
    {
        $this->urlBuilder = $urlBuilder;
    }

    public function afterGetText(CacheOutdated $subject, $result)
    {
        $title = __('Flush Magento Cache');
        $success = __('Magento Cache was successfully flushed');
        $url = $this->urlBuilder->getUrl('adminhtml/cache/flushSystem');
        $link = <<<HTML
            <a href="{$url}" onclick="
                require(['jquery'], ($) => {
                    $.get({
                        url: '{$url}',
                        showLoader: true,
                        success: () => $(this).parent()
                            .removeClass('message-warning')
                            .addClass('message-success')
                            .html('{$success}')
                    });
                });
                return false;
            ">{$title}</a>
        HTML;

        return $result . ' ' . $link;
    }
}
