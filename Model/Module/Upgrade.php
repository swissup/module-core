<?php

namespace Swissup\Core\Model\Module;

abstract class Upgrade
{
    protected $installer;

    /**
     * @var array Store ids, where the module will be installed
     */
    protected $storeIds = array();

    /**
     * Additional operations could be done from this method
     */
    public function up()
    {
        //
    }

    public function upgrade()
    {
        $this->up();
    }

    public function setInstaller($installer)
    {
        $this->installer = $installer;
    }

    /**
     * Set store ids to run the upgrade on
     *
     * @return
     */
    public function setStoreIds(array $ids)
    {
        // @todo test with isSingleStoreMode
        $this->_storeIds = $ids;
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
     * @return \Swissup\Core\Model\Module\MessageLogger
     */
    protected function getMessageLogger()
    {
        return $this->installer->getMessageLogger();
    }
}
