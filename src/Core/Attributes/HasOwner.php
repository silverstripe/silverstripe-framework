<?php

namespace SilverStripe\Core\Attributes;

use Reflector;

trait HasOwner
{
    protected ?Reflector $owner = null;

    public function setOwner(Reflector $owner): void
    {
        $this->owner = $owner;
    }

    public function getOwner(): ?Reflector
    {
        return $this->owner;
    }
}
