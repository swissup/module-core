<?php

namespace Swissup\Core\Installer\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Swissup\Core\Installer\Request;
use Psr\Log\LoggerAwareTrait;

class Unpack
{
    use LoggerAwareTrait;

    public function __construct(
        private \Magento\Framework\Archive $archiver,
        private \Magento\Framework\Filesystem\Io\File $ioFile
    ) {
    }

    public function execute(Request $request)
    {
        $this->logger->info('Unpack');
        $params = $request->getParams();
        $destination = $params['destination'];
        $this->ioFile->checkAndCreateFolder($destination);
        $archive = $params['archive'];
        $this->archiver->unpack($archive, $destination);
    }
}
