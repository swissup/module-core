<?php

namespace Swissup\Core\Helper;

class PopupMessageManager
{
    const GROUP_ID = 'swissup_popup_messages';

    /**
     * @var \Magento\Framework\View\Layout
     */
    protected $layout;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    public function __construct(
        \Magento\Framework\View\Layout $layout,
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\Session $session
    ) {
        $this->layout = $layout;
        $this->messageManager = $messageManager;
        $this->session = $session;
    }

    public function addError($message, $popupText, $popupTitle)
    {
        $popupId = $this->addPopup($popupText, $popupTitle);
        $this->messageManager->addError($this->getMessageText($message, $popupId));
    }

    public function addWarning($message, $popupText, $popupTitle)
    {
        $popupId = $this->addPopup($popupText, $popupTitle);
        $this->messageManager->addWarning($this->getMessageText($message, $popupId));
    }

    public function addNotice($message, $popupText, $popupTitle)
    {
        $popupId = $this->addPopup($popupText, $popupTitle);
        $this->messageManager->addNotice($this->getMessageText($message, $popupId));
    }

    public function addSuccess($message, $popupText, $popupTitle)
    {
        $popupId = $this->addPopup($popupText, $popupTitle);
        $this->messageManager->addSuccess($this->getMessageText($message, $popupId));
    }

    protected function getMessageText($message, $popupId)
    {
        return $message . ' | ' . sprintf(
            "<a href='#' data-target='%s' class='swissup__action-info-message'>%s</a>",
            $popupId,
            __('Show Info')
        );
    }

    public function addPopup($text, $title = 'Debug Information')
    {
        if (!$popups = $this->session->getData(self::GROUP_ID)) {
            $popups = [];
        }
        $popups[] = [
            'title' => $title,
            'text'  => $text
        ];
        $this->session->setData(self::GROUP_ID, $popups);
        return count($popups) - 1;
    }

    public function getPopups($clear = false)
    {
        return $this->session->getData(self::GROUP_ID, $clear);
    }
}
