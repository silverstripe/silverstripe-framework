<?php

namespace SilverStripe\Core;

use InvalidArgumentException;

/**
 * Path manipulation helpers
 */
class Path
{
    const TRIM_CHARS = ' /\\';

    /**
     * Joins one or more paths, normalising all separators to DIRECTORY_SEPARATOR
     *
     * Note: Errors on collapsed `/../` for security reasons. Use realpath() if you need to
     * join a trusted relative path.
     * @link https://www.owasp.org/index.php/Testing_Directory_traversal/file_include_(OTG-AUTHZ-001)
     * @see File::join_paths() for joining file identifiers
     *
     * @param array $parts
     * @return string Combined path, not including trailing slash (unless it's a single slash)
     */
    public static function join(...$parts)
    {
        // In case $parts passed as an array in first parameter
        if (count($parts ?? []) === 1 && is_array($parts[0])) {
            $parts = $parts[0];
        }

        // Cleanup and join all parts
        $parts = array_filter(array_map('trim', array_filter($parts ?? [])));
        $fullPath = static::normalise(implode(DIRECTORY_SEPARATOR, $parts));

        // Protect against directory traversal vulnerability (OTG-AUTHZ-001)
        if ($fullPath === '..' || str_ends_with($fullPath, '/..') || str_contains($fullPath, '../')) {
            throw new InvalidArgumentException('Can not collapse relative folders');
        }

        return $fullPath ?: DIRECTORY_SEPARATOR;
    }

    /**
     * Normalise absolute or relative filesystem path.
     * Important: Single slashes are converted to empty strings (empty relative paths)
     *
     * @param string $path Input path
     * @param bool $relative
     * @return string Path with no trailing slash. If $relative is true, also trim leading slashes
     */
    public static function normalise($path, $relative = false)
    {
        $path = trim(Convert::slashes($path) ?? '');
        if ($relative) {
            return trim($path ?? '', Path::TRIM_CHARS ?? '');
        } else {
            return rtrim($path ?? '', Path::TRIM_CHARS ?? '');
        }
    }
}
