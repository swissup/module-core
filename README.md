# Core

Swissup_Core module adds menu and config entries to Magento backend. It also
utilize some common tasks used by other modules.

## Installation

```bash
composer require swissup/module-core
bin/magento setup:upgrade
```

## Swissup installer

Aavailable commands

Command                                 | Description
----------------------------------------|---------------------------------------
`bin/magento swissup:repo:enable`       | Add repo to composer.json file
`bin/magento swissup:repo:disable`      | Remove repo from composer.json file
**Authorization**                       |
`bin/magento swissup:auth:add {key}`    | Add auth key
`bin/magento swissup:auth:remove {key}` | Remove auth key
`bin/magento swissup:auth:show`         | Display auth keys info
**Packages**                            |
`bin/magento swissup:require {package}` | Download package
`bin/magento swissup:install {package}` | Run installer for downloaded package
`bin/magento swissup:remove {package}`  | Remove package
`bin/magento swissup:update`            | Update `swissup/*` packages

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
