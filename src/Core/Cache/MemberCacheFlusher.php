<?php

namespace SilverStripe\Core\Cache;

/**
 * Defines a service that can flush its cache for a list of members
 * @package SilverStripe\Core\Cache
 */
interface MemberCacheFlusher
{
    /**
     * @param null $memberIDs
     * @return mixed
     */
    public function flushMemberCache($memberIDs = null);
}