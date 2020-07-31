<?php

namespace SilverStripe\Core\Startup;

/**
 * Implements the composite over flush discoverers
 *
 * @see https://en.wikipedia.org/wiki/Composite_pattern composite design pattern for more information
 */
class CompositeFlushDiscoverer extends \ArrayIterator implements FlushDiscoverer
{
    public function shouldFlush()
    {
        foreach ($this as $discoverer) {
            $flush = $discoverer->shouldFlush();

            if ($flush !== null) {
                return $flush;
            }
        }
    }
}
