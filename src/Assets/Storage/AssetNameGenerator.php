<?php

namespace SilverStripe\Assets\Storage;

/**
 * Provides a mechanism for suggesting filename alterations to a file
 *
 * Does not actually check for existence of the file, but rather comes up with as many suggestions for
 * the given file as possible to a finite limit.
 */
interface AssetNameGenerator extends \Iterator
{

    /**
     * Construct a generator for the given filename
     *
     * @param string $filename
     */
    public function __construct($filename);

    /**
     * Number of attempts allowed
     *
     * @return int
     */
    public function getMaxTries();
}
