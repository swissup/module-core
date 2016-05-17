<?php

namespace Swissup\Core\Controller\Adminhtml\Installer;

use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;

class Form extends \Magento\Backend\App\Action
{
    const ADMIN_RESOURCE = 'Swissup_Core::installer_form';

    /**
     * @var PageFactory
     */
    protected $resultPageFactory;

    /**
     * @param Context $context
     * @param PageFactory $resultPageFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
    }

    /**
     * Install action
     *
     * @return \Magento\Backend\Model\View\Result\Page
     */
    public function execute()
    {
        $model = $this->_objectManager->create('Swissup\Core\Model\Module');
        $model->load($this->getRequest()->getParam('code'));

        $session = $this->_objectManager->get('Magento\Backend\Model\Session');
        $data = $session->getFormData(true);
        if (!empty($data) && !empty($data['general'])) {
            $model->addData($data['general']);
        }

        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('Swissup_Core::module_manager')
            ->addBreadcrumb('Swissup', 'Swissup')
            ->addBreadcrumb(__('Installer'), __('Installer'));
        $resultPage->getConfig()->getTitle()->prepend(__('Swissup Installer'));
        $resultPage->getConfig()->getTitle()->prepend($model->getName());

        return $resultPage;
    }
}
