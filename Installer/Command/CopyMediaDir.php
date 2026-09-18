<?php

namespace Swissup\Core\Installer\Command;

use Magento\Framework\App\Filesystem\DirectoryList;
use Swissup\Core\Installer\Request;
use Psr\Log\LoggerAwareTrait;

class CopyMediaDir
{
    use LoggerAwareTrait;

    public function __construct(private \Magento\Framework\Filesystem $filesystem)
    {
    }

    public function execute(Request $request)
    {
        $this->logger->info('Resources: Copy media files');

        $media = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        $driver = $media->getDriver();
        $mediaPath = $media->getAbsolutePath();

        foreach ($request->getParams() as $dir) {
            $paths = $driver->readDirectoryRecursively($dir);
            $paths = array_reverse($paths); // put deepest in the end

            foreach ($paths as $path) {
                $relative = str_replace($dir . '/', '', $path);
                $destination = $mediaPath . $relative;

                try {
                    if ($driver->isExists($destination)) {
                        continue;
                    }

                    if ($driver->isDirectory($path)) {
                        $driver->createDirectory($destination);
                    } else {
                        $driver->copy($path, $destination);
                    }
                } catch (\Exception $e) {
                    $this->logger->warning($e->getMessage());
                }
            }
        }
    }
}
