<?php

namespace SilverStripe\Core;

use Exception;
use SilverStripe\Dev\Deprecation;

/**
 * Boot a kernel without requiring a database connection.
 * This is a workaround for the lack of composition in the boot stages
 * of CoreKernel, as well as for the framework's misguided assumptions
 * around the availability of a database for every execution path.
 *
 * @internal
 * @deprecated 5.4.0 Use SilverStripe\Core\CoreKernel::setBootDatabase() instead
 */
class DatabaselessKernel extends BaseKernel
{
    /**
     * Indicates whether the Kernel has been flushed on boot
     * Null before boot
     */
    private ?bool $flush = null;

    /**
     * Allows disabling of the configured error handling.
     * This can be useful to ensure the execution context (e.g. composer)
     * can consistently use its own error handling.
     *
     * @var boolean
     */
    protected $bootErrorHandling = true;

    public function __construct($basePath)
    {
        parent::__construct($basePath);
        Deprecation::notice(
            '5.4.0',
            'Use ' . CoreKernel::class . '::setBootDatabase() instead in a future major release',
            Deprecation::SCOPE_CLASS
        );
    }

    public function setBootErrorHandling(bool $bool)
    {
        $this->bootErrorHandling = $bool;
        return $this;
    }

    /**
     * @param false $flush
     * @throws Exception
     */
    public function boot($flush = false)
    {
        $this->flush = $flush;

        $this->bootPHP();
        $this->bootManifests($flush);
        $this->bootErrorHandling();
        $this->bootConfigs();

        $this->setBooted(true);
    }

    public function isFlushed(): ?bool
    {
        return $this->flush;
    }
}
