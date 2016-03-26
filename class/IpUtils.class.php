<?php
/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * Released under the MIT license.
 *
 * Modifications by Paolo Galeone <nessuno@nerdz.eu> to work with NERDZ.
 * Released under LGPL that's compatible with the MIT license.
 */

namespace NERDZ\Core;

/**
 * Http utility functions.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 */
class IpUtils
{
    /**
     * This class should not be instantiated.
     */
    private function __construct()
    {
    }

    /**
     * Checks if an IPv4 or IPv6 address is contained in the list of given IPs or subnets.
     *
     * @param string       $requestIp IP to check
     * @param string|array $ips       List of IPs or subnets (can be a string if only a single one)
     *
     * @return bool Whether the IP is valid
     */
    public static function checkIp($requestIp, $ips)
    {
        if (!is_array($ips)) {
            $ips = array($ips);
        }

        $method = substr_count($requestIp, ':') > 1 ? 'checkIp6' : 'checkIp4';

        foreach ($ips as $ip) {
            if (self::$method($requestIp, $ip)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Returns the IPv4 or IPv6 address of the current user, handling NERDZ TRUSTED_PROXIES.
     * If the user is behind a trusted proxy, extracts the X-Real-IP header, otherwise uses
     * the REMOTE_ADDRR.
     *
     * @return string The IP address
     */
    public static function getIp()
    {
        foreach (Config\TRUSTED_PROXIES as $proxy) {
            if (self::checkIp($_SERVER['REMOTE_ADDR'], $proxy)) {
                return self::getForwardedIp();
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Test proxy headers and returns the IP if exists. Methodu used by getIp(), therefore
     * the IP is just validated (comes from a trusted proxy).
     *
     * @return string the detected forwarted IP, if any. Otherwise returns REMOTE_ADDR
     */
    private static function getForwardedIp()
    {
        foreach ([
            'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', ] as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    return trim($ip);
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'];
    }

    /**
     * Compares two IPv4 addresses.
     * In case a subnet is given, it checks if it contains the request IP.
     *
     * @param string $requestIp IPv4 address to check
     * @param string $ip        IPv4 address or subnet in CIDR notation
     *
     * @return bool Whether the request IP matches the IP, or whether the request IP is within the CIDR subnet.
     */
    public static function checkIp4($requestIp, $ip)
    {
        if (false !== strpos($ip, '/')) {
            list($address, $netmask) = explode('/', $ip, 2);

            if ($netmask === '0') {
                // Ensure IP is valid - using ip2long below implicitly validates, but we need to do it manually here
                return filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4);
            }

            if ($netmask < 0 || $netmask > 32) {
                return false;
            }
        } else {
            $address = $ip;
            $netmask = 32;
        }

        return 0 === substr_compare(sprintf('%032b', ip2long($requestIp)), sprintf('%032b', ip2long($address)), 0, $netmask);
    }

    /**
     * Compares two IPv6 addresses.
     * In case a subnet is given, it checks if it contains the request IP.
     *
     * @author David Soria Parra <dsp at php dot net>
     *
     * @see https://github.com/dsp/v6tools
     *
     * @param string $requestIp IPv6 address to check
     * @param string $ip        IPv6 address or subnet in CIDR notation
     *
     * @return bool Whether the IP is valid
     *
     * @throws \RuntimeException When IPV6 support is not enabled
     */
    public static function checkIp6($requestIp, $ip)
    {
        if (!((extension_loaded('sockets') && defined('AF_INET6')) || @inet_pton('::1'))) {
            throw new \RuntimeException('Unable to check Ipv6. Check that PHP was not compiled with option "disable-ipv6".');
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
        $bytesTest = unpack('n*', @inet_pton($requestIp));

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
