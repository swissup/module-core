<?php

namespace Swissup\Core\Controller\Adminhtml\Security;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Swissup\Core\Model\PatchStatus;

class Index extends Action implements HttpGetActionInterface
{
    const ADMIN_RESOURCE = 'Swissup_Core::security';

    protected PatchStatus $patchStatus;

    public function __construct(
        Action\Context $context,
        PatchStatus $patchStatus
    ) {
        parent::__construct($context);
        $this->patchStatus = $patchStatus;
    }

    public function execute()
    {
        // the grid shows the stored result, so the check runs before rendering
        $this->patchStatus->run();

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Swissup_Core::security_patches');
        $resultPage->addBreadcrumb(__('Security'), __('Security'));
        $resultPage->getConfig()->getTitle()->prepend(__('Security'));

        return $resultPage;
    }
}
