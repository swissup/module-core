<?php

namespace Swissup\Core\Model\Module;

use Swissup\Core\Api\Data\ModuleUpgradeInterface;

abstract class Upgrade implements ModuleUpgradeInterface
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
    }

    /**
     * Retrieve the list of operation to run,
     * including module depends.
     *
     * Supported operations:
     *  configuration       @see runConfiguration
     *  cmsblock            @see runCmsblock
     *  cmspage             @see runCmspage
     *  easyslide           @see runEasyslide
     *  easybanner          @see runEasybanner
     *  prolabels           @see runProlabels
     *  productAttribute    @see runProductAttribute
     *
     * @return array
     */
    public function getOperations()
    {
        return [];
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
