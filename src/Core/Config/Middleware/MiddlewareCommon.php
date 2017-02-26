<?php

namespace SilverStripe\Core\Config\Middleware;

/**
 * Abstract flag-aware middleware
 */
trait MiddlewareCommon
{
    /**
     * Disable flag
     *
     * @var int
     */
    protected $disableFlag = 0;

    public function __construct($disableFlag = 0)
    {
        $this->disableFlag = $disableFlag;
    }

    /**
     * Check if this middlware is enabled
     *
     * @param int|true $excludeMiddleware
     * @return bool
     */
    protected function enabled($excludeMiddleware)
    {
        if ($excludeMiddleware === true) {
            return false;
        }
        if (!$this->disableFlag) {
            return true;
        }
        return ($excludeMiddleware & $this->disableFlag) !== $this->disableFlag;
    }

    public function serialize()
    {
        return json_encode([$this->disableFlag]);
    }

    public function unserialize($serialized)
    {
        list($this->disableFlag) = json_decode($serialized, true);
    }
}
