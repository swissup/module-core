<?php

namespace Swissup\Core\Helper;

class Component
{
    public function convertPackageNameToModuleName($packageName)
    {
        list($vendor, $name) = explode('/', $packageName, 2);
        $name = str_replace('-', ' ', $name);
        $name = str_replace(' ', '', ucwords($name));
        return ucfirst($vendor) . '_' . $name;
    }
}
