<?php

namespace Swissup\Core\Model\Media;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Mime;
use Magento\Framework\Filesystem;
use Magento\Framework\Filesystem\Directory\WriteInterface;
use Magento\Framework\UrlInterface;

class FileInfo implements \Swissup\Core\Api\Media\FileInfoInterface
{
    /**
     * Path in /pub/media directory
     *
     * @var string
     */
    protected $mediaPath;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Mime
     */
    private $mime;

    /**
     * @var WriteInterface
     */
    private $mediaDirectory;

    /**
     * @param Filesystem $filesystem
     * @param Mime $mime
     */
    public function __construct(
        UrlInterface $urlBuilder,
        Filesystem $filesystem,
        Mime $mime,
        $mediaPath = ''
    ) {
        $this->urlBuilder = $urlBuilder;
        $this->filesystem = $filesystem;
        $this->mime = $mime;
        $this->mediaPath = $mediaPath;
    }

    /**
     * Get WriteInterface instance
     *
     * @return WriteInterface
     */
    private function getMediaDirectory()
    {
        if ($this->mediaDirectory === null) {
            $this->mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
        }
        return $this->mediaDirectory;
    }

    /**
     * Retrieve MIME type of requested file
     *
     * @param string $fileName
     * @return string
     */
    public function getMimeType($fileName)
    {
        $filePath = $this->mediaPath . '/' . ltrim($fileName, '/');
        $absoluteFilePath = $this->getMediaDirectory()->getAbsolutePath($filePath);

        $result = $this->mime->getMimeType($absoluteFilePath);
        return $result;
    }

    /**
     * Get file statistics data
     *
     * @param string $fileName
     * @return array
     */
    public function getStat($fileName)
    {
        $filePath = $this->mediaPath . '/' . ltrim($fileName, '/');

        $result = $this->getMediaDirectory()->stat($filePath);
        return $result;
    }

    /**
     * Check if the file exists
     *
     * @param string $fileName
     * @return bool
     */
    public function isExist($fileName)
    {
        $filePath = $this->mediaPath . '/' . ltrim($fileName, '/');
        $result = $this->getMediaDirectory()->isExist($filePath);
        return $result;
    }

    /**
     * get base image dir
     *
     * @return string
     */
    public function getBaseDir()
    {
        return $this->getMediaDirectory()->getAbsolutePath($this->mediaPath);
    }

    /**
     * Get base url fro media directory including path
     *
     * @return string
     */
    public function getBaseUrl()
    {
        return $this->urlBuilder->getBaseUrl([
            '_type' => UrlInterface::URL_TYPE_MEDIA
        ]) . $this->mediaPath;
    }

    /**
     * Get image data
     *
     * @param  string $fileName
     * @return array
     */
    public function getImageData($fileName)
    {
        if (!$this->isExist($fileName)) {
            return [];
        }

        $stat = $this->getStat($fileName);
        return [
            'name' => $fileName,
            'url'  => $this->getBaseUrl() . '/' . ltrim($fileName, '/'),
            'size' => isset($stat['size']) ? $stat['size'] : 0,
            'type' => $this->getMimeType($fileName)
        ];
    }
}
