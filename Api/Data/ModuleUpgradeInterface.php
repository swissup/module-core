<?php

namespace Swissup\Core\Api\Data;

interface ModuleUpgradeInterface
{
    public function getOperations();

    public function upgrade();

    public function up();

    public function setInstaller($installer);

    public function setStoreIds(array $ids);

    public function getStoreIds();

    public function getMessageLogger();
}
