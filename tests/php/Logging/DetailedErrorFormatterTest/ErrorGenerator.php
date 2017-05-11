<?php

namespace SilverStripe\Logging\Tests\DetailedErrorFormatterTest;

use Exception;
use SilverStripe\Dev\TestOnly;

/**
 * WARNING: This file is sensitive to whitespace changes
 */
class ErrorGenerator implements TestOnly
{
    /**
     * Generate an exception with a trace depeth of at least 4
     *
     * @param int $depth
     * @return Exception
     * @throws Exception
     */
    public function mockException($depth = 0)
    {
        switch ($depth) {
            case 0:
                try {
                    $this->mockException(1);
                } catch (\Exception $ex) {
                    return $ex;
                }
                return null;
                break;
            case 4:
                throw new Exception('Error');
            default:
                return $this->mockException($depth + 1);
        }
    }
}
