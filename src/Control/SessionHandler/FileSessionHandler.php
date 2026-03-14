<?php

namespace SilverStripe\Control\SessionHandler;

use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use RuntimeException;
use SensitiveParameter;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Path;
use SilverStripe\ORM\FieldType\DBDatetime;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/**
 * Session save handler that stores session data in the filesystem as defined by `session.save_path` ini configuration.
 *
 * Similar to PHP's default filesystem session handler, except it doesn't lock the session file meaning
 * sessions are non-blocking.
 */
class FileSessionHandler extends AbstractSessionHandler
{
    public const string SESSION_FILE_PREFIX = 'sess_';

    /**
     * The base directory that houses session files.
     */
    private string $baseDir;

    /**
     * Number of sub-directories to use when storing session files.
     */
    private int $numSubDirs = 0;

    /**
     * The filesystem mode to use when creating session files.
     */
    private int $mode = 0600;

    public function close(): bool
    {
        // No action is required to close the session.
        return true;
    }

    /**
     * @inheritDoc
     * Deletes the file that represents this session ID.
     */
    public function destroy(#[SensitiveParameter] string $id): bool
    {
        $path = $this->getFilePath($id);
        $filesystem = new Filesystem();
        // If we haven't even saved the file yet, there's no action required.
        if (!$filesystem->exists($path)) {
            return true;
        }
        // Try delete the file.
        try {
            $filesystem->remove($path);
            return true;
        } catch (IOException $e) {
            $this->logError('Could not remove session file: ' . $this->getSafeExceptionMessage($e, $id));
            return false;
        }
    }

    /**
     * @inheritDoc
     * Deletes all session files which have a last modified datetime older than the session max lifetime.
     * Note that we use our own calculated session lifetime rather than the passed in lifetime which doesn't
     * take Silverstripe CMS configuration values into account.
     */
    public function gc(int $max_lifetime): int|false
    {
        $now = DBDatetime::now()->Rfc2822();
        $maxLifetime = $this->getLifetime();
        // Recursively find all session files in the base dir older than the max lifetime.
        $files = Finder::create()
            ->in($this->baseDir)
            ->files()
            ->name(FileSessionHandler::SESSION_FILE_PREFIX . '*')
            ->ignoreDotFiles(true)
            ->date('<= ' . $now . ' - ' . $maxLifetime . ' seconds');

        // Attempt to remove all found files.
        $filesystem = new Filesystem();
        $numRemoved = 0;
        $success = true;
        foreach ($files as $file) {
            try {
                $filesystem->remove($file->getRealPath());
                $numRemoved++;
            } catch (IOException $e) {
                // Log and mark failure - but don't stop trying to delete other expired session files.
                $this->logError('Could not collect session file: ' . $this->getSafeExceptionMessage($e));
                $success = false;
            }
        }

        return $success ? $numRemoved : false;
    }

    /**
     * @inheritDoc
     * Defines where and how to save session files.
     */
    public function open(string $path, string $name): bool
    {
        try {
            $this->setSavePath($path);
        } catch (InvalidArgumentException) {
            $this->logError('Could not open session due to invalid save path');
            return false;
        }
        // No action is required to open the session.
        return true;
    }

    /**
     * @inheritDoc
     * Returns data of a pre-existing session, or an empty string for a new session.
     */
    public function read(#[SensitiveParameter] string $id): string|false
    {
        $this->checkSessionID($id);
        $path = $this->getFilePath($id);
        $filesystem = new Filesystem();
        // If the file doesn't exist, it's a new session so just return empty string.
        // The documentation says to return false, but that results in a warning being emitted
        // instead of just using a new empty session.
        if (!$filesystem->exists($path)) {
            return '';
        }

        try {
            // We need to make sure we don't return session data that is already expired because gc()
            // is called after read() (and not even after *every* read).
            if ($this->isSessionExpired($path)) {
                return '';
            }
        } catch (RuntimeException $e) {
            $this->logError($this->getSafeExceptionMessage($e, $id));
            return false;
        }

        // Try reading the file contents
        try {
            return $filesystem->readFile($path);
        } catch (IOException $e) {
            $this->logError('Could not read session file: ' . $this->getSafeExceptionMessage($e, $id));
            return false;
        }
    }

    /**
     * @inheritDoc
     * Writes session data to a file and sets file permissions if necessary.
     */
    public function write(#[SensitiveParameter] string $id, string $data): bool
    {
        $path = $this->getFilePath($id);
        $filesystem = new Filesystem();

        // Create the file if it doesn't exist
        // We do this without data initially so an attacker doesn't have access to session data with potentially invalid permissions
        if (!$filesystem->exists($path)) {
            try {
                $filesystem->dumpFile($path, '');
            } catch (IOException $e) {
                $this->logError('Could not create session file: ' . $this->getSafeExceptionMessage($e, $id));
                return false;
            }
        }

        // Set file permissions
        if ($this->needsPermissionUpdate($path)) {
            try {
                $filesystem->chmod($path, (int) $this->mode);
            } catch (IOException $e) {
                $this->logError('Could not set permissions for session file: ' . $this->getSafeExceptionMessage($e, $id));
                return false;
            }
        }

        // Save file contents
        try {
            $filesystem->dumpFile($path, $data);
        } catch (IOException $e) {
            $this->logError('Could not write to session file: ' . $this->getSafeExceptionMessage($e, $id));
            return false;
        }

        return true;
    }

    /**
     * @inheritDoc
     * A session ID is valid if a file for that session ID already exists and has not expired.
     */
    public function validateId(#[SensitiveParameter] string $id): bool
    {
        $path = $this->getFilePath($id);
        $filesystem = new Filesystem();
        $fileExists = $filesystem->exists($path);
        try {
            return $fileExists && !$this->isSessionExpired($path);
        } catch (RuntimeException $e) {
            $this->logError($this->getSafeExceptionMessage($e, $id));
            // If we couldn't check the session expiry, we can give a best-guess based on whether the file exists.
            // The read() method checks the expiry independently so this is safe.
            return $fileExists;
        }
    }

    /**
     * @inheritDoc
     * Called instead of write if session.lazy_write is enabled and no data has changed for this session.
     */
    public function updateTimestamp(#[SensitiveParameter] string $id, string $data): bool
    {
        $path = $this->getFilePath($id);
        $filesystem = new Filesystem();

        try {
            // The $data isn't actually meant to be used here and seems to only be in the interface method signature
            // because when wrapping native handlers with the SessionHandler class you can't call parent::updateTimestamp()
            // and must therefore call parent::write($id, $data) - the data is needed there to avoid clearing out the
            // session data.
            $time = DBDatetime::now()->getTimestamp();
            $filesystem->touch($path, $time, $time);
            return true;
        } catch (IOException $e) {
            $this->logError('Could not update timestamp for session file: ' . $this->getSafeExceptionMessage($e, $id));
            return false;
        }
    }

    /**
     * Set the path sessions will be saved in, along with optional additional configuration.
     * @throws InvalidArgumentException if $path is invalid (e.g. has too many semicolons)
     */
    private function setSavePath(string $path): void
    {
        // If session.save-path is empty, we should use the tmp dir.
        if (!$path) {
            $path = sys_get_temp_dir();
        }
        // Handle configuration for depth and mode arguments
        // $path can have up to two optional params ending with semi-colon defining
        // 1) the number of subdirs and 2) the octal mode e.g. N;MODE;/path
        // See https://www.php.net/manual/en/session.configuration.php#ini.session.save-path for details
        if (str_contains($path, ';')) {
            $parts = explode(';', $path);
            $numParts = count($parts);
            if ($numParts > 3) {
                throw new InvalidArgumentException('$path was invalid');
            }

            // Set n depth
            $this->numSubDirs = (int) array_shift($parts);

            // Mode is the 2nd item in the config. Usually will be like '600' but we need true octal e.g. 0600
            if ($numParts === 3) {
                $this->mode = $this->ensureValueIsOctal(array_shift($parts));
            }

            // The actual path is the last part of the configuration.
            $path = array_shift($parts);
        }
        $this->baseDir = $path;
    }

    /**
     * Get the absolute path to the file which this session's data will be stored in.
     */
    private function getFilePath(#[SensitiveParameter] string $id): string
    {
        $subParts = str_split(substr($id, 0, $this->numSubDirs));
        return Path::join(...[$this->baseDir, ...$subParts, FileSessionHandler::SESSION_FILE_PREFIX . $id]);
    }

    /**
     * Take a numeric string from save_path configuration and make sure it's a PHP octal integer.
     */
    private function ensureValueIsOctal(string $value): int
    {
        if (strlen($value) < 4) {
            $value = '0' . $value;
        }
        return octdec($value);
    }

    /**
     * Check if a session file needs its permissions to be updated to match configured mode.
     */
    private function needsPermissionUpdate(#[SensitiveParameter] string $path): bool
    {
        // Silence PHP's warning, any problems here will already be logged by the calling method.
        $rawPerms = @fileperms($path);
        // If we had trouble reading the permissions, just assume it needs updating.
        // Either we'll be able to set it right, or the calling method will fail and log.
        if ($rawPerms === false) {
            return true;
        }
        // See https://www.php.net/manual/en/function.fileperms.php#refsect1-function.fileperms-examples
        $permsInOctal = octdec(substr(sprintf('%o', $rawPerms), -4));
        return $permsInOctal !== $this->mode;
    }

    /**
     * Get the exception message without leaking session IDs
     */
    private function getSafeExceptionMessage(Exception $exception, #[SensitiveParameter] ?string $id = null): string
    {
        if ($id) {
            return str_replace($id, '<session_id>', $exception->getMessage());
        }
        $prefix = FileSessionHandler::SESSION_FILE_PREFIX;
        // See https://www.php.net/manual/en/session.configuration.php#ini.session.sid-bits-per-character
        // for valid characters in a session id
        return preg_replace('/(' . $prefix . ')[,\w\-]+/', '$1<session_id>', $exception->getMessage());
    }

    /**
     * Check if a session file is expired.
     *
     * This method is necessary because garbage collection may not have run yet.
     *
     * @throws RuntimeException if the modified time of the file can't be read
     */
    private function isSessionExpired(#[SensitiveParameter] string $path): bool
    {
        // Silence PHP's warning as it's just noise at this stage. Proper logging is handled by the caller of this method.
        $mTime = @filemtime($path);
        $maxLifeInSeconds = $this->getLifetime();
        if ($mTime === false) {
            throw new RuntimeException('Could not read modified time of session file');
        }
        return $mTime < (DBDatetime::now()->getTimestamp() - $maxLifeInSeconds);
    }

    private function logError(string $message): void
    {
        Injector::inst()->get(LoggerInterface::class)->error($message);
    }
}
