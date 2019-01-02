<?php

namespace Swissup\Core\Observer\Backend;

class FetchNotifications implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Swissup\Core\Model\Notification\FeedFactory
     */
    protected $feedFactory;

    /**
     * @var \Magento\Backend\Model\Auth\Session
     */
    protected $backendAuthSession;

    /**
     * @var \Magento\Backend\Helper\Data
     */
    protected $helper;

    /**
     * @param \Swissup\Core\Model\Notification\FeedFactory $feedFactory
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     * @param \Magento\Backend\Helper\Data $helper
     */
    public function __construct(
        \Swissup\Core\Model\Notification\FeedFactory $feedFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession,
        \Magento\Backend\Helper\Data $helper
    ) {
        $this->feedFactory = $feedFactory;
        $this->backendAuthSession = $backendAuthSession;
        $this->helper = $helper;
    }

    /**
     * Predispath admin action controller
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if (!$this->helper->isModuleOutputEnabled('Magento_AdminNotification')) {
            return;
        }

        if ($this->backendAuthSession->isLoggedIn()) {
            $feedModel = $this->feedFactory->create();
            $feedModel->checkUpdate();
        }
    }
}
