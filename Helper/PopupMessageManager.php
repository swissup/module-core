<?php

namespace Swissup\Core\Helper;

class PopupMessageManager
{
    const GROUP_ID = 'swissup_popup_messages';

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @var \Magento\Backend\Model\Session
     */
    protected $session;

    public function __construct(
        \Magento\Framework\Message\ManagerInterface $messageManager,
        \Magento\Backend\Model\Session $session
    ) {
        $this->messageManager = $messageManager;
        $this->session = $session;
    }

    public function addError($message, $popupText = null, $popupTitle = null)
    {
        $popupId = $this->addPopup($popupText, $popupTitle);
        $this->messageManager->addError($this->getMessageText($message, $popupId));
    }

    public function addWarning($message, $popupText = null, $popupTitle = null)
    {
        $popupId = $this->addPopup($popupText, $popupTitle);
        $this->messageManager->addWarning($this->getMessageText($message, $popupId));
    }

    public function addNotice($message, $popupText = null, $popupTitle = null)
    {
        $popupId = $this->addPopup($popupText, $popupTitle);
        $this->messageManager->addNotice($this->getMessageText($message, $popupId));
    }

    public function addSuccess($message, $popupText = null, $popupTitle = null)
    {
        $popupId = $this->addPopup($popupText, $popupTitle);
        $this->messageManager->addSuccess($this->getMessageText($message, $popupId));
    }

    protected function getMessageText($message, $popupId = null)
    {
        if (null === $popupId) {
            return $message;
        }

        return $message . ' | ' . sprintf(
            "<a href='#' data-target='%s' class='swissup__action-info-message'>%s</a>",
            $popupId,
            __('Show Info')
        );
    }

    public function addPopup($text, $title = 'Debug Information')
    {
        if (null === $text) {
            return null;
        }

        if (!$popups = $this->session->getData(self::GROUP_ID)) {
            $popups = [];
        }
        if (!is_string($text)) {
            $text = print_r($text, true);
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
