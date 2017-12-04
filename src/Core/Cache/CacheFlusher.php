<?php

namespace SilverStripe\Core\Cache;

/**
 * Defines a service that can flush its cache for a list of members
 * @package SilverStripe\Core\Cache
 */
interface CacheFlusher
{
    /**
     * @param null $ids
     * @return mixed
     */
    public function flushCache($ids = null);
}