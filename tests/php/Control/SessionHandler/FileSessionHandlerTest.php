<?php

namespace SilverStripe\Control\Tests\SessionHandler;

use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;
use ReflectionProperty;
use RuntimeException;
use SilverStripe\Control\Session;
use SilverStripe\Control\SessionHandler\FileSessionHandler;
use SilverStripe\Core\Path;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\FieldType\DBDatetime;
use Symfony\Component\Filesystem\Filesystem;

class FileSessionHandlerTest extends SapphireTest
{
    private static string $sessionSavePath = __DIR__ . '/FileSessionHandlerTest';

    protected $usesDatabase = false;

    private string|false $gcLifeTime;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gcLifeTime = ini_get('session.gc_maxlifetime');
    }

    protected function tearDown(): void
    {
        ini_set('session.gc_maxlifetime', $this->gcLifeTime);
        parent::tearDown();
    }

    public static function provideOpen(): array
    {
        return [
            'empty string path' => [
                'path' => '',
                'expected' => true,
            ],
            'valid path on its own' => [
                'path' => static::$sessionSavePath,
                'expected' => true,
            ],
            'valid path with config' => [
                'path' => '0;600;' . static::$sessionSavePath,
                'expected' => true,
            ],
            'valid path with invalid config' => [
                'path' => ';0;600;' . static::$sessionSavePath,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideOpen')]
    public function testOpen(string $path, bool $expected): void
    {
        $handler = new FileSessionHandler();
        $result = $handler->open($path, 'PHPSESSID');
        $this->assertSame($expected, $result);
    }

    public static function provideSetSavePath(): array
    {
        return [
            'empty string path' => [
                'sessionSavePath' => '',
                'baseDir' => sys_get_temp_dir(),
                'numSubDirs' => 0,
                'mode' => 0600,
            ],
            'just a path' => [
                'sessionSavePath' => static::$sessionSavePath,
                'baseDir' => static::$sessionSavePath,
                'numSubDirs' => 0,
                'mode' => 0600,
            ],
            'path and subdirs' => [
                'sessionSavePath' => '1;' . static::$sessionSavePath,
                'baseDir' => static::$sessionSavePath,
                'numSubDirs' => 1,
                'mode' => 0600,
            ],
            'all config defined' => [
                'sessionSavePath' => '2;123;' . static::$sessionSavePath,
                'baseDir' => static::$sessionSavePath,
                'numSubDirs' => 2,
                'mode' => 0123,
            ],
            'full octal mode' => [
                'sessionSavePath' => '3;0766;' . static::$sessionSavePath,
                'baseDir' => static::$sessionSavePath,
                'numSubDirs' => 3,
                'mode' => 0766,
            ],
        ];
    }

    #[DataProvider('provideSetSavePath')]
    public function testSetSavePath(string $sessionSavePath, string $baseDir, int $numSubDirs, int $mode): void
    {
        // Set path
        $handler = new FileSessionHandler();
        $reflectionMethod = new ReflectionMethod($handler, 'setSavePath');
        $reflectionMethod->setAccessible(true);
        $reflectionMethod->invoke($handler, $sessionSavePath);

        // Check baseDir
        $reflectionBaseDir = new ReflectionProperty($handler, 'baseDir');
        $reflectionBaseDir->setAccessible(true);
        $this->assertSame($baseDir, $reflectionBaseDir->getValue($handler));

        // Check numSubDirs
        $reflectionNumSubDirs = new ReflectionProperty($handler, 'numSubDirs');
        $reflectionNumSubDirs->setAccessible(true);
        $this->assertSame($numSubDirs, $reflectionNumSubDirs->getValue($handler));

        // Check mode
        $reflectionMode = new ReflectionProperty($handler, 'mode');
        $reflectionMode->setAccessible(true);
        $this->assertSame($mode, $reflectionMode->getValue($handler));
    }

    public static function provideGetFilePath(): array
    {
        return [
            'path no subdirs' => [
                'savePath' => static::$sessionSavePath,
                'sessionID' => 'a0f123456789',
                'expected' => Path::join(static::$sessionSavePath, 'sess_a0f123456789'),
            ],
            'path three subdirs' => [
                'savePath' => '3;' . static::$sessionSavePath,
                'sessionID' => 'a0f123456789',
                'expected' => Path::join(static::$sessionSavePath, 'a/0/f', 'sess_a0f123456789'),
            ],
        ];
    }

    #[DataProvider('provideGetFilePath')]
    public function testGetFilePath(string $savePath, string $sessionID, string $expected): void
    {
        $handler = new FileSessionHandler();
        $reflectionSetSavePath = new ReflectionMethod($handler, 'setSavePath');
        $reflectionSetSavePath->setAccessible(true);
        $reflectionSetSavePath->invoke($handler, $savePath);

        $reflectionGetFilePath = new ReflectionMethod($handler, 'getFilePath');
        $reflectionGetFilePath->setAccessible(true);
        $this->assertSame($expected, $reflectionGetFilePath->invoke($handler, $sessionID));
    }

    public static function provideNeedsPermissionUpdate(): array
    {
        return [
            'default settings needs update' => [
                'savePath' => static::$sessionSavePath,
                'createFileBeforeTest' => true,
                'expected' => true,
            ],
            'custom settings needs update' => [
                'savePath' => '0;755;' . static::$sessionSavePath,
                'createFileBeforeTest' => true,
                'expected' => true,
            ],
            'custom settings already correct' => [
                'savePath' => '0;777;' . static::$sessionSavePath,
                'createFileBeforeTest' => true,
                'expected' => false,
            ],
            'file missing, assume needs update' => [
                'savePath' => '0;777;' . static::$sessionSavePath,
                'createFileBeforeTest' => false,
                'expected' => true,
            ],
        ];
    }

    #[DataProvider('provideNeedsPermissionUpdate')]
    public function testNeedsPermissionUpdate(string $savePath, bool $createFileBeforeTest, bool $expected): void
    {
        $sessionFilePath = Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'new-session');
        $this->assertFileDoesNotExist($sessionFilePath);

        $handler = new FileSessionHandler();
        $handler->open($savePath, 'PHPSESSID');
        $reflectionNeedsPermissionUpdate = new ReflectionMethod($handler, 'needsPermissionUpdate');
        $reflectionNeedsPermissionUpdate->setAccessible(true);

        try {
            if ($createFileBeforeTest) {
                file_put_contents($sessionFilePath, 'original content');
                chmod($sessionFilePath, 0777);
            }
            $this->assertSame($expected, $reflectionNeedsPermissionUpdate->invoke($handler, $sessionFilePath));
        } finally {
            if (file_exists($sessionFilePath)) {
                unlink($sessionFilePath);
            }
        }
    }

    public static function provideIsSessionExpired(): array
    {
        return [
            'file exists and is expired' => [
                'isExpired' => true,
            ],
            'file exists, not expired' => [
                'isExpired' => false,
            ],
        ];
    }

    #[DataProvider('provideIsSessionExpired')]
    public function testIsSessionExpired(bool $isExpired): void
    {
        $sessionFilePath = Path::join(static::$sessionSavePath, 'sess_new-session');
        $this->assertFileDoesNotExist($sessionFilePath);

        file_put_contents($sessionFilePath, 'original content');

        try {
            $this->withSessionExpiry($sessionFilePath, function () use ($isExpired, $sessionFilePath) {
                $handler = new FileSessionHandler();
                $reflectionIsSessionExpired = new ReflectionMethod($handler, 'isSessionExpired');
                $reflectionIsSessionExpired->setAccessible(true);
                $this->assertSame($isExpired, $reflectionIsSessionExpired->invoke($handler, $sessionFilePath));
            }, $isExpired);
        } finally {
            unlink($sessionFilePath);
        }
    }

    public function testIsSessionExpiredMissingFile()
    {
        $sessionFilePath = Path::join(static::$sessionSavePath, 'sess_new-session');
        $this->assertFileDoesNotExist($sessionFilePath);

        $handler = new FileSessionHandler();
        $reflectionIsSessionExpired = new ReflectionMethod($handler, 'isSessionExpired');
        $reflectionIsSessionExpired->setAccessible(true);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not read modified time of session file');
        $reflectionIsSessionExpired->invoke($handler, $sessionFilePath);

        // Make sure the file didn't get inadvertently created
        $this->assertFileDoesNotExist($sessionFilePath);
    }

    public static function provideRead(): array
    {
        return [
            'new session (aka no file)' => [
                'savePath' => static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'new-session'),
                'sessionID' => 'new-session',
                'expected' => '',
            ],
            'existing session' => [
                'savePath' => static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'test-session1'),
                'sessionID' => 'test-session1',
                'expected' => "session1 value\n",
            ],
            'existing session with subdirs' => [
                'savePath' => '2;' . static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, 't/e', FileSessionHandler::SESSION_FILE_PREFIX . 'test-session2'),
                'sessionID' => 'test-session2',
                'expected' => "session2 value\n",
            ],
        ];
    }

    #[DataProvider('provideRead')]
    public function testRead(string $savePath, string $sessionFilePath, string $sessionID, string $expected): void
    {
        $handler = new FileSessionHandler();
        $handler->open($savePath, 'PHPSESSID');

        // Make sure the file hasn't expired when reading
        $this->withSessionExpiry($sessionFilePath, function () use ($expected, $handler, $sessionID) {
            $this->assertSame($expected, $handler->read($sessionID));
        });

        if ($expected === '') {
            // Make sure no new file is created for new sessions
            $this->assertFileDoesNotExist($sessionFilePath);
        } else {
            // Check file was not locked
            $fileHandler = fopen($sessionFilePath, 'c+');
            $this->assertTrue(flock($fileHandler, LOCK_EX|LOCK_NB, $wouldblock));
            $this->assertSame(0, $wouldblock);
            fclose($fileHandler);
        }
    }

    public function testReadExpired(): void
    {
        $handler = new FileSessionHandler();
        $handler->open(static::$sessionSavePath, 'PHPSESSID');

        $sessionID = 'test-session1';
        $sessionFilePath = Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . $sessionID);

        // Make sure the file has expired when reading
        $this->withSessionExpiry($sessionFilePath, function () use ($handler, $sessionID) {
            $this->assertSame('', $handler->read($sessionID));
        }, true);
    }

    public static function provideWrite(): array
    {
        return [
            'overrides existing file' => [
                'savePath' => static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'test-existing-session'),
                'sessionID' => 'test-existing-session',
                'createFileBeforeTest' => true,
            ],
            'overrides existing file with subdirs' => [
                'savePath' => '2;' . static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, 't/e', FileSessionHandler::SESSION_FILE_PREFIX . 'test-existing-session'),
                'sessionID' => 'test-existing-session',
                'createFileBeforeTest' => true,
            ],
            'creates new file' => [
                'savePath' => static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'test-new-session'),
                'sessionID' => 'test-new-session',
                'createFileBeforeTest' => false,
            ],
            'creates new file with subdirs' => [
                'savePath' => '2;' . static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, 't/e', FileSessionHandler::SESSION_FILE_PREFIX . 'test-new-session'),
                'sessionID' => 'test-new-session',
                'createFileBeforeTest' => false,
            ],
        ];
    }

    #[DataProvider('provideWrite')]
    public function testWrite(string $savePath, string $sessionFilePath, string $sessionID, bool $createFileBeforeTest): void
    {
        $handler = new FileSessionHandler();
        $handler->open($savePath, 'PHPSESSID');
        $this->assertFileDoesNotExist($sessionFilePath);

        try {
            if ($createFileBeforeTest) {
                file_put_contents($sessionFilePath, 'original content');
            }
            $this->assertTrue($handler->write($sessionID, 'New content now'));
            $this->assertFileExists($sessionFilePath);
            $this->assertSame('New content now', file_get_contents($sessionFilePath));
        } finally {
            unlink($sessionFilePath);
        }
    }

    public static function provideWriteUpdatesPermissions(): array
    {
        return [
            'default perms' => [
                'savePath' => static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'test-new-session'),
                'sessionID' => 'test-new-session',
                'expected' => '0600',
            ],
            'custom perms' => [
                'savePath' => '0;0555;' . static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'test-new-session'),
                'sessionID' => 'test-new-session',
                'expected' => '0555',
            ],
            'same as existing perms' => [
                'savePath' => '0;0777;' . static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'test-new-session'),
                'sessionID' => 'test-new-session',
                'expected' => '0777',
            ],
        ];
    }

    #[DataProvider('provideWriteUpdatesPermissions')]
    public function testWriteUpdatesPermissions(string $savePath, string $sessionFilePath, string $sessionID, string $expected): void
    {
        $handler = new FileSessionHandler();
        $handler->open($savePath, 'PHPSESSID');
        $this->assertFileDoesNotExist($sessionFilePath);

        file_put_contents($sessionFilePath, 'some content');

        try {
            chmod($sessionFilePath, 0777);
            $this->assertTrue($handler->write($sessionID, 'New content now'));
            $perms = substr(sprintf('%o', fileperms($sessionFilePath)), -4);
            $this->assertSame($expected, $perms);
        } finally {
            unlink($sessionFilePath);
        }
    }

    public static function provideDestroy(): array
    {
        return [
            'deletes existing file' => [
                'savePath' => static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'test-existing-session'),
                'sessionID' => 'test-existing-session',
                'createFileBeforeTest' => true,
            ],
            'deletes existing file with subdirs' => [
                'savePath' => '2;' . static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, 't/e', FileSessionHandler::SESSION_FILE_PREFIX . 'test-existing-session'),
                'sessionID' => 'test-existing-session',
                'createFileBeforeTest' => true,
            ],
            'no action for missing file' => [
                'savePath' => static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'test-new-session'),
                'sessionID' => 'test-new-session',
                'createFileBeforeTest' => false,
            ],
            'no action for missing file with subdirs' => [
                'savePath' => '2;' . static::$sessionSavePath,
                'sessionFilePath' => Path::join(static::$sessionSavePath, 't/e', FileSessionHandler::SESSION_FILE_PREFIX . 'test-new-session'),
                'sessionID' => 'test-new-session',
                'createFileBeforeTest' => false,
            ],
        ];
    }

    #[DataProvider('provideDestroy')]
    public function testDestroy(string $savePath, string $sessionFilePath, string $sessionID, bool $createFileBeforeTest): void
    {
        $handler = new FileSessionHandler();
        $handler->open($savePath, 'PHPSESSID');
        $this->assertFileDoesNotExist($sessionFilePath);

        if ($createFileBeforeTest) {
            file_put_contents($sessionFilePath, 'some content');
        }

        try {
            $this->assertTrue($handler->destroy($sessionID));
            $this->assertFileDoesNotExist($sessionFilePath);
        } finally {
            if (file_exists($sessionFilePath)) {
                unlink($sessionFilePath);
            }
        }
    }

    public static function provideGc(): array
    {
        $file1 = Path::join(static::$sessionSavePath, 'gc-test', 'sess_expired-session1');
        $file2 = Path::join(static::$sessionSavePath, 'gc-test', 'sess_expired-session2');
        $file3 = Path::join(static::$sessionSavePath, 'gc-test', 'e/x/sess_expired-session3');
        $file4 = Path::join(static::$sessionSavePath, 'gc-test', 'e/x/sess_expired-session4');
        return [
            'respect config' => [
                'gcLifetime' => 100,
                'configLifetime' => 500,
                'sessionFilesLifetimeMap' => [
                    $file1 => 50,
                    $file2 => 550,
                    $file3 => 150,
                    $file4 => 1000,
                ],
                'expectDeleted' => [
                    $file2,
                    $file4,
                ],
            ],
            'fall back on gc' => [
                'gcLifetime' => 100,
                'configLifetime' => 0,
                'sessionFilesLifetimeMap' => [
                    $file1 => 50,
                    $file2 => 550,
                    $file3 => 150,
                    $file4 => 1000,
                ],
                'expectDeleted' => [
                    $file2,
                    $file3,
                    $file4,
                ],
            ],
        ];
    }

    #[DataProvider('provideGc')]
    public function testGc(int $gcLifetime, int $configLifetime, array $sessionFilesLifetimeMap, array $expectDeleted): void
    {
        $baseDir = Path::join(static::$sessionSavePath, 'gc-test');
        $nonSessionFilePath = Path::join($baseDir, 'non-session-file');

        $handler = new FileSessionHandler();
        $handler->open($baseDir, 'PHPSESSID');

        ini_set('session.gc_maxlifetime', $gcLifetime);
        Session::config()->set('timeout', $configLifetime);

        try {
            $this->withSessionExpiry($nonSessionFilePath, function () use ($sessionFilesLifetimeMap, $handler, $nonSessionFilePath, $expectDeleted) {
                // Create the dummy files and set their modified time as appropriate
                // Use symfony filesystem so that any subdirs are automatically created
                $filesystem = new Filesystem();
                $now = DBDatetime::now()->getTimestamp();
                foreach ($sessionFilesLifetimeMap as $path => $lifetime) {
                    $filesystem->dumpFile($path, 'some content');
                    $mtime = $now - $lifetime;
                    $filesystem->touch($path, $mtime, $mtime);
                }
                // Run gc
                $numDeleted = $handler->gc(1);
                // Check it deleted the right things
                foreach ($expectDeleted as $path) {
                    $this->assertFileDoesNotExist($path);
                }
                foreach (array_diff(array_keys($sessionFilesLifetimeMap), $expectDeleted) as $path) {
                    $this->assertFileExists($path);
                }
                $this->assertFileExists($nonSessionFilePath);
            }, true);
        } finally {
            foreach (array_keys($sessionFilesLifetimeMap) as $filePath) {
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }
            rmdir(Path::join($baseDir, 'e/x'));
            rmdir(Path::join($baseDir, 'e'));
        }
    }

    public static function provideValidateId(): array
    {
        return [
            'new session (no file) is invalid' => [
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'new-session'),
                'sessionID' => 'new-session',
                'isExpired' => true,
                'expected' => false,
            ],
            'new session (no file) is invalid (expiry doesnt change anything)' => [
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'new-session'),
                'sessionID' => 'new-session',
                'isExpired' => false,
                'expected' => false,
            ],
            'existing session is valid' => [
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'test-session1'),
                'sessionID' => 'test-session1',
                'isExpired' => false,
                'expected' => true,
            ],
            'expired existing session is invalid' => [
                'sessionFilePath' => Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . 'test-session1'),
                'sessionID' => 'test-session1',
                'isExpired' => true,
                'expected' => false,
            ],
        ];
    }

    #[DataProvider('provideValidateId')]
    public function testValidateId(string $sessionFilePath, string $sessionID, bool $isExpired, bool $expected): void
    {
        $handler = new FileSessionHandler();
        $handler->open(static::$sessionSavePath, 'PHPSESSID');

        $this->withSessionExpiry($sessionFilePath, function () use ($expected, $handler, $sessionID) {
            $this->assertSame($expected, $handler->validateId($sessionID));
        }, $isExpired);
    }

    public static function provideUpdateTimestamp(): array
    {
        return [
            'file already exists' => [
                'createFileBeforeTest' => true,
                'expectedContent' => 'some content',
            ],
            'file doesnt exist (edge case)' => [
                'createFileBeforeTest' => false,
                'expectedContent' => '',
            ],
        ];
    }

    #[DataProvider('provideUpdateTimestamp')]
    public function testUpdateTimestamp(bool $createFileBeforeTest, string $expectedContent): void
    {
        $handler = new FileSessionHandler();
        $handler->open(static::$sessionSavePath, 'PHPSESSID');
        $sessionID = 'new-session';
        $sessionFilePath = Path::join(static::$sessionSavePath, FileSessionHandler::SESSION_FILE_PREFIX . $sessionID);
        $this->assertFileDoesNotExist($sessionFilePath);

        if ($createFileBeforeTest) {
            file_put_contents($sessionFilePath, 'some content');
        }

        try {
            $now = DBDatetime::now()->getTimestamp() + 3600;
            DBDatetime::withFixedNow($now, function () use ($handler, $sessionID) {
                $this->assertTrue($handler->updateTimestamp($sessionID, 'new content'));
            });
            $this->assertFileExists($sessionFilePath);
            $this->assertSame($now, filemtime($sessionFilePath));
            $this->assertSame($expectedContent, file_get_contents($sessionFilePath));
        } finally {
            if (file_exists($sessionFilePath)) {
                unlink($sessionFilePath);
            }
        }
    }

    /**
     * Executes a callback with "now" set to either the same time the file was modified, or a year later, depending on $isExpired.
     */
    private function withSessionExpiry(string $sessionFilePath, callable $callback, bool $isExpired = false): mixed
    {
        $now = file_exists($sessionFilePath) ? filemtime($sessionFilePath) : time();
        if ($isExpired) {
            // Make sure the file has expired by setting "now" to a year after the file was last modified.
            $now += (365 * 24 * 60 * 60);
        }
        return DBDatetime::withFixedNow($now, $callback);
    }
}
