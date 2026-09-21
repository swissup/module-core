# Swissup Core

This module ships Swissup Installer and adds Swissup menu and config entries
to Magento backend. It also utilize some common tasks used by other modules.

## Installation

```bash
composer require swissup/module-core
bin/magento setup:upgrade
```

## Swissup Installer

Aavailable commands

Command                                         | Description
------------------------------------------------|---------------------------------------
`bin/magento swissup:channel:enable`            | Add SwissupLabs repository to composer.json file
`bin/magento swissup:channel:disable`           | Remove SwissupLabs repository from composer.json file
**Authorization**                               |
`bin/magento swissup:auth:add {key}`            | Add SwissupLabs access key
`bin/magento swissup:auth:check`                | Display SwissupLabs access keys currently in use with count of available packages per key
`bin/magento swissup:auth:remove {key}`         | Remove SwissupLabs access key
`bin/magento swissup:auth:show`                 | Display SwissupLabs access username and password currently in use
**Packages**                                    |
`bin/magento swissup:package:require {package}` | Download SwissupLabs package(s) using composer and run setup:upgrade
`bin/magento swissup:package:install {package}` | Run installer for downloaded package
`bin/magento swissup:package:update`            | Update SwissupLabs package(s) using composer and run setup:upgrade
`bin/magento swissup:package:remove {package}`  | Remove SwissupLabs package(s) using composer and run setup:upgrade

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
