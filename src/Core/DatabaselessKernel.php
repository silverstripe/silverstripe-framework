<?php

namespace SilverStripe\Core;

/**
 * Boot a kernel without requiring a database connection.
 * This is a workaround for the lack of composition in the boot stages
 * of CoreKernel, as well as for the framework's misguided assumptions
 * around the availability of a database for every execution path.
 *
 * @internal
 */
class DatabaselessKernel extends CoreKernel
{
    protected $queryErrorMessage = 'Booted with DatabaseLessKernel, cannot execute query: %s';

    /**
     * Allows disabling of the configured error handling.
     * This can be useful to ensure the execution context (e.g. composer)
     * can consistently use its own error handling.
     *
     * @var boolean
     */
    protected $bootErrorHandling = true;

    public function setBootErrorHandling(bool $bool)
    {
        $this->bootErrorHandling = $bool;
        return $this;
    }

    public function boot($flush = false)
    {
        $this->flush = $flush;

        $this->bootPHP();
        $this->bootManifests($flush);

        if ($this->bootErrorHandling) {
            $this->bootErrorHandling();
        }

        $this->bootConfigs();

        $this->booted = true;
    }
}
