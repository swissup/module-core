<?php

namespace Swissup\Core\Model\Installer;

use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Module\FullModuleList;
use Magento\Framework\Module\ModuleListInterface;

/**
 * Modules that are present in the codebase, but turned off in app/etc/config.php
 */
class DisabledModules
{
    private FullModuleList $fullModuleList;
    private ModuleListInterface $moduleList;
    private ComponentRegistrarInterface $componentRegistrar;

    public function __construct(
        FullModuleList $fullModuleList,
        ModuleListInterface $moduleList,
        ComponentRegistrarInterface $componentRegistrar
    ) {
        $this->fullModuleList = $fullModuleList;
        $this->moduleList = $moduleList;
        $this->componentRegistrar = $componentRegistrar;
    }

    /**
     * @return string[]
     */
    public function getNames()
    {
        return array_values(
            array_diff($this->fullModuleList->getNames(), $this->moduleList->getNames())
        );
    }

    /**
     * Declarative schema drops the tables of such modules during setup:upgrade:
     * db_schema.xml is read for the enabled modules only, while
     * db_schema_whitelist.json - the file that allows the drop - is read for all of them.
     *
     * @return string[]
     */
    public function getNamesWithDbSchema()
    {
        $result = [];

        foreach ($this->getNames() as $name) {
            $path = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, $name);

            if ($path && is_file($path . '/etc/db_schema_whitelist.json')) {
                $result[] = $name;
            }
        }

        return $result;
    }
}
