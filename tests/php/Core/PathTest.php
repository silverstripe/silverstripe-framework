<?php

namespace SilverStripe\Core\Tests;

use InvalidArgumentException;
use SilverStripe\Core\Path;
use SilverStripe\Dev\SapphireTest;
use PHPUnit\Framework\Attributes\DataProvider;

class PathTest extends SapphireTest
{
    /**
     * Test paths are joined
     *
     * @param array $args Arguments to pass to Path::join()
     * @param string $expected Expected path
     */
    #[DataProvider('providerTestJoinPaths')]
    public function testJoinPaths($args, $expected)
    {
        $joined = Path::join(...$args);
        $this->assertEquals($expected, $joined);
    }

    /**
     * List of tests for testJoinPaths
     *
     * @return array
     */
    public static function providerTestJoinPaths()
    {
        $tests = [
            // Single arg
            [['/'], '/'],
            [['\\'], '/'],
            [['base'], 'base'],
            [['c:/base\\'], 'c:/base'],
            // Windows paths
            [['c:/', 'bob'], 'c:/bob'],
            [['c:/', '\\bob/'], 'c:/bob'],
            [['c:\\basedir', '/bob\\'], 'c:/basedir/bob'],
            // Empty-ish paths to clear out
            [['/root/dir', '/', ' ', 'next/', '\\'], '/root/dir/next'],
            [['/', '/', ' ', '/', '\\'], '/'],
            [['/', '', '',], '/'],
            [['/root', '/', ' ', '/', '\\'], '/root'],
            [['', '/root', '/', ' ', '/', '\\'], '/root'],
            [['', 'root', '/', ' ', '/', '\\'], 'root'],
            [['\\', '', '/root', '/', ' ', '/', '\\'], '/root'],
            [[' one ', ' ', 'two', null], 'one/two'],
            // join blocks of paths
            [['/root/dir', 'another/path\\to/join'], '/root/dir/another/path/to/join'],
            // Double dot is fine if it's not attempting directory traversal
            [['/root/my..name/', 'another/path\\to/join'], '/root/my..name/another/path/to/join'],
            // Don't remove zero
            [['0'], '0'],
            [[0], '0'],
            [['1', '0', 'a'], '1/0/a'],
            [[1, 0, 'a'], '1/0/a'],
            // first arg is array
            [[['one', 'two', 'three']], 'one/two/three'],
        ];

        // Rewrite tests for other filesystems (output arg only)
        if (DIRECTORY_SEPARATOR !== '/') {
            foreach ($tests as $index => $test) {
                $tests[$index][1] = str_replace('/', DIRECTORY_SEPARATOR, $tests[$index][1] ?? '');
            }
        }
        return $tests;
    }

    /**
     * Test that joinPaths give the appropriate error
     *
     * @param array $args Arguments to pass to Filesystem::joinPath()
     * @param string $error Expected path
     */
    #[DataProvider('providerTestJoinPathsErrors')]
    public function testJoinPathsErrors($args, $error)
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage($error);
        Path::join($args);
    }

    public static function providerTestJoinPathsErrors()
    {
        return [
            [['/base', '../passwd'], 'Can not collapse relative folders'],
            [['/base/../', 'passwd/path'], 'Can not collapse relative folders'],
            [['../', 'passwd/path'], 'Can not collapse relative folders'],
            [['..', 'passwd/path'], 'Can not collapse relative folders'],
            [['base/..', 'passwd/path'], 'Can not collapse relative folders'],
        ];
    }

    /**
     * @param string $input
     * @param string $expected
     */
    #[DataProvider('providerTestNormalise')]
    public function testNormalise($input, $expected)
    {
        $output = Path::normalise($input);
        $this->assertEquals($expected, $output, "Expected $input to be normalised to $expected");
    }

    public static function providerTestNormalise()
    {
        $tests = [
            // Windows paths
            ["c:/bob", "c:/bob"],
            ["c://bob", "c:/bob"],
            ["/root/dir/", "/root/dir"],
            ["/root\\dir\\\\sub/", "/root/dir/sub"],
            [" /some/dir/ ", "/some/dir"],
            ["", ""],
            ["/", ""],
            ["\\", ""],
        ];

        // Rewrite tests for other filesystems (output arg only)
        if (DIRECTORY_SEPARATOR !== '/') {
            foreach ($tests as $index => $test) {
                $tests[$index][1] = str_replace('/', DIRECTORY_SEPARATOR, $tests[$index][1] ?? '');
            }
        }
        return $tests;
    }

    #[DataProvider('provideResolveLocalFile')]
    public function testResolveLocalFile(string $path, bool $expectResolved): void
    {
        $expected = $expectResolved ? realpath(__FILE__) : null;
        $this->assertSame($expected, Path::resolveLocalFile($path));
    }

    public static function provideResolveLocalFile(): array
    {
        return [
            'absolute path' => [__FILE__, true],
            'path with dot segments' => [__DIR__ . '/../Core/' . basename(__FILE__), true],
            'directory' => [__DIR__, false],
            'missing file' => [__DIR__ . '/does-not-exist.php', false],
            'empty string' => ['', false],
            'null byte' => [__FILE__ . "\0.ss", false],
            'file wrapper' => ['file://' . __FILE__, false],
            'ftp wrapper' => ['ftp://127.0.0.1/' . basename(__FILE__), false],
            'php filter wrapper' => ['php://filter/resource=' . __FILE__, false],
            'compress.zlib wrapper' => ['compress.zlib://' . __FILE__, false],
            'glob wrapper' => ['glob://' . __FILE__, false],
            'data uri' => ['data:text/plain,hello', false],
        ];
    }

    public function testResolveLocalFileRelativePath(): void
    {
        $cwd = getcwd();
        try {
            chdir(__DIR__);
            $this->assertSame(realpath(__FILE__), Path::resolveLocalFile(basename(__FILE__)));
        } finally {
            chdir($cwd);
        }
    }
}
