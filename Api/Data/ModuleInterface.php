<?php

namespace Swissup\Core\Api\Data;

interface ModuleInterface
{
    /**#@+
     * Constants for keys of data array. Identical to the name of the getter in snake case
     */
    const CODE          = 'code';
    const DATA_VERSION  = 'data_version';
    const IDENTITY_KEY  = 'identity_key';
    const STORE_IDS     = 'store_ids';
    /**#@-*/

    /**
     * Run module upgrades
     *
     * @return void
     */
    public function up();

    /**
     * Run module license validation
     *
     * @return boolean|array
     */
    public function validateLicense();

    /**
     * Get module data from remote server:
     * - latest version
     * - changelog
     * - url to download page
     * - url to activation page
     *
     * @return array
     */
    public function getRemote();

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId();

    /**
     * Get code
     *
     * @return string
     */
    public function getCode();

    /**
     * Get dataVersion
     *
     * @return string|null
     */
    public function getDataVersion();

    /**
     * Get identityKey
     *
     * @return string|null
     */
    public function getIdentityKey();

    /**
     * Get store ids
     *
     * @return string|null
     */
    public function getStoreIds();

    /**
     * Set ID
     *
     * @param string $id
     * @return BlockInterface
     */
    public function setId($id);

    /**
     * Set code
     *
     * @param string $code
     * @return BlockInterface
     */
    public function setCode($code);

    /**
     * Set dataVersion
     *
     * @param string $dataVersion
     * @return BlockInterface
     */
    public function setDataVersion($dataVersion);

    /**
     * Set identityKey
     *
     * @param string $identityKey
     * @return BlockInterface
     */
    public function setIdentityKey($identityKey);

    /**
     * Set store ids
     *
     * @param string $storeIds
     * @return BlockInterface
     */
    public function setStoreIds($storeIds);
}
