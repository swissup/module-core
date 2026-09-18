<?php

namespace Swissup\Core\Installer\Command;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Swissup\Core\Installer\Request;
use Swissup\Core\Installer\LoggerAware;

class Unpack
{
    use LoggerAware;

    public function __construct(
        private \Magento\Framework\Archive $archiver,
        private \Magento\Framework\Filesystem\Io\File $ioFile
    ) {
    }

    public function execute(Request $request)
    {
        $this->logger->info('Unpack');
        $params = $request->getParams();
        $destanation = $params['destination'];
        $this->ioFile->checkAndCreateFolder($destanation);
        $archive = $params['archive'];
        $this->archiver->unpack($archive, $destanation);
    }
}
