<?php

namespace SilverStripe\Core\Tests;

use InvalidArgumentException;
use SilverStripe\Core\Path;
use SilverStripe\Dev\SapphireTest;

class PathTest extends SapphireTest
{
    /**
     * Test paths are joined
     *
     * @dataProvider providerTestJoinPaths
     * @param array $args Arguments to pass to Path::join()
     * @param string $expected Expected path
     */
    public function testJoinPaths($args, $expected)
    {
        $joined = Path::join($args);
        $this->assertEquals($expected, $joined);
    }

    /**
     * List of tests for testJoinPaths
     *
     * @return array
     */
    public function providerTestJoinPaths()
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
            // join blocks of paths
            [['/root/dir', 'another/path\\to/join'], '/root/dir/another/path/to/join'],
        ];

        // Rewrite tests for other filesystems (output arg only)
        if (DIRECTORY_SEPARATOR !== '/') {
            foreach ($tests as $index => $test) {
                $tests[$index][1] = str_replace('/', DIRECTORY_SEPARATOR, $tests[$index][1]);
            }
        }
        return $tests;
    }

    /**
     * Test that joinPaths give the appropriate error
     *
     * @dataProvider providerTestJoinPathsErrors
     * @param array $args Arguments to pass to Filesystem::joinPath()
     * @param string $error Expected path
     */
    public function testJoinPathsErrors($args, $error)
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage($error);
        Path::join($args);
    }

    public function providerTestJoinPathsErrors()
    {
        return [
            [['/base', '../passwd'], 'Can not collapse relative folders'],
            [['/base/../', 'passwd/path'], 'Can not collapse relative folders'],
            [['../', 'passwd/path'], 'Can not collapse relative folders'],
        ];
    }

    /**
     * @dataProvider providerTestNormalise
     * @param string $input
     * @param string $expected
     */
    public function testNormalise($input, $expected)
    {
        $output = Path::normalise($input);
        $this->assertEquals($expected, $output, "Expected $input to be normalised to $expected");
    }

    public function providerTestNormalise()
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
                $tests[$index][1] = str_replace('/', DIRECTORY_SEPARATOR, $tests[$index][1]);
            }
        }
        return $tests;
    }
}
