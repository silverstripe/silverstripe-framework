<?php

namespace SilverStripe\Control\Middleware\ConfirmationMiddleware;

/**
 * Path aware trait for rules and bypasses
 */
trait PathAware
{
    /**
     * @var string
     */
    private $path;

    /**
     * Returns the path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Update the path
     *
     * @param string $path
     *
     * @return $this
     */
    public function setPath($path)
    {
        $this->path = $this->normalisePath($path);
        return $this;
    }

    /**
     * Returns the normalised version of the given path
     *
     * @param string $path Path to normalise
     *
     * @return string normalised version of the path
     */
    protected function normalisePath($path)
    {
        if (substr($path ?? '', -1) !== '/') {
            return $path . '/';
        } else {
            return $path;
        }
    }
}
