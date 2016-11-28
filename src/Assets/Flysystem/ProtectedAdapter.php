<?php

namespace SilverStripe\Assets\Flysystem;

use League\Flysystem\AdapterInterface;

/**
 * An adapter which does not publicly expose protected files
 */
interface ProtectedAdapter extends AdapterInterface
{

    /**
     * Provide downloadable url that is restricted to granted users
     *
     * @param string $path
     * @return string|null
     */
    public function getProtectedUrl($path);
}
