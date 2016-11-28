<?php

namespace SilverStripe\Assets;

/**
 * Provides a mechanism for determining the effective visibility of a set of assets (identified by
 * filename and hash), given their membership to objects of varying visibility.
 *
 * The effective visibility of assets is based on three rules:
 * - If an asset is attached to any public record, that asset is public.
 * - If an asset is not attached to any public record, but is attached to a protected record,
 *   that asset is protected.
 * - If an asset is attached to a record being deleted, but not any existing public or protected
 *   record, then that asset is marked for deletion.
 *
 * Variants are ignored for the purpose of determining visibility
 */
class AssetManipulationList
{

    const STATE_PUBLIC = 'public';

    const STATE_PROTECTED = 'protected';

    const STATE_DELETED = 'deleted';

    /**
     * List of public assets
     *
     * @var array
     */
    protected $public = array();

    /**
     * List of protected assets
     *
     * @var array
     */
    protected $protected = array();

    /**
     * List of deleted assets
     *
     * @var array
     */
    protected $deleted = array();

    /**
     * Get an identifying key for a given filename and hash
     *
     * @param array $asset Asset tuple
     * @return string
     */
    protected function getAssetKey($asset)
    {
        return $asset['Hash'] . '/' . $asset['Filename'];
    }

    /**
     * Add asset with the given state
     *
     * @param array $asset Asset tuple
     * @param string $state One of the STATE_* const vars
     * @return bool True if the asset was added to the set matching the given state
     */
    public function addAsset($asset, $state)
    {
        switch ($state) {
            case self::STATE_PUBLIC:
                return $this->addPublicAsset($asset);
            case self::STATE_PROTECTED:
                return $this->addProtectedAsset($asset);
            case self::STATE_DELETED:
                return $this->addDeletedAsset($asset);
            default:
                throw new \InvalidArgumentException("Invalid state {$state}");
        }
    }

    /**
     * Mark a file as public
     *
     * @param array $asset Asset tuple
     * @return bool True if the asset was added to the public set
     */
    public function addPublicAsset($asset)
    {
        // Remove from protected / deleted lists
        $key = $this->getAssetKey($asset);
        unset($this->protected[$key]);
        unset($this->deleted[$key]);
        // Skip if already public
        if (isset($this->public[$key])) {
            return false;
        }
        unset($asset['Variant']);
        $this->public[$key] = $asset;
        return true;
    }

    /**
     * Record an asset as protected
     *
     * @param array $asset Asset tuple
     * @return bool True if the asset was added to the protected set
     */
    public function addProtectedAsset($asset)
    {
        $key = $this->getAssetKey($asset);
        // Don't demote from public
        if (isset($this->public[$key])) {
            return false;
        }
        unset($this->deleted[$key]);
        // Skip if already protected
        if (isset($this->protected[$key])) {
            return false;
        }
        unset($asset['Variant']);
        $this->protected[$key] = $asset;
        return true;
    }

    /**
     * Record an asset as deleted
     *
     * @param array $asset Asset tuple
     * @return bool True if the asset was added to the deleted set
     */
    public function addDeletedAsset($asset)
    {
        $key = $this->getAssetKey($asset);
        // Only delete if this doesn't exist in any non-deleted state
        if (isset($this->public[$key]) || isset($this->protected[$key])) {
            return false;
        }
        // Skip if already deleted
        if (isset($this->deleted[$key])) {
            return false;
        }
        unset($asset['Variant']);
        $this->deleted[$key] = $asset;
        return true;
    }

    /**
     * Get all public assets
     *
     * @return array
     */
    public function getPublicAssets()
    {
        return $this->public;
    }

    /**
     * Get protected assets
     *
     * @return array
     */
    public function getProtectedAssets()
    {
        return $this->protected;
    }

    /**
     * Get deleted assets
     *
     * @return array
     */
    public function getDeletedAssets()
    {
        return $this->deleted;
    }
}
