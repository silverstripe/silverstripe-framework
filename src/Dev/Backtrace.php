<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Configurable;

/**
 * Backtrace helper
 */
class Backtrace
{
    use Configurable;

    /**
     * @var array Replaces all arguments with a '<filtered>' string,
     * mostly for security reasons. Use string values for global functions,
     * and array notation for class methods.
     * PHP's debug_backtrace() doesn't allow to inspect the argument names,
     * so all arguments of the provided functions will be filtered out.
     */
    private static $ignore_function_args = [
        'mysql_connect',
        'mssql_connect',
        'pg_connect',
        ['PDO', '__construct'],
        ['mysqli', 'mysqli'],
        ['mysqli', 'select_db'],
        ['mysqli', 'real_connect'],
        ['SilverStripe\\ORM\\DB', 'connect'],
        ['SilverStripe\\Security\\Security', 'check_default_admin'],
        ['SilverStripe\\Security\\Security', 'encrypt_password'],
        ['SilverStripe\\Security\\Security', 'setDefaultAdmin'],
        ['SilverStripe\\ORM\\DB', 'createDatabase'],
        ['SilverStripe\\Security\\Member', 'checkPassword'],
        ['SilverStripe\\Security\\Member', 'changePassword'],
        ['SilverStripe\\Security\\MemberPassword', 'checkPassword'],
        ['SilverStripe\\Security\\PasswordValidator', 'validate'],
        ['SilverStripe\\Security\\PasswordEncryptor_PHPHash', 'encrypt'],
        ['SilverStripe\\Security\\PasswordEncryptor_PHPHash', 'salt'],
        ['SilverStripe\\Security\\PasswordEncryptor_LegacyPHPHash', 'encrypt'],
        ['SilverStripe\\Security\\PasswordEncryptor_LegacyPHPHash', 'salt'],
        ['SilverStripe\\Security\\PasswordEncryptor_MySQLPassword', 'encrypt'],
        ['SilverStripe\\Security\\PasswordEncryptor_MySQLPassword', 'salt'],
        ['SilverStripe\\Security\\PasswordEncryptor_MySQLOldPassword', 'encrypt'],
        ['SilverStripe\\Security\\PasswordEncryptor_MySQLOldPassword', 'salt'],
        ['SilverStripe\\Security\\PasswordEncryptor_Blowfish', 'encrypt'],
        ['SilverStripe\\Security\\PasswordEncryptor_Blowfish', 'salt'],
        ['*', 'updateValidatePassword'],
    ];

    /**
     * Return debug_backtrace() results with functions filtered
     * specific to the debugging system, and not the trace.
     *
     * @param null|array $ignoredFunctions If an array, filter these functions out of the trace
     * @return array
     */
    public static function filtered_backtrace($ignoredFunctions = null)
    {
        return self::filter_backtrace(debug_backtrace(), $ignoredFunctions);
    }

    /**
     * Filter a backtrace so that it doesn't show the calls to the
     * debugging system, which is useless information.
     *
     * @param array $bt Backtrace to filter
     * @param null|array $ignoredFunctions List of extra functions to filter out
     * @return array
     */
    public static function filter_backtrace($bt, $ignoredFunctions = null)
    {
        $defaultIgnoredFunctions = [
            'SilverStripe\\Logging\\Log::log',
            'SilverStripe\\Dev\\Backtrace::backtrace',
            'SilverStripe\\Dev\\Backtrace::filtered_backtrace',
            'Zend_Log_Writer_Abstract->write',
            'Zend_Log->log',
            'Zend_Log->__call',
            'Zend_Log->err',
            'SilverStripe\\Dev\\DebugView->writeTrace',
            'SilverStripe\\Dev\\CliDebugView->writeTrace',
            'SilverStripe\\Dev\\Debug::emailError',
            'SilverStripe\\Dev\\Debug::warningHandler',
            'SilverStripe\\Dev\\Debug::noticeHandler',
            'SilverStripe\\Dev\\Debug::fatalHandler',
            'errorHandler',
            'SilverStripe\\Dev\\Debug::showError',
            'SilverStripe\\Dev\\Debug::backtrace',
            'exceptionHandler'
        ];

        if ($ignoredFunctions) {
            foreach ($ignoredFunctions as $ignoredFunction) {
                $defaultIgnoredFunctions[] = $ignoredFunction;
            }
        }

        while ($bt && in_array(self::full_func_name($bt[0]), $defaultIgnoredFunctions)) {
            array_shift($bt);
        }

        $ignoredArgs = static::config()->get('ignore_function_args');

        // Filter out arguments
        foreach ($bt as $i => $frame) {
            $match = false;
            if (!empty($bt[$i]['class'])) {
                foreach ($ignoredArgs as $fnSpec) {
                    if (is_array($fnSpec) &&
                        ('*' == $fnSpec[0] || $bt[$i]['class'] == $fnSpec[0]) &&
                        $bt[$i]['function'] == $fnSpec[1]
                    ) {
                        $match = true;
                    }
                }
            } else {
                if (in_array($bt[$i]['function'], $ignoredArgs)) {
                    $match = true;
                }
            }
            if ($match) {
                foreach ($bt[$i]['args'] as $j => $arg) {
                    $bt[$i]['args'][$j] = '<filtered>';
                }
            }
        }

        return $bt;
    }

    /**
     * Render or return a backtrace from the given scope.
     *
     * @param mixed $returnVal
     * @param bool $ignoreAjax
     * @param array $ignoredFunctions
     * @return mixed
     */
    public static function backtrace($returnVal = false, $ignoreAjax = false, $ignoredFunctions = null)
    {
        $plainText = Director::is_cli() || (Director::is_ajax() && !$ignoreAjax);
        $result = self::get_rendered_backtrace(debug_backtrace(), $plainText, $ignoredFunctions);
        if ($returnVal) {
            return $result;
        } else {
            echo $result;
            return null;
        }
    }

    /**
     * Return the full function name.  If showArgs is set to true, a string representation of the arguments will be
     * shown
     *
     * @param Object $item
     * @param bool $showArgs
     * @param int $argCharLimit
     * @return string
     */
    public static function full_func_name($item, $showArgs = false, $argCharLimit = 10000)
    {
        $funcName = '';
        if (isset($item['class'])) {
            $funcName .= $item['class'];
        }
        if (isset($item['type'])) {
            $funcName .= $item['type'];
        }
        if (isset($item['function'])) {
            $funcName .= $item['function'];
        }

        if ($showArgs && isset($item['args'])) {
            $args = [];
            foreach ($item['args'] as $arg) {
                if (!is_object($arg) || method_exists($arg, '__toString')) {
                    $sarg = is_array($arg) ? 'Array' : strval($arg);
                    $args[] = (strlen($sarg) > $argCharLimit) ? substr($sarg, 0, $argCharLimit) . '...' : $sarg;
                } else {
                    $args[] = get_class($arg);
                }
            }

            $funcName .= "(" . implode(", ", $args) . ")";
        }

        return $funcName;
    }

    /**
     * Render a backtrace array into an appropriate plain-text or HTML string.
     *
     * @param array $bt The trace array, as returned by debug_backtrace() or Exception::getTrace()
     * @param boolean $plainText Set to false for HTML output, or true for plain-text output
     * @param array $ignoredFunctions List of functions that should be ignored. If not set, a default is provided
     * @return string The rendered backtrace
     */
    public static function get_rendered_backtrace($bt, $plainText = false, $ignoredFunctions = null)
    {
        if (empty($bt)) {
            return '';
        }
        $bt = self::filter_backtrace($bt, $ignoredFunctions);
        $result = ($plainText) ? '' : '<ul>';
        foreach ($bt as $item) {
            if ($plainText) {
                $result .= self::full_func_name($item, true) . "\n";
                if (isset($item['line']) && isset($item['file'])) {
                    $result .= basename($item['file']) . ":$item[line]\n";
                }
                $result .= "\n";
            } else {
                if ($item['function'] == 'user_error') {
                    $name = $item['args'][0];
                } else {
                    $name = self::full_func_name($item, true);
                }
                $result .= "<li><b>" . htmlentities($name, ENT_COMPAT, 'UTF-8') . "</b>\n<br />\n";
                $result .=  isset($item['file']) ? htmlentities(basename($item['file']), ENT_COMPAT, 'UTF-8') : '';
                $result .= isset($item['line']) ? ":$item[line]" : '';
                $result .= "</li>\n";
            }
        }
        if (!$plainText) {
            $result .= '</ul>';
        }
        return $result;
    }
}
