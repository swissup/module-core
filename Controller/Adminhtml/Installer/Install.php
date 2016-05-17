<?php

namespace Swissup\Core\Controller\Adminhtml\Installer;

class Install extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Core::installer_install';

    /**
     * @var \Swissup\Core\Helper\PopupMessageManager
     */
    protected $popupMessageManager;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\View\Result\PageFactory $resultPageFactory
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Swissup\Core\Helper\PopupMessageManager $popupMessageManager
    ) {
        parent::__construct($context);
        $this->popupMessageManager = $popupMessageManager;
    }

    public function execute()
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultRedirectFactory->create();

        $params = $this->getRequest()->getPostValue('general');
        if (empty($params['code']) || empty($params['new_stores'])) {
            return $resultRedirect->setPath('*/*/index');
        }

        $session = $this->_objectManager->get('Magento\Backend\Model\Session');
        $session->setFormData($this->getRequest()->getPostValue());

        $model = $this->_objectManager->create('Swissup\Core\Model\Module')
            ->load($params['code'])
            ->setNewStores($params['new_stores']);

        if (!empty($params['identity_key'])) {
            $model->setIdentityKey($params['identity_key']);
        }

        $result = $model->validateLicense();
        if (is_array($result) && isset($result['error'])) {
            $error = call_user_func_array('__', $result['error']);
            if (isset($result['response'])) {
                $this->popupMessageManager->addError(
                    $error,
                    $result['response'],
                    'License validation response'
                );
            } else {
                $this->messageManager->addError($error);
            }
            return $resultRedirect->setPath('*/*/form', ['code' => $params['code']]);
        }

        $model->up();

        // @todo flush cache

        $groupedErrors = $model->getInstaller()->getMessageLogger()->getErrors();
        if (count($groupedErrors)) {
            $popupMessages = [];
            foreach ($groupedErrors as $type => $errors) {
                foreach ($errors as $error) {
                    if (is_array($error)) {
                        $message = $error['message'];
                    } else {
                        $message = $error;
                    }
                    $popupMessages[$type][] = $message;
                }
            }
            $this->popupMessageManager->addWarning(
                __('Module installed, but some operations where failed'),
                $popupMessages,
                'Installation errors'
            );
            return $resultRedirect->setPath('*/*/form', ['code' => $params['code']]);
        }

        $session->setFormData(false);
        $this->messageManager->addSuccess(__('Module successfully installed'));
        return $resultRedirect->setPath('*/*/index');
    }
}
