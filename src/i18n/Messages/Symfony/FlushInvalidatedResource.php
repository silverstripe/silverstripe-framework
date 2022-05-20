<?php

namespace SilverStripe\i18n\Messages\Symfony;

use SilverStripe\Core\Flushable;
use Symfony\Component\Config\Resource\DirectoryResource;
use Symfony\Component\Config\Resource\SelfCheckingResourceInterface;

/**
 * Some arbitrary resource which expires when flush is invoked.
 * Uses a canary file to mark future freshness requests as stale.
 *
 * @link https://media.giphy.com/media/fRRD3T37DeY6Y/giphy.gif for use case
 * @see DirectoryResource
 */
class FlushInvalidatedResource implements SelfCheckingResourceInterface, \Serializable, Flushable
{

    public function __toString()
    {
        return md5(__CLASS__);
    }

    public function getResource()
    {
        // @deprecated at 3.0, do nothing
        return null;
    }

    public function isFresh($timestamp)
    {
        // Check mtime of canary
        $canary = static::canary();
        if (file_exists($canary ?? '')) {
            return filemtime($canary ?? '') < $timestamp;
        }

        // Rebuild canary
        static::touch();
        return false;
    }

    public function __serialize(): array
    {
        return [];
    }

    public function __unserialize(array $data): void
    {
        // no-op
    }

    /**
     * The __serialize() magic method will be automatically used instead of this
     *
     * @return string
     * @deprecated will be removed in 5.0
     */
    public function serialize()
    {
        return '';
    }

    /**
     * The __unserialize() magic method will be automatically used instead of this almost all the time
     * This method will be automatically used if existing serialized data was not saved as an associative array
     * and the PHP version used in less than PHP 9.0
     *
     * @param string $serialized
     * @deprecated will be removed in 5.0
     */
    public function unserialize($serialized)
    {
        // no-op
    }

    public static function flush()
    {
        // Mark canary as dirty
        static::touch();
    }

    /**
     * Path to i18n canary
     *
     * @return string
     */
    protected static function canary()
    {
        return TEMP_PATH . DIRECTORY_SEPARATOR . 'catalog.i18n_canary';
    }

    /**
     * Touch the canary
     */
    protected static function touch()
    {
        touch(static::canary() ?? '');
    }
}
