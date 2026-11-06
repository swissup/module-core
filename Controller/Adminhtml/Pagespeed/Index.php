<?php

declare(strict_types=1);

namespace Swissup\Core\Controller\Adminhtml\Pagespeed;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

class Index extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Swissup_Core::pagespeed';

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Swissup_Core::pagespeed');
        $resultPage->addBreadcrumb(__('PageSpeed'), __('PageSpeed'));
        $resultPage->getConfig()->getTitle()->prepend(__('PageSpeed'));

        return $resultPage;
    }
}
