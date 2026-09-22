<?php

namespace Swissup\Core\Installer\Helper;

class Arr
{
    public function join(array $request, $glue, array $pieces = [])
    {
        return implode($glue, $pieces);
    }
}
