<?php

namespace Swissup\Core\Installer\Helper;

class Request
{
    public function getData(array $request, $key, $default = null)
    {
        return $request[$key] ?? $default;
    }
}
