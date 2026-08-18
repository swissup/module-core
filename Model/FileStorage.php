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

        $file = $this->filesystem
            ->getDirectoryWrite(DirectoryList::VAR_DIR)
            ->openFile($this->getPath($id));

        // The lock throws when it cannot be acquired, hence no return value to
        // check. It only keeps the writers apart: openFile() has truncated the
        // file by now, so a reader can still catch the entry half-written -
        // that is what the length line in the contents is for.
        $file->lock();

        try {
            $file->write(
                self::EXPIRES_PREFIX . $expiresAt . "\n"
                . strlen($data) . "\n"
                . $data
            );
        } finally {
            $file->unlock();
            $file->close();
        }

        return $this;
    }

    /**
     * Take an exclusive lock over the entry, without waiting for it.
     *
     * A separate file is locked, and not the entry itself: openFile() truncates
     * whatever it opens, so touching the entry before knowing that the lock is
     * ours would destroy the very data the other process is writing.
     *
     * The lock lives as long as the returned file does - keep it in a variable
     * for as long as the lock is needed, and it is released on every way out of
     * the scope, including the ones taken by an exception.
     *
     * @param  string $id
     * @return \Magento\Framework\Filesystem\File\WriteInterface|false
     */
    public function lock($id)
    {
        try {
            $file = $this->filesystem
                ->getDirectoryWrite(DirectoryList::VAR_DIR)
                ->openFile($this->getPath($id) . '.lock');

            // Throws when the lock is held by somebody else
            $file->lock(LOCK_EX | LOCK_NB);
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
            (int) substr($contents, $prefixLength, $expiresEnd - $prefixLength),
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
