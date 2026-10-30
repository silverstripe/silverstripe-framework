<?php

namespace SilverStripe\Core\Attributes;

use Reflector;

interface OwnerAware
{
    public function getOwner(): ?Reflector;
    public function setOwner(Reflector $owner): void;
}
