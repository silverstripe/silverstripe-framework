<?php

namespace SilverStripe\Control\Tests;

/**
 * These helpful tests were lifted from the Symfony library
 * https://github.com/symfony/http-foundation/blob/master/LICENSE
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\Util\IPUtils;
use SilverStripe\Dev\Deprecation;

class IPUtilsTest extends SapphireTest
{
    /**
     * @dataProvider iPv4Provider
     */
    public function testIPv4($matches, $remoteAddr, $cidr)
    {
        Deprecation::withSuppressedNotice(function () use ($matches, $remoteAddr, $cidr) {
            $this->assertSame($matches, IPUtils::checkIP($remoteAddr, $cidr));
        });
    }

    public function iPv4Provider()
    {
        return [
            [true, '192.168.1.1', '192.168.1.1'],
            [true, '192.168.1.1', '192.168.1.1/1'],
            [true, '192.168.1.1', '192.168.1.0/24'],
            [false, '192.168.1.1', '1.2.3.4/1'],
            [false, '192.168.1.1', '192.168.1.1/33'], // invalid subnet
            [true, '192.168.1.1', ['1.2.3.4/1', '192.168.1.0/24']],
            [true, '192.168.1.1', ['192.168.1.0/24', '1.2.3.4/1']],
            [false, '192.168.1.1', ['1.2.3.4/1', '4.3.2.1/1']],
            [true, '1.2.3.4', '0.0.0.0/0'],
            [true, '1.2.3.4', '192.168.1.0/0'],
            [false, '1.2.3.4', '256.256.256/0'], // invalid CIDR notation
            [false, 'an_invalid_ip', '192.168.1.0/24'],
        ];
    }

    /**
     * @dataProvider iPv6Provider
     */
    public function testIPv6($matches, $remoteAddr, $cidr)
    {
        if (!defined('AF_INET6')) {
            $this->markTestSkipped('Only works when PHP is compiled without the option "disable-ipv6".');
        }

        Deprecation::withSuppressedNotice(function () use ($matches, $remoteAddr, $cidr) {
            $this->assertSame($matches, IPUtils::checkIP($remoteAddr, $cidr));
        });
    }

    public function iPv6Provider()
    {
        return [
            [true, '2a01:198:603:0:396e:4789:8e99:890f', '2a01:198:603:0::/65'],
            [false, '2a00:198:603:0:396e:4789:8e99:890f', '2a01:198:603:0::/65'],
            [false, '2a01:198:603:0:396e:4789:8e99:890f', '::1'],
            [true, '0:0:0:0:0:0:0:1', '::1'],
            [false, '0:0:603:0:396e:4789:8e99:0001', '::1'],
            [true, '2a01:198:603:0:396e:4789:8e99:890f', ['::1', '2a01:198:603:0::/65']],
            [true, '2a01:198:603:0:396e:4789:8e99:890f', ['2a01:198:603:0::/65', '::1']],
            [false, '2a01:198:603:0:396e:4789:8e99:890f', ['::1', '1a01:198:603:0::/65']],
            [false, '}__test|O:21:&quot;JDatabaseDriverMysqli&quot;:3:{s:2', '::1'],
            [false, '2a01:198:603:0:396e:4789:8e99:890f', 'unknown'],
        ];
    }

    /**
     * @requires extension sockets
     */
    public function testAnIPv6WithOptionDisabledIPv6()
    {
        $this->expectException(\RuntimeException::class);
        if (defined('AF_INET6')) {
            $this->markTestSkipped('Only works when PHP is compiled with the option "disable-ipv6".');
        }

        Deprecation::withSuppressedNotice(function () {
            IPUtils::checkIP('2a01:198:603:0:396e:4789:8e99:890f', '2a01:198:603:0::/65');
        });
    }
}
