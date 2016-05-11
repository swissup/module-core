<?php

namespace Swissup\Core\Api\Data;

interface ModuleUpgradeInterface
{
    public function getCommands();

    public function upgrade();

    public function up();

    public function setStoreIds(array $ids);

    public function getStoreIds();

    public function setMessageLogger($logger);

    public function getMessageLogger();
}
