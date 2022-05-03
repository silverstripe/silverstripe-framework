<?php

namespace SilverStripe\Core;

use Exception;

/**
 * Guesses location for temp folder
 */
class TempFolder
{
    /**
     * Returns the temporary folder path that silverstripe should use for its cache files.
     *
     * @param string $base The base path to use for determining the temporary path
     * @return string Path to temp
     */
    public static function getTempFolder($base)
    {
        $parent = static::getTempParentFolder($base);

        // The actual temp folder is a subfolder of getTempParentFolder(), named by username
        $subfolder = Path::join($parent, static::getTempFolderUsername());

        if (!@file_exists($subfolder ?? '')) {
            mkdir($subfolder ?? '');
        }

        return $subfolder;
    }

    /**
     * Returns as best a representation of the current username as we can glean.
     *
     * @return string
     */
    public static function getTempFolderUsername()
    {
        $user = '';
        if (function_exists('posix_getpwuid') && function_exists('posix_getuid')) {
            $userDetails = posix_getpwuid(posix_getuid());
            $user = $userDetails['name'] ?? false;
        }
        if (!$user) {
            $user = Environment::getEnv('APACHE_RUN_USER');
        }
        if (!$user) {
            $user = Environment::getEnv('USER');
        }
        if (!$user) {
            $user = Environment::getEnv('USERNAME');
        }
        if (!$user) {
            $user = 'unknown';
        }
        $user = preg_replace('/[^A-Za-z0-9_\-]/', '', $user ?? '');
        return $user;
    }

    /**
     * Return the parent folder of the temp folder.
     * The temp folder will be a subfolder of this, named by username.
     * This structure prevents permission problems.
     *
     * @param string $base
     * @return string
     * @throws Exception
     */
    protected static function getTempParentFolder($base)
    {
        // first, try finding a silverstripe-cache dir built off the base path
        $localPath = Path::join($base, 'silverstripe-cache');
        if (@file_exists($localPath ?? '')) {
            if ((fileperms($localPath ?? '') & 0777) != 0777) {
                @chmod($localPath ?? '', 0777);
            }
            return $localPath;
        }

        // failing the above, try finding a namespaced silverstripe-cache dir in the system temp
        $tempPath = Path::join(
            sys_get_temp_dir(),
            'silverstripe-cache-php' . preg_replace('/[^\w\-\.+]+/', '-', PHP_VERSION) .
            str_replace([' ', '/', ':', '\\'], '-', $base ?? '')
        );
        if (!@file_exists($tempPath ?? '')) {
            $oldUMask = umask(0);
            @mkdir($tempPath ?? '', 0777);
            umask($oldUMask);

        // if the folder already exists, correct perms
        } else {
            if ((fileperms($tempPath ?? '') & 0777) != 0777) {
                @chmod($tempPath ?? '', 0777);
            }
        }

        $worked = @file_exists($tempPath ?? '') && @is_writable($tempPath ?? '');

        // failing to use the system path, attempt to create a local silverstripe-cache dir
        if (!$worked) {
            $tempPath = $localPath;
            if (!@file_exists($tempPath ?? '')) {
                $oldUMask = umask(0);
                @mkdir($tempPath ?? '', 0777);
                umask($oldUMask);
            }

            $worked = @file_exists($tempPath ?? '') && @is_writable($tempPath ?? '');
        }

        if (!$worked) {
            throw new Exception(
                'Permission problem gaining access to a temp folder. ' . 'Please create a folder named silverstripe-cache in the base folder ' . 'of the installation and ensure it has the correct permissions'
            );
        }

        return $tempPath;
    }
}
