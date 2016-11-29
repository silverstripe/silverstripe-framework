<?php

namespace SilverStripe\Core\Manifest;

/**
 * Same as ManifestCache_File, but stores the data as valid PHP which gets included to load
 * This is a bit faster if you have an opcode cache installed, but slower otherwise
 */
class ManifestCache_File_PHP extends ManifestCache_File
{
    function load($key)
    {
        global $loaded_manifest;
        $loaded_manifest = null;

        $file = $this->folder . DIRECTORY_SEPARATOR . 'cache_' . $key;
        if (file_exists($file)) {
            include $file;
        }

        return $loaded_manifest;
    }

    function save($data, $key)
    {
        $file = $this->folder . DIRECTORY_SEPARATOR. 'cache_' . $key;
        file_put_contents($file, '<?php $loaded_manifest = ' . var_export($data, true) . ';');
    }
}
