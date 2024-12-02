<?php
namespace Swissup\Core\Model\System\Message;

class CacheOutdated implements \Magento\Framework\Notification\MessageInterface
{
    protected $_urlBuilder;
    protected $_authorization;
    protected $_cacheTypeList;

    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization,
        \Magento\Framework\UrlInterface $urlBuilder,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
    ) {
        $this->_authorization = $authorization;
        $this->_urlBuilder = $urlBuilder;
        $this->_cacheTypeList = $cacheTypeList;
    }

    protected function _getCacheTypesForRefresh()
    {
        $output = [];
        foreach ($this->_cacheTypeList->getInvalidated() as $type) {
            $output[] = $type->getCacheType();
        }
        return $output;
    }

    public function getText()
    {
        $cacheTypes = implode(', ', $this->_getCacheTypesForRefresh());
        $message = __('One or more of the Cache Types are invalidated: %1. ', $cacheTypes) . ' ';
        $url = $this->_urlBuilder->getUrl('adminhtml/cache/flushSystem');
        $message .= __(
            '<button type="button" onclick="
                require([\'jquery\', \'mage/translate\'], function($) {
                    $.ajax({
                        url: \'%1\',
                        type: \'GET\',
                        showLoader: true
                    }).done(function() {
                        require([\'Magento_Ui/js/modal/alert\'], function(alert) {
                            alert({
                                title: $.mage.__(\'Success\'),
                                content: $.mage.__(\'The cache was flushed\'),
                                actions: {
                                    always: function() {
                                        location.reload();
                                    }
                                }
                            });
                        });
                    });
                });
                return false;
            " class="action-primary">Flush Cache Storage</button>',
            $url
        );
        return $message;
    }

    public function isDisplayed()
    {
        return $this->_authorization->isAllowed('Magento_Backend::cache') 
            && count($this->_getCacheTypesForRefresh()) > 0;
    }

    public function getIdentity()
    {
        return md5('cache' . implode(':', $this->_getCacheTypesForRefresh()));
    }

    public function getLink()
    {
        return $this->_urlBuilder->getUrl('adminhtml/cache');
    }

    public function getSeverity()
    {
        return \Magento\Framework\Notification\MessageInterface::SEVERITY_CRITICAL;
    }
}
