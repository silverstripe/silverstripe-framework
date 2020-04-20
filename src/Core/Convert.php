<?php

namespace SilverStripe\Core;

use SilverStripe\Dev\Deprecation;
use SilverStripe\ORM\DB;
use SilverStripe\View\Parsers\URLSegmentFilter;
use InvalidArgumentException;
use SimpleXMLElement;
use Exception;

/**
 * Library of conversion functions, implemented as static methods.
 *
 * The methods are all of the form (format)2(format), where the format is one of
 *
 *  raw: A UTF8 string
 *  attr: A UTF8 string suitable for inclusion in an HTML attribute
 *  js: A UTF8 string suitable for inclusion in a double-quoted javascript string.
 *
 *  array: A PHP associative array
 *  json: JavaScript object notation
 *
 *  html: HTML source suitable for use in a page or email
 *  text: Plain-text content, suitable for display to a user as-is, or insertion in a plaintext email.
 *
 * Objects of type {@link ViewableData} can have an "escaping type",
 * which determines if they are automatically escaped before output by {@link SSViewer}.
 */
class Convert
{

    /**
     * Convert a value to be suitable for an XML attribute.
     *
     * Warning: Does not escape array keys
     *
     * @param array|string $val String to escape, or array of strings
     * @return array|string
     */
    public static function raw2att($val)
    {
        return self::raw2xml($val);
    }

    /**
     * Convert a value to be suitable for an HTML attribute.
     *
     * Warning: Does not escape array keys
     *
     * @param string|array $val String to escape, or array of strings
     * @return array|string
     */
    public static function raw2htmlatt($val)
    {
        return self::raw2att($val);
    }

    /**
     * Convert a value to be suitable for an HTML ID attribute. Replaces non
     * supported characters with a space.
     *
     * Warning: Does not escape array keys
     *
     * @see http://www.w3.org/TR/REC-html40/types.html#type-cdata
     *
     * @param array|string $val String to escape, or array of strings
     *
     * @return array|string
     */
    public static function raw2htmlname($val)
    {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = self::raw2htmlname($v);
            }

            return $val;
        }

        return self::raw2att($val);
    }

    /**
     * Convert a value to be suitable for an HTML ID attribute. Replaces non
     * supported characters with an underscore.
     *
     * Warning: Does not escape array keys
     *
     * @see http://www.w3.org/TR/REC-html40/types.html#type-cdata
     *
     * @param array|string $val String to escape, or array of strings
     *
     * @return array|string
     */
    public static function raw2htmlid($val)
    {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = self::raw2htmlid($v);
            }

            return $val;
        }

        return trim(
            preg_replace(
                '/_+/',
                '_',
                preg_replace('/[^a-zA-Z0-9\-_:.]+/', '_', $val)
            ),
            '_'
        );
    }

    /**
     * Ensure that text is properly escaped for XML.
     *
     * Warning: Does not escape array keys
     *
     * @see http://www.w3.org/TR/REC-xml/#dt-escape
     * @param array|string $val String to escape, or array of strings
     * @return array|string
     */
    public static function raw2xml($val)
    {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = self::raw2xml($v);
            }
            return $val;
        }

        return htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Ensure that text is properly escaped for Javascript.
     *
     * Warning: Does not escape array keys
     *
     * @param array|string $val String to escape, or array of strings
     * @return array|string
     */
    public static function raw2js($val)
    {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = self::raw2js($v);
            }
            return $val;
        }

        return str_replace(
            // Intercepts some characters such as <, >, and & which can interfere
            ["\\", '"', "\n", "\r", "'", '<', '>', '&'],
            ["\\\\", '\"', '\n', '\r', "\\'", "\\x3c", "\\x3e", "\\x26"],
            $val
        );
    }

    /**
     * Encode a value as a JSON encoded string. You can optionally pass a bitmask of
     * JSON constants as options through to the encode function.
     *
     * @deprecated 4.4.0:5.0.0 Use json_encode() instead
     * @param  mixed $val     Value to be encoded
     * @param  int   $options Optional bitmask of JSON constants
     * @return string           JSON encoded string
     */
    public static function raw2json($val, $options = 0)
    {
        Deprecation::notice('4.4', 'Please use json_encode() instead.');

        return json_encode($val, $options);
    }

    /**
     * Encode an array as a JSON encoded string.
     *
     * @deprecated 4.4.0:5.0.0 Use json_encode() instead
     * @param  array  $val     Array to convert
     * @param  int    $options Optional bitmask of JSON constants
     * @return string          JSON encoded string
     */
    public static function array2json($val, $options = 0)
    {
        Deprecation::notice('4.4', 'Please use json_encode() instead.');

        return json_encode($val, $options);
    }

    /**
     * Safely encodes a value (or list of values) using the current database's
     * safe string encoding method
     *
     * Warning: Does not encode array keys
     *
     * @param mixed|array $val Input value, or list of values as an array
     * @param boolean $quoted Flag indicating whether the value should be safely
     * quoted, instead of only being escaped. By default this function will
     * only escape the string (false).
     * @return string|array Safely encoded value in the same format as the input
     */
    public static function raw2sql($val, $quoted = false)
    {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = self::raw2sql($v, $quoted);
            }
            return $val;
        }

        if ($quoted) {
            return DB::get_conn()->quoteString($val);
        }

        return DB::get_conn()->escapeString($val);
    }

    /**
     * Safely encodes a SQL symbolic identifier (or list of identifiers), such as a database,
     * table, or column name. Supports encoding of multi identfiers separated by
     * a delimiter (e.g. ".")
     *
     * @param string|array $identifier The identifier to escape. E.g. 'SiteTree.Title' or list of identifiers
     * to be joined via the separator.
     * @param string $separator The string that delimits subsequent identifiers
     * @return string The escaped identifier. E.g. '"SiteTree"."Title"'
     */
    public static function symbol2sql($identifier, $separator = '.')
    {
        return DB::get_conn()->escapeIdentifier($identifier, $separator);
    }

    /**
     * Convert XML to raw text.
     *
     * Warning: Does not decode array keys
     *
     * @uses html2raw()
     * @todo Currently &#xxx; entries are stripped; they should be converted
     * @param mixed $val
     * @return array|string
     */
    public static function xml2raw($val)
    {
        if (is_array($val)) {
            foreach ($val as $k => $v) {
                $val[$k] = self::xml2raw($v);
            }
            return $val;
        }

        // More complex text needs to use html2raw instead
        if (strpos($val, '<') !== false) {
            return self::html2raw($val);
        }

        return html_entity_decode($val, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Convert a JSON encoded string into an object.
     *
     * @deprecated 4.4.0:5.0.0 Use json_decode() instead
     * @param string $val
     * @return object|boolean
     */
    public static function json2obj($val)
    {
        Deprecation::notice('4.4', 'Please use json_decode() instead.');

        return json_decode($val);
    }

    /**
     * Convert a JSON string into an array.
     *
     * @deprecated 4.4.0:5.0.0 Use json_decode() instead
     * @param string $val JSON string to convert
     * @return array|boolean
     */
    public static function json2array($val)
    {
        Deprecation::notice('4.4', 'Please use json_decode() instead.');

        return json_decode($val, true);
    }

    /**
     * Converts an XML string to a PHP array
     * See http://phpsecurity.readthedocs.org/en/latest/Injection-Attacks.html#xml-external-entity-injection
     *
     * @uses recursiveXMLToArray()
     * @param string $val
     * @param boolean $disableDoctypes Disables the use of DOCTYPE, and will trigger an error if encountered.
     * false by default.
     * @param boolean $disableExternals Disables the loading of external entities. false by default.
     * @return array
     * @throws Exception
     */
    public static function xml2array($val, $disableDoctypes = false, $disableExternals = false)
    {
        // Check doctype
        if ($disableDoctypes && preg_match('/\<\!DOCTYPE.+]\>/', $val)) {
            throw new InvalidArgumentException('XML Doctype parsing disabled');
        }

        // Disable external entity loading
        $oldVal = null;
        if ($disableExternals) {
            $oldVal = libxml_disable_entity_loader($disableExternals);
        }
        try {
            $xml = new SimpleXMLElement($val);
            $result = self::recursiveXMLToArray($xml);
        } catch (Exception $ex) {
            if ($disableExternals) {
                libxml_disable_entity_loader($oldVal);
            }
            throw $ex;
        }
        if ($disableExternals) {
            libxml_disable_entity_loader($oldVal);
        }
        return $result;
    }

    /**
     * Convert a XML string to a PHP array recursively. Do not
     * call this function directly, Please use {@link Convert::xml2array()}
     *
     * @param SimpleXMLElement
     *
     * @return mixed
     */
    protected static function recursiveXMLToArray($xml)
    {
        $x = null;
        if ($xml instanceof SimpleXMLElement) {
            $attributes = $xml->attributes();
            foreach ($attributes as $k => $v) {
                if ($v) {
                    $a[$k] = (string) $v;
                }
            }
            $x = $xml;
            $xml = get_object_vars($xml);
        }
        if (is_array($xml)) {
            if (count($xml) === 0) {
                return (string)$x;
            } // for CDATA
            $r = [];
            foreach ($xml as $key => $value) {
                $r[$key] = self::recursiveXMLToArray($value);
            }
            // Attributes
            if (isset($a)) {
                $r['@'] = $a;
            }
            return $r;
        }

        return (string) $xml;
    }

    /**
     * Create a link if the string is a valid URL
     *
     * @param string $string The string to linkify
     * @return string A link to the URL if string is a URL
     */
    public static function linkIfMatch($string)
    {
        if (preg_match('/^[a-z+]+\:\/\/[a-zA-Z0-9$-_.+?&=!*\'()%]+$/', $string)) {
            return "<a style=\"white-space: nowrap\" href=\"$string\">$string</a>";
        }

        return $string;
    }

    /**
     * Simple conversion of HTML to plaintext.
     *
     * @param string $data Input data
     * @param bool $preserveLinks
     * @param int $wordWrap
     * @param array $config
     * @return string
     */
    public static function html2raw($data, $preserveLinks = false, $wordWrap = 0, $config = null)
    {
        $defaultConfig = [
            'PreserveLinks' => false,
            'ReplaceBoldAsterisk' => true,
            'CompressWhitespace' => true,
            'ReplaceImagesWithAlt' => true,
        ];
        if (isset($config)) {
            $config = array_merge($defaultConfig, $config);
        } else {
            $config = $defaultConfig;
        }

        $data = preg_replace("/<style([^A-Za-z0-9>][^>]*)?>.*?<\/style[^>]*>/is", '', $data);
        $data = preg_replace("/<script([^A-Za-z0-9>][^>]*)?>.*?<\/script[^>]*>/is", '', $data);

        if ($config['ReplaceBoldAsterisk']) {
            $data = preg_replace('%<(strong|b)( [^>]*)?>|</(strong|b)>%i', '*', $data);
        }

        // Expand hyperlinks
        if (!$preserveLinks && !$config['PreserveLinks']) {
            $data = preg_replace_callback('/<a[^>]*href\s*=\s*"([^"]*)">(.*?)<\/a>/ui', function ($matches) {
                return Convert::html2raw($matches[2]) . "[$matches[1]]";
            }, $data);
            $data = preg_replace_callback('/<a[^>]*href\s*=\s*([^ ]*)>(.*?)<\/a>/ui', function ($matches) {
                return Convert::html2raw($matches[2]) . "[$matches[1]]";
            }, $data);
        }

        // Replace images with their alt tags
        if ($config['ReplaceImagesWithAlt']) {
            $data = preg_replace('/<img[^>]*alt *= *"([^"]*)"[^>]*>/i', ' \\1 ', $data);
            $data = preg_replace('/<img[^>]*alt *= *([^ ]*)[^>]*>/i', ' \\1 ', $data);
        }

        // Compress whitespace
        if ($config['CompressWhitespace']) {
            $data = preg_replace("/\s+/u", ' ', $data);
        }

        // Parse newline tags
        $data = preg_replace("/\s*<[Hh][1-6]([^A-Za-z0-9>][^>]*)?> */u", "\n\n", $data);
        $data = preg_replace("/\s*<[Pp]([^A-Za-z0-9>][^>]*)?> */u", "\n\n", $data);
        $data = preg_replace("/\s*<[Dd][Ii][Vv]([^A-Za-z0-9>][^>]*)?> */u", "\n\n", $data);
        $data = preg_replace("/\n\n\n+/", "\n\n", $data);

        $data = preg_replace('/<[Bb][Rr]([^A-Za-z0-9>][^>]*)?> */', "\n", $data);
        $data = preg_replace('/<[Tt][Rr]([^A-Za-z0-9>][^>]*)?> */', "\n", $data);
        $data = preg_replace("/<\/[Tt][Dd]([^A-Za-z0-9>][^>]*)?> */", '    ', $data);
        $data = preg_replace('/<\/p>/i', "\n\n", $data);

        // Replace HTML entities
        $data = html_entity_decode($data, ENT_QUOTES, 'UTF-8');
        // Remove all tags (but optionally keep links)

        // strip_tags seemed to be restricting the length of the output
        // arbitrarily. This essentially does the same thing.
        if (!$preserveLinks && !$config['PreserveLinks']) {
            $data = preg_replace('/<\/?[^>]*>/', '', $data);
        } else {
            $data = strip_tags($data, '<a>');
        }

        // Wrap
        if ($wordWrap) {
            $data = wordwrap(trim($data), $wordWrap);
        }
        return trim($data);
    }

    /**
     * There are no real specifications on correctly encoding mailto-links,
     * but this seems to be compatible with most of the user-agents.
     * Does nearly the same as rawurlencode().
     * Please only encode the values, not the whole url, e.g.
     * "mailto:test@test.com?subject=" . Convert::raw2mailto($subject)
     *
     * @param $data string
     * @return string
     * @see http://www.ietf.org/rfc/rfc1738.txt
     */
    public static function raw2mailto($data)
    {
        return str_ireplace(
            ["\n",'?','=',' ','(',')','&','@','"','\'',';'],
            ['%0A','%3F','%3D','%20','%28','%29','%26','%40','%22','%27','%3B'],
            $data
        );
    }

    /**
     * Convert a string (normally a title) to a string suitable for using in
     * urls and other html attributes. Uses {@link URLSegmentFilter}.
     *
     * @param string
     * @return string
     */
    public static function raw2url($title)
    {
        $f = URLSegmentFilter::create();
        return $f->filter($title);
    }

    /**
     * Normalises newline sequences to conform to (an) OS specific format.
     *
     * @param string $data Text containing potentially mixed formats of newline
     * sequences including \r, \r\n, \n, or unicode newline characters
     * @param string $nl The newline sequence to normalise to. Defaults to that
     * specified by the current OS
     * @return string
     */
    public static function nl2os($data, $nl = PHP_EOL)
    {
        return preg_replace('~\R~u', $nl, $data);
    }

    /**
     * Encode a value into a string that can be used as part of a filename.
     * All string data must be UTF-8 encoded.
     *
     * @param mixed $val Value to be encoded
     * @return string
     */
    public static function base64url_encode($val)
    {
        return rtrim(strtr(base64_encode(json_encode($val)), '+/', '~_'), '=');
    }

    /**
     * Decode a value that was encoded with Convert::base64url_encode.
     *
     * @param string $val Value to be decoded
     * @return mixed Original value
     */
    public static function base64url_decode($val)
    {
        return json_decode(
            base64_decode(str_pad(strtr($val, '~_', '+/'), strlen($val) % 4, '=', STR_PAD_RIGHT)),
            true
        );
    }

    /**
     * Converts upper camel case names to lower camel case,
     * with leading upper case characters replaced with lower case.
     * Tries to retain word case.
     *
     * Examples:
     * - ID => id
     * - IDField => idField
     * - iDField => iDField
     *
     * @param $str
     * @return string
     */
    public static function upperCamelToLowerCamel($str)
    {
        $return = null;
        $matches = null;
        if (preg_match('/(^[A-Z]{1,})([A-Z]{1})([a-z]+.*)/', $str, $matches)) {
            // If string has trailing lowercase after more than one leading uppercase characters,
            // match everything but the last leading uppercase character.
            $return = implode('', [
                strtolower($matches[1]),
                $matches[2],
                $matches[3]
            ]);
        } elseif (preg_match('/(^[A-Z]{1})([a-z]+.*)/', $str, $matches)) {
            // If string has trailing lowercase after exactly one leading uppercase characters,
            // match everything but the last leading uppercase character.
            $return = implode('', [
                strtolower($matches[1]),
                $matches[2]
            ]);
        } elseif (preg_match('/^[A-Z]+$/', $str)) {
            // If string has leading uppercase without trailing lowercase,
            // just lowerase the whole thing.
            $return = strtolower($str);
        } else {
            // If string has no leading uppercase, just return.
            $return = $str;
        }

        return $return;
    }

    /**
     * Turn a memory string, such as 512M into an actual number of bytes.
     * Preserves integer values like "1024" or "-1"
     *
     * @param string $memString A memory limit string, such as "64M"
     * @return int
     */
    public static function memstring2bytes($memString)
    {
        // Remove  non-unit characters from the size
        $unit = preg_replace('/[^bkmgtpezy]/i', '', $memString);
        // Remove non-numeric characters from the size
        $size = preg_replace('/[^0-9\.\-]/', '', $memString);

        if ($unit) {
            // Find the position of the unit in the ordered string which is the power
            // of magnitude to multiply a kilobyte by
            return (int)round($size * pow(1024, stripos('bkmgtpezy', $unit[0])));
        }

        return (int)round($size);
    }

    /**
     * @param float $bytes
     * @param int $decimal decimal precision
     * @return string
     */
    public static function bytes2memstring($bytes, $decimal = 0)
    {
        $scales = ['B','K','M','G','T','P','E','Z','Y'];
        // Get scale
        $scale = (int)floor(log($bytes, 1024));
        if (!isset($scales[$scale])) {
            $scale = 2;
        }

        // Size
        $num = round($bytes / pow(1024, $scale), $decimal);
        return $num . $scales[$scale];
    }

    /**
     * Convert slashes in relative or asolute filesystem path. Defaults to DIRECTORY_SEPARATOR
     *
     * @param string $path
     * @param string $separator
     * @param bool $multiple Collapses multiple slashes or not
     * @return string
     */
    public static function slashes($path, $separator = DIRECTORY_SEPARATOR, $multiple = true)
    {
        if ($multiple) {
            return preg_replace('#[/\\\\]+#', $separator, $path);
        }
        return str_replace(['/', '\\'], $separator, $path);
    }
}
