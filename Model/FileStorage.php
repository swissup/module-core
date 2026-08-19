<?php

namespace Swissup\Core\Model;

use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Simple file storage to keep the remote data when magento caches are flushed.
 */
class FileStorage
{
    /**
     * Beginning of the first line, holding the expiration timestamp
     */
    const EXPIRES_PREFIX = 'expires:';

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @var string
     */
    private $folder;

    /**
     * @param \Magento\Framework\Filesystem $filesystem
     * @param string $folder
     */
    public function __construct(
        \Magento\Framework\Filesystem $filesystem,
        $folder = 'swissup/core'
    ) {
        $this->filesystem = $filesystem;
        $this->folder = $folder;
    }

    /**
     * Retrieve the stored entry, unless it is expired
     *
     * @param  string $id
     * @return string|false
     */
    public function load($id)
    {
        $path = $this->getPath($id);

        try {
            $directory = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);

            if (!$directory->isReadable($path)) {
                return false;
            }

            $contents = $directory->openFile($path)->readAll();
        } catch (\Exception $e) {
            return false;
        }

        if (!$entry = $this->split($contents)) {
            return false;
        }

        list($expiresAt, $data) = $entry;

        if ($expiresAt && $expiresAt < time()) {
            return false;
        }

        return $data;
    }

    /**
     * Store the entry. Zero lifetime means it never expires.
     *
     * @param  string $data
     * @param  string $id
     * @param  int $lifetime
     * @return $this
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function save($data, $id, $lifetime = 0)
    {
        $expiresAt = $lifetime ? time() + $lifetime : 0;

        // Locked before the entry is opened - openFile() truncates, and a
        // truncation outside the lock lets two saves interleave. A file of its
        // own, since the caller likely holds the one lock() uses.
        $lock = $this->flock($id . '.write.lock', LOCK_EX);

        $file = $this->filesystem
            ->getDirectoryWrite(DirectoryList::VAR_DIR)
            ->openFile($this->getPath($id));

        try {
            $file->write(
                self::EXPIRES_PREFIX . $expiresAt . "\n"
                . strlen($data) . "\n"
                . $data
            );
        } finally {
            $file->close();
        }

        return $this;
    }

    /**
     * Take an exclusive lock over the entry, without waiting for it.
     *
     * The lock lives as long as the returned file does - keep it in a variable
     * for as long as the lock is needed.
     *
     * @param  string $id
     * @return \Magento\Framework\Filesystem\File\WriteInterface|bool
     */
    public function lock($id)
    {
        return $this->flock($id . '.lock', LOCK_EX | LOCK_NB);
    }

    /**
     * Lock a file standing next to the entry.
     *
     * A file that cannot be opened at all - one left behind by another user,
     * say - is reported as no lock rather than as one held by somebody else,
     * which would keep the caller away from the entry for good.
     *
     * @param  string $path
     * @param  int $mode
     * @return \Magento\Framework\Filesystem\File\WriteInterface|bool
     */
    private function flock($path, $mode)
    {
        try {
            $file = $this->filesystem
                ->getDirectoryWrite(DirectoryList::VAR_DIR)
                ->openFile($this->getPath($path));
        } catch (\Exception $e) {
            return true;
        }

        try {
            // Throws when the lock is held by somebody else
            $file->lock($mode);
        } catch (\Exception $e) {
            return false;
        }

        return $file;
    }

    /**
     * Delete the entry
     *
     * @param  string $id
     * @return bool
     */
    public function remove($id)
    {
        try {
            return $this->filesystem
                ->getDirectoryWrite(DirectoryList::VAR_DIR)
                ->delete($this->getPath($id));
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Split the contents into the expiration time and the data itself.
     *
     * File format:
     * expires:1234567890
     * 1234
     * <data>
     *
     * @param  string $contents
     * @return array|false
     */
    private function split($contents)
    {
        $prefixLength = strlen(self::EXPIRES_PREFIX);
        if (strncmp($contents, self::EXPIRES_PREFIX, $prefixLength) !== 0) {
            return false;
        }

        $expiresEnd = strpos($contents, "\n");
        if ($expiresEnd === false) {
            return false;
        }

        $expiresAt = substr($contents, $prefixLength, $expiresEnd - $prefixLength);
        if (!ctype_digit($expiresAt)) {
            return false;
        }

        $lengthEnd = strpos($contents, "\n", $expiresEnd + 1);
        if ($lengthEnd === false) {
            return false;
        }

        $length = substr($contents, $expiresEnd + 1, $lengthEnd - $expiresEnd - 1);
        if (!ctype_digit($length)) {
            return false;
        }

        $data = substr($contents, $lengthEnd + 1);
        if (strlen($data) !== (int) $length) {
            return false;
        }

        return [
            (int) $expiresAt,
            $data,
        ];
    }

    /**
     * Retrieve the entry path, relative to the var directory
     *
     * @param  string $id
     * @return string
     */
    private function getPath($id)
    {
        return $this->folder . '/' . $id;
    }
}
