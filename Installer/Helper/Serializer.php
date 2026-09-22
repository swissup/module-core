<?php

namespace Swissup\Core\Installer\Helper;

class Serializer
{
    public function __construct(
        private \Magento\Framework\Serialize\Serializer\Json $jsonSerializer
    ) {
    }

    /**
     * @param array $request
     * @param array $value
     * @return string
     */
    public function serialize(array $request, $value)
    {
        return $this->jsonSerializer->serialize($value);
    }
}
