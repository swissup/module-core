<?php

namespace Swissup\Core\Controller\Adminhtml\Installer;

class Install extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Core::installer_install';

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $params = $this->getRequest()->getPostValue('general');
        if (empty($params['code']) || empty($params['installer']['new_stores'])) {
            return $resultRedirect->setPath('*/*/index');
        }

        $model = $this->_objectManager->create('Swissup\Core\Model\Module')
            ->load($params['code'])
            ->setNewStores($params['installer']['new_stores']);
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

        $this->messageManager->addSuccess(__('Module successfully installed'));
        return $resultRedirect->setPath('*/*/index');
    }
}
