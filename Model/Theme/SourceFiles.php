<?php

namespace Swissup\Core\Model\Theme;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\View\Design\ThemeInterface;

class SourceFiles
{
    const THEME_FILES = ['registration.php', 'theme.xml'];

    const READABLE_FILE_MODE = 0644;

    private ComponentRegistrarInterface $registrar;

    private File $filesystemDriver;

    public function __construct(
        ComponentRegistrarInterface $registrar,
        File $filesystemDriver
    ) {
        $this->registrar = $registrar;
        $this->filesystemDriver = $filesystemDriver;
    }

    public function getUnreadable(ThemeInterface $theme): array
    {
        $directory = $this->getDirectory($theme);

        $unreadable = [];
        foreach (self::THEME_FILES as $filename) {
            if (!$directory || !$this->isReadable($directory . '/' . $filename)) {
                $unreadable[] = $filename;
            }
        }

        return $unreadable;
    }

    public function makeReadable(ThemeInterface $theme): bool
    {
        $directory = $this->getDirectory($theme);
        if (!$directory) {
            return false;
        }

        foreach (self::THEME_FILES as $filename) {
            $path = $directory . '/' . $filename;
            if ($this->isReadable($path)) {
                continue;
            }

            if (!$this->changePermissions($path) || !$this->isReadable($path)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Themes created in admin have no theme_path. Theme with unreadable
     * directory is not registered either - it is silently skipped by the glob
     * in app/etc/NonComposerComponentRegistration.php, so an unregistered theme
     * never means a removed one.
     */
    private function getDirectory(ThemeInterface $theme): ?string
    {
        $fullPath = $theme->getFullPath();

        return $fullPath
            ? $this->registrar->getPath(ComponentRegistrar::THEME, $fullPath)
            : null;
    }

    private function changePermissions(string $path): bool
    {
        try {
            return $this->filesystemDriver->changePermissions($path, self::READABLE_FILE_MODE);
        } catch (FileSystemException $e) {
            return false;
        }
    }

    private function isReadable(string $path): bool
    {
        try {
            return $this->filesystemDriver->isReadable($path);
        } catch (FileSystemException $e) {
            return false;
        }
    }
}
