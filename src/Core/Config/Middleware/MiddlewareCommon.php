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

    protected function enabled($options)
    {
        if ($options === true) {
            return false;
        }
        if (!$this->disableFlag) {
            return true;
        }
        if (is_array($options)) {
            if (!isset($options['disableFlag'])) {
                return true;
            }
            $options = $options['disableFlag'];
        }

        return ($options & $this->disableFlag) !== $this->disableFlag;
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
