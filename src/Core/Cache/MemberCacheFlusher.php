<?php declare(strict_types = 1);

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
