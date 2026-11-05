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

        // Remove empty parts and trim all parts
        // Explicitly allow zero as a segment in the path (e.g. /some/path/0/file.txt)
        $notEmpty = fn(mixed $part) => ($part === 0 || $part === '0' || (bool) $part);
        $parts = array_filter(array_map('trim', array_filter($parts ?? [], $notEmpty)), $notEmpty);

        // Normalise and join parts
        $fullPath = static::normalise(implode(DIRECTORY_SEPARATOR, $parts));

        // Protect against directory traversal vulnerability (OTG-AUTHZ-001)
        if ($fullPath === '..' || str_ends_with($fullPath, '/..') || str_contains($fullPath, '../')) {
            throw new InvalidArgumentException('Can not collapse relative folders');
        }

        if ($fullPath === '') {
            return DIRECTORY_SEPARATOR;
        }

        return $fullPath;
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

    /**
     * Resolve a path to an existing file on the local filesystem.
     *
     * Use this instead of file_exists() where a path must refer to a file on disk, for example
     * a template that will be compiled and executed. file_exists() also accepts stream wrapper
     * paths such as ftp:// and phar://, which would let a remote or crafted file stand in for a
     * local one. realpath() only resolves plain filesystem paths, so every stream wrapper
     * (including file://) is rejected without having to recognise its syntax.
     *
     * Relative paths resolve against the current working directory, as they do for file_exists().
     *
     * @return string|null The canonical absolute path, or null if $path is not an existing local file
     */
    public static function resolveLocalFile(string $path): ?string
    {
        // realpath('') returns the working directory, and realpath() throws on null bytes
        if ($path === '' || str_contains($path, "\0")) {
            return null;
        }
        $resolved = realpath($path);
        if ($resolved === false || !is_file($resolved)) {
            return null;
        }
        return $resolved;
    }
}
