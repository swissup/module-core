<?php

namespace Swissup\Core\Helper;

class Component
{
    /**
     * Convert composer package name into module name or full theme path.
     *
     * swissup/module-core => Swissup_Core
     * swissup/theme-frontend-breeze-evolution => frontend/Swissup/breeze-evolution
     *
     * @param  string $packageName
     * @return string
     */
    public function convertPackageNameToModuleName($packageName)
    {
        list($vendor, $name) = explode('/', $packageName, 2);

        if (strpos($name, 'theme-') === 0) {
            $parts = explode('-', substr($name, strlen('theme-')), 2);
            if (count($parts) === 2) {
                return $parts[0] . '/' . ucfirst($vendor) . '/' . $parts[1];
            }
        }

        $name = str_replace('module-', '', $name);
        $name = str_replace('-', ' ', $name);
        $name = str_replace(' ', '', ucwords($name));
        return ucfirst($vendor) . '_' . $name;
    }
}
