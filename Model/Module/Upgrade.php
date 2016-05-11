<?php

namespace Swissup\Core\Model\Module;

use Swissup\Core\Api\Data\ModuleUpgradeInterface;

abstract class Upgrade implements ModuleUpgradeInterface
{
    /**
     * @var \Swissup\Core\Model\Module\MessageLogger
     */
    protected $messageLogger;

    /**
     * @var array Store ids, where the module will be installed
     */
    protected $storeIds = array();

    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager
    ) {
        $this->objectManager = $objectManager;
    }

    /**
     * Additional operations could be done from this method
     */
    public function up()
    {
    }

    /**
     * Retrieve the list of commands to run
     *
     * @see Swissup\Core\Model\Module\UpgradeCommands for built-in commands
     *
     * @return array Key => Value pairs with command name and data
     */
    public function getCommands()
    {
        return [];
    }

    public function upgrade()
    {
        foreach ($this->getCommands() as $command => $data) {
            $className = 'Swissup\\Core\\Model\\Module\\UpgradeCommands\\' . $command;
            $this->objectManager->create($className)
                ->setStoreIds($this->getStoreIds())
                ->setMessageLogger($this->getMessageLogger())
                ->execute($data);
        }
        $this->up();
    }

    /**
     * Set store ids to run the upgrade on
     *
     * @return $this
     */
    public function setStoreIds(array $ids)
    {
        $this->storeIds = $ids;
        return $this;
    }

    /**
     * Retrieve store ids
     *
     * @return array
     */
    public function getStoreIds()
    {
        return $this->storeIds;
    }

    /**
     * @return $this
     */
    public function setMessageLogger($messageLogger)
    {
        $this->messageLogger = $messageLogger;
        return $this;
    }

    /**
     * @return \Swissup\Core\Model\Module\MessageLogger
     */
    public function getMessageLogger()
    {
        return $this->messageLogger;
    }
}
