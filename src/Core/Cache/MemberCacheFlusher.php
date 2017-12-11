<?php

namespace SilverStripe\Core\Cache;

/**
 * Defines a service that can flush its cache for a list of members
 */
interface MemberCacheFlusher
{
    /**
     * @param array $memberIDs
     */
    public function flushMemberCache($memberIDs = null);
}
