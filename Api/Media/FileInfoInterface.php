<?php

namespace Swissup\Core\Api\Media;

interface FileInfoInterface
{
    /**
     * Retrieve MIME type of requested file
     *
     * @param string $fileName
     * @return string
     */
    public function getMimeType($fileName);

    /**
     * Get file statistics data
     *
     * @param string $fileName
     * @return array
     */
    public function getStat($fileName);

    /**
     * Check if the file exists
     *
     * @param string $fileName
     * @return bool
     */
    public function isExist($fileName);

    /**
     * get base image dir
     *
     * @return string
     */
    public function getBaseDir();

    /**
     * Get base url fro media directory including path
     *
     * @return string
     */
    public function getBaseUrl();

    /**
     * Get image data
     *
     * @param  string $fileName
     * @return array
     */
    public function getImageData($fileName);
}
