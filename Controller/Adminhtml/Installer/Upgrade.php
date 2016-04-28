<?php

namespace Swissup\Core\Controller\Adminhtml\Installer;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Upgrade extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Core::installer_upgrade';

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $code = $this->getRequest()->getParam('code');
        if (!$code) {
            return $resultRedirect->setPath('*/*/index');
        }

        $model = $this->_objectManager->create('Swissup\Core\Model\Module')
            ->load($code);
        $model->up();

        $groupedErrors = $model->getInstaller()->getMessageLogger()->getErrors();
        if (count($groupedErrors)) {
            foreach ($groupedErrors as $type => $errors) {
                foreach ($errors as $error) {
                    if (is_array($error)) {
                        $message = $error['message'];
                    } else {
                        $message = $error;
                    }
                    $this->messageManager->addError($message);
                }
            }
            return $resultRedirect->setPath('*/*/index');
        }

        $this->messageManager->addSuccess(__('Module upgrades successfully applied'));
        return $resultRedirect->setPath('*/*/index');
    }
}
