<?php

namespace Swissup\Core\Api\Media;

interface FileInfoInterface
{
    /**
     * Get image data
     *
     * @param  string $fileName
     * @return array
     */
    public function getImageData($fileName);
}
