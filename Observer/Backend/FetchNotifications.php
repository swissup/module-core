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
     * @param \Swissup\Core\Model\Notification\FeedFactory $feedFactory
     * @param \Magento\Backend\Model\Auth\Session $backendAuthSession
     */
    public function __construct(
        \Swissup\Core\Model\Notification\FeedFactory $feedFactory,
        \Magento\Backend\Model\Auth\Session $backendAuthSession
    ) {
        $this->feedFactory = $feedFactory;
        $this->backendAuthSession = $backendAuthSession;
    }

    /**
     * Predispath admin action controller
     *
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        if ($this->backendAuthSession->isLoggedIn()) {
            $feedModel = $this->feedFactory->create();
            $feedModel->checkUpdate();
        }
    }
}
