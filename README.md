# Core

## Contents

1. [Installation](#installation)
2. [Swissup Installer Usage](#swissup-installer-usage)
3. [Swissup Upgrade Class](#swissup-upgrade-class)

### Installation

```bash
cd <magento_root>
composer config repositories.swissup/core vcs git@github.com:swissup/core.git
composer require swissup/core --prefer-source
bin/magento module:enable Swissup_Core
bin/magento setup:upgrade
```

### Swissup Installer Usage

Swissup installer is a class that collects [Swissup Upgrades](#swissup-upgrade-class) 
from all module dependencies and run them, if needed.

Lets see the example of how the Argento theme installer is working:

```php
$module = $this->_objectManager->create('Swissup\Core\Model\Module');
$module->load('Swissup_ArgentoDefault')
    ->setNewStores([0])
    ->up();
```

What does this code do?

 1. Create `Swissup\Core\Model\Module` object.
 2. Load module info for `Swissup_ArgentoDefault` module from `composer.json` 
    file.
 3. Set the store to use (All Stores).
 4. Run installer:
    1. Search for [Swissup\Upgrade](#swissup-upgrade-class) classes for all 
        depends of `Swissup_ArgentoDefault` module.
    2. Run `getOperations` and `up` command for each of the found upgrade class.
    3. Run `getOperations` and `up` command of `Swissup_ArgentoDefault` upgrade class.

### Swissup Upgrade Class

When module or theme needs to run some extra logic for specified store views,
it's very handy to use `Swissup\Upgrade` class, which allows to create and 
automatically backup various content types and configuration.

> Why not to use Magento DataUpgrade?
> - It does not allow to run upgrade multiple times (reinstall)
> - It does not have built-in methods to change store configuration
> - It does not support content backups

Swissup upgrades &mdash; are migrations, located at `<module_dir>/Upgrades` directory.
Upgrade class must implement `Swissup\Core\Api\Data\ModuleUpgradeInterface`.

Upgrade examples:

```
Swissup/ArgentoDefault/Upgrades/1.0.0_initial_installation.php
Swissup/ArgentoDefault/Upgrades/1.0.1_add_callout_blocks.php
Swissup/ArgentoDefault/Upgrades/1.1.0_create_featured_products.php
```

**Upgrade naming conventions**

```
1.0.0       _               initial_installation   .php
^ version   ^ Separator     ^ ClassName            ^ file extension
```

Class example:

```php
<?php

namespace Swissup\ArgentoDefault\Upgrades;

class InitialInstallation extends \Swissup\Core\Model\Module\Upgrade
{
    public function up()
    {   
        // This method is optional.
        // Additional logic may be placed here.
    }

    public function getOperations()
    {
        return [
            'configuration' => [
                'design' => [
                    'package/name' => 'argento',
                    'theme' => [
                        'template' => 'argento',
                        'skin'     => 'argento',
                        'layout'   => 'argento'
                    ]
                ],
                'navigationpro/top/enabled' => 1
            ],

            'cmsblock' => [
                'header_callout' => [
                    'title' => 'header_callout',
                    'identifier' => 'header_callout',
                    'status' => 1,
                    'content' => <<<HTML
HEADER_CALLOUT
HTML
                ]
            ]

            'productAttribute' => [
                [
                    'attribute_code' => 'featured',
                    'frontend_label' => array('Featured'),
                    'default_value'  => 0
                ]
            ]
        ];
    }
}

```

**Supported Operations**

Key | Method | Description
----|--------|------------
configuration   | runConfiguration  | Update Store Configuration
cmsblock        | runCmsblock       | Create/backup cms blocks
cmspage         | runCmspage        | Create/backup cms pages
productAttribute| runProductAttribute| Create attribute if it does not exists
