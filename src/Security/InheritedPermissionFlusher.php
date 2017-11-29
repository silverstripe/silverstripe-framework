<?php

namespace SilverStripe\Security;

use Psr\Log\InvalidArgumentException;
use SilverStripe\ORM\DataExtension;

class InheritedPermissionFlusher extends DataExtension
{
    protected $services = [];

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

    public function flushInheritedPermissions()
    {
        foreach ($this->services as $service) {
            $service->flushCache($persistant);
        }
    }
}