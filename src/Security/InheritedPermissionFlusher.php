<?php

namespace SilverStripe\Security;

use Psr\Log\InvalidArgumentException;
use SilverStripe\Core\Flushable;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;

class InheritedPermissionFlusher extends DataExtension implements Flushable
{
    /**
     * @var InheritedPermissions[]
     */
    protected $services = [];

    /**
     * Flush all InheritedPermission services
     */
    public static function flush()
    {
        singleton(__CLASS__)->flushCache();
    }

    /**
     * @param DataObject $owner
     */
    public function setOwner($owner)
    {
        if (!$owner instanceof Member && !$owner instanceof Group) {
            throw new InvalidArgumentException(sprintf(
                '%s can only be applied to %s or %s',
                __CLASS__,
                Member::class,
                Group::class
            ));
        }

        parent::setOwner($owner);
    }

    /**
     * @param InheritedPermissions []
     */
    public function setServices($services)
    {
        foreach ($services as $service) {
            if (!$service instanceof InheritedPermissions) {
                throw new InvalidArgumentException(sprintf(
                    '%s.services must contain only %s instances',
                    __CLASS__,
                    InheritedPermissions::class
                ));
            }
        }

        $this->services = $services;
    }

    /**
     * @return InheritedPermissions[]
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Flushes all registered InheritedPermission services
     */
    public function flushCache()
    {
        $ids = $this->getMemberIDList();
        foreach ($this->services as $service) {
            $service->flushCache($ids);
        }
    }

    /**
     * Get a list of member IDs that need their permissions flushed
     *
     * @return array|null
     */
    protected function getMemberIDList()
    {
        if (!$this->owner) {
            return null;
        }

        if (!$this->owner->exists()) {
            return null;
        }

        if ($this->owner instanceof Group) {
            return $this->owner->Members()->exists()
                ? $this->owner->Members()->column('ID')
                : null;
        }

        return [$this->owner->ID];
    }
}