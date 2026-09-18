<?php

namespace Swissup\Core\Installer\Command;

use Swissup\Core\Installer\Request;

class Product extends ProductCollection
{
    public function execute(Request $request)
    {
        // $this->logger->warning('Product Command is deprecated. Please use ProductCollection instead');
        parent::execute($request);
    }
}
