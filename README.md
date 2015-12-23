# Core

### Installation

```bash
cd <magento_root>
composer config repositories.swissup/core vcs git@github.com:swissup/core.git
composer require swissup/core
bin/magento module:enable Swissup_Core
bin/magento setup:upgrade
```
