<?php

namespace Swissup\Core\Model;

use Swissup\Core\Api\Data\ModuleInterface;

class Module extends \Magento\Framework\Model\AbstractModel implements ModuleInterface
{
    /**
     * @var \Swissup\Core\Model\Module\LicenseValidatorFactory
     */
    protected $licenseValidatorFactory;

    /**
     * @var \Magento\Framework\Module\PackageInfo
     */
    protected $packageInfo;

    /**
     * @var \Swissup\Core\Model\ResourceModel\Module\RemoteCollection
     */
    protected $remoteCollection;

    /**
     * @param \Magento\Framework\Model\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Swissup\Core\Model\Module\LicenseValidator $licenseValidator
     * @param \Magento\Framework\Model\ResourceModel\AbstractResource $resource
     * @param \Magento\Framework\Data\Collection\AbstractDb $resourceCollection
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\Model\Context $context,
        \Magento\Framework\Registry $registry,
        \Swissup\Core\Model\Module\LicenseValidatorFactory $licenseValidatorFactory,
        \Magento\Framework\Module\PackageInfo $packageInfo,
        \Swissup\Core\Model\ResourceModel\Module\RemoteCollection $remoteCollection,
        \Magento\Framework\Model\ResourceModel\AbstractResource $resource = null,
        \Magento\Framework\Data\Collection\AbstractDb $resourceCollection = null,
        array $data = []
    ) {
        $this->licenseValidatorFactory = $licenseValidatorFactory;
        $this->packageInfo = $packageInfo;
        $this->remoteCollection = $remoteCollection;
        parent::__construct($context, $registry, $resource, $resourceCollection, $data);
    }

    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init('Swissup\Core\Model\ResourceModel\Module');
    }

    public function load($modelId, $field = null)
    {
        parent::load($modelId, $field);

        $this->setId($modelId);
        $this->setDepends($this->packageInfo->getRequire($this->getCode()));
        $this->setVersion($this->packageInfo->getVersion($this->getCode()));
        $this->setPackageName($this->packageInfo->getPackageName($this->getCode()));

        return $this;
    }

    public function up()
    {
        // $this->getUpgradeProxy()->up();
        $this->save();
    }

    public function validateLicense()
    {
        return $this->licenseValidatorFactory->create(['module' => $this])->validate();
    }

    public function getRemote()
    {
        return $this->remoteCollection->getItemById($this->getId());
    }

    /**
     * Prepare store ids
     *
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function beforeSave()
    {
        $oldStores = $this->getOldStores();
        $newStores = $this->getNewStoreIds();
        if (is_array($newStores)) {
            $stores = array_merge($oldStores, $newStores);
            $this->setStoreIds(implode(',', array_unique($stores)));
        }
        return parent::beforeSave();
    }

    /**
     * Retieve store ids, where the module is already installed
     *
     * @return array
     */
    public function getOldStores()
    {
        $ids = $this->getStoreIds();
        if (null === $ids || '' === $ids) {
            return array();
        }
        if (!is_array($ids)) {
            $ids = explode(',', $ids);
        }
        return $ids;
    }

    /**
     * Get the stores, where the module should be installed or reinstalled
     *
     * @return array
     */
    public function getNewStores()
    {
        return $this->getNewStoreIds();
    }

    /**
     * Set the stores, where the module should be installed or reinstalled
     *
     * @param array $ids
     * @return ModuleInterface
     */
    public function setNewStores(array $ids)
    {
        $this->setData('new_store_ids', array_unique($ids));
        return $this;
    }

    /**
     * Retrieve module code
     *
     * @return int
     */
    public function getId()
    {
        return $this->getData(self::CODE);
    }

    /**
     * Retrieve module code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->getData(self::CODE);
    }

    /**
     * Retrieve module data version
     *
     * @return string
     */
    public function getDataVersion()
    {
        return $this->getData(self::DATA_VERSION);
    }

    /**
     * Retrieve module identity key
     *
     * @return string
     */
    public function getIdentityKey()
    {
        return $this->getData(self::IDENTITY_KEY);
    }

    /**
     * Retrieve module store ids
     *
     * @return string
     */
    public function getStoreIds()
    {
        return $this->getData(self::STORE_IDS);
    }

    /**
     * Set code
     *
     * @param string $id
     * @return ModuleInterface
     */
    public function setId($id)
    {
        return $this->setData(self::CODE, $id);
    }

    /**
     * Set code
     *
     * @param string $code
     * @return ModuleInterface
     */
    public function setCode($code)
    {
        return $this->setData(self::CODE, $code);
    }

    /**
     * Set data version
     *
     * @param string $dataVersion
     * @return ModuleInterface
     */
    public function setDataVersion($dataVersion)
    {
        return $this->setData(self::DATA_VERSION, $dataVersion);
    }

    /**
     * Set identity key
     *
     * @param string $identityKey
     * @return ModuleInterface
     */
    public function setIdentityKey($identityKey)
    {
        return $this->setData(self::IDENTITY_KEY, $identityKey);
    }

    /**
     * Set store ids
     *
     * @param string $storeIds Comma separated store ids
     * @return ModuleInterface
     */
    public function setStoreIds($storeIds)
    {
        return $this->setData(self::STORE_IDS, $storeIds);
    }
}
