# Core

Swissup_Core module adds menu and config entries to Magento backend. It also
utilize some common tasks used by other modules.

## Installation

```bash
composer require swissup/module-core
bin/magento setup:upgrade
```

## Popup Message Manager

Popup message manager allows to show regular Magento messages with additional
information in popup window.

![Popup Message Example](/resources/docs/images/popup_message_example.gif)

**Usage example**

Inject `\Swissup\Helper\PopupMessageManager` component into your controller
action and use it instead of built-in `\Magento\Framework\Message\Manager`:

```php
$this->popupMessageManager->addError(
    __('Decoding failed: Syntax error'),
    $popupText,
    $popupTitle
);
```
