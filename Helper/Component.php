<?php

namespace Swissup\Core\Helper;

class Component
{
    /**
     * This code is taken from \Magento\Framework\Module\PackageInfo
     *
     * @param  string $packageName [description]
     * @return string
     */
    public function convertPackageNameToModuleName($packageName)
    {
        list($vendor, $name) = explode('/', $packageName, 2);
        $name = str_replace('module-', '', $name);
        $name = str_replace('-', ' ', $name);
        $name = str_replace(' ', '', ucwords($name));
        return ucfirst($vendor) . '_' . $name;
    }
}
