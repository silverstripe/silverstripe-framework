<?php

namespace SilverStripe\Security;

use Psr\Log\InvalidArgumentException;
use SilverStripe\Core\Cache\MemberCacheFlusher;
use SilverStripe\Core\Flushable;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;

class InheritedPermissionFlusher extends DataExtension implements Flushable
{
    /**
     * @var MemberCacheFlusher[]
     */
    protected $services = [];

    /**
     * Flush all MemberCacheFlusher services
     */
    public static function flush()
    {
        singleton(self::class)->flushCache();
    }

    /**
     * @param DataObject $owner
     */
    public function setOwner($owner)
    {
        if (!$owner instanceof Member && !$owner instanceof Group) {
            throw new InvalidArgumentException(sprintf(
                '%s can only be applied to %s or %s',
                self::class,
                Member::class,
                Group::class
            ));
        }

        parent::setOwner($owner);
    }

    /**
     * @param MemberCacheFlusher[] $services
     * @throws InvalidArgumentException
     * @return $this
     */
    public function setServices($services)
    {
        foreach ($services as $service) {
            if (!$service instanceof MemberCacheFlusher) {
                throw new InvalidArgumentException(sprintf(
                    '%s.services must contain only %s instances. %s provided.',
                    self::class,
                    MemberCacheFlusher::class,
                    get_class($service)
                ));
            }
        }

        $this->services = $services;

        return $this;
    }

    /**
     * @return MemberCacheFlusher[]
     */
    public function getServices()
    {
        return $this->services;
    }

    /**
     * Flushes all registered MemberCacheFlusher services
     */
    public function flushCache()
    {
        $ids = $this->getMemberIDList();
        foreach ($this->getServices() as $service) {
            $service->flushMemberCache($ids);
        }
    }

    /**
     * Get a list of member IDs that need their permissions flushed
     *
     * @return array|null
     */
    protected function getMemberIDList()
    {
        if (!$this->owner || !$this->owner->exists()) {
            return null;
        }

        if ($this->owner instanceof Group) {
            return $this->owner->Members()->column('ID');
        }

        return [$this->owner->ID];
    }
}
