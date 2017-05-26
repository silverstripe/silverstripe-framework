<?php
/**
 * These helpful functions were lifted from the Symfony library
 * https://github.com/symfony/http-foundation/blob/master/LICENSE
 *
 * Http utility functions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
namespace SilverStripe\Control\Util;
/**
 * Http utility functions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IPUtils {
	/**
	 * This class should not be instantiated.
	 */
	private function __construct()
	{
	}
	/**
	 * Checks if an IPv4 or IPv6 address is contained in the list of given IPs or subnets.
	 *
	 * @param string       $requestIP IP to check
	 * @param string|array $ips       List of IPs or subnets (can be a string if only a single one)
	 *
	 * @return bool Whether the IP is valid
	 *
	 * @package framework
	 * @subpackage core
	 */
	public static function checkIP($requestIP, $ips) {
		if (!is_array($ips)) {
			$ips = array($ips);
		}

		$method = substr_count($requestIP, ':') > 1 ? 'checkIP6' : 'checkIP4';

		foreach ($ips as $ip) {
			if (self::$method($requestIP, trim($ip))) {
				return true;
			}
		}

		return false;
	}
	/**
	 * Compares two IPv4 addresses.
	 * In case a subnet is given, it checks if it contains the request IP.
	 *
	 * @param string $requestIP IPv4 address to check
	 * @param string $ip        IPv4 address or subnet in CIDR notation
	 *
	 * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet
	 */
	public static function checkIP4($requestIP, $ip) {
		if (!filter_var($requestIP, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
			return false;
		}

		if (false !== strpos($ip, '/')) {
			list($address, $netmask) = explode('/', $ip, 2);

			if ($netmask === '0') {
				return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
			}

			if ($netmask < 0 || $netmask > 32) {
				return false;
			}
		} else {
			$address = $ip;
			$netmask = 32;
		}

		return 0 === substr_compare(sprintf('%032b', ip2long($requestIP)), sprintf('%032b', ip2long($address)), 0, $netmask);
	}
	/**
	 * Compares two IPv6 addresses.
	 * In case a subnet is given, it checks if it contains the request IP.
	 *
	 * @author David Soria Parra <dsp at php dot net>
	 *
	 * @see https://github.com/dsp/v6tools
	 *
	 * @param string $requestIP IPv6 address to check
	 * @param string $ip        IPv6 address or subnet in CIDR notation
	 *
	 * @return bool Whether the IP is valid
	 *
	 * @throws \RuntimeException When IPV6 support is not enabled
	 */
	public static function checkIP6($requestIP, $ip) {
		if (!((extension_loaded('sockets') && defined('AF_INET6')) || @inet_pton('::1'))) {
			throw new \RuntimeException('Unable to check IPv6. Check that PHP was not compiled with option "disable-ipv6".');
		}

		if (false !== strpos($ip, '/')) {
			list($address, $netmask) = explode('/', $ip, 2);

			if ($netmask < 1 || $netmask > 128) {
				return false;
			}
		} else {
			$address = $ip;
			$netmask = 128;
		}

		$bytesAddr = unpack('n*', @inet_pton($address));
		$bytesTest = unpack('n*', @inet_pton($requestIP));

		if (!$bytesAddr || !$bytesTest) {
			return false;
		}

		for ($i = 1, $ceil = ceil($netmask / 16); $i <= $ceil; ++$i) {
			$left = $netmask - 16 * ($i - 1);
			$left = ($left <= 16) ? $left : 16;
			$mask = ~(0xffff >> $left) & 0xffff;
			if (($bytesAddr[$i] & $mask) != ($bytesTest[$i] & $mask)) {
				return false;
			}
		}

		return true;
	}
}
