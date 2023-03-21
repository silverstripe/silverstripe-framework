<?php

namespace SilverStripe\Dev;

use BadMethodCallException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\InjectionCreator;
use SilverStripe\Core\Injector\InjectorLoader;
use SilverStripe\Core\Manifest\Module;

/**
 * Handles raising an notice when accessing a deprecated method, class, configuration, or behaviour.
 *
 * Sometimes we want to mark that a method will be deprecated in some future version and shouldn't be used in
 * new code, but not forbid in the current version - for instance when that method is still heavily used in framework
 * or cms.
 *
 * See https://docs.silverstripe.org/en/contributing/release_process/#deprecation
 */
class Deprecation
{
    const SCOPE_METHOD = 1;
    const SCOPE_CLASS = 2;
    const SCOPE_GLOBAL = 4;
    const SCOPE_CONFIG = 8;

    /**
     * @var string
     * @deprecated 4.12.0 Will be removed without equivalent functionality to replace it
     */
    protected static $version;

    /**
     * Override whether deprecation is enabled. If null, then fallback to
     * SS_DEPRECATION_ENABLED, and then true if not defined.
     *
     * Deprecation is only available on dev.
     *
     * Must be configured outside of the config API, as deprecation API
     * must be available before this to avoid infinite loops.
     *
     * @var boolean|null
     * @deprecated 4.12.0 Use $currentlyEnabled instead
     */
    protected static $enabled = null;

    /**
     * @var array
     * @deprecated 4.12.0 Will be removed without equivalent functionality to replace it
     */
    protected static $module_version_overrides = [];

    /**
     * @var array
     * @deprecated 4.12.0 Will be removed without equivalent functionality to replace it
     */
    public static $notice_level = E_USER_DEPRECATED;

    /**
     * Must be configured outside of the config API, as deprecation API
     * must be available before this to avoid infinite loops.
     *
     * This will be overriden by the SS_DEPRECATION_ENABLED environment variable if present
     *
     * @internal - Marked as internal so this and other private static's are not treated as config
     */
    private static bool $currentlyEnabled = false;

    /**
     * @internal
     */
    private static bool $shouldShowForHttp = false;

    /**
     * @internal
     */
    private static bool $shouldShowForCli = true;

    /**
     * @internal
     */
    private static bool $insideNotice = false;

    /**
     * @internal
     */
    private static bool $insideWithNoReplacement = false;

    /**
     * @internal
     */
    private static bool $isTriggeringError = false;

    /**
     * Buffer of user_errors to be raised
     *
     * @internal
     */
    private static array $userErrorMessageBuffer = [];

    /**
     * @internal
     */
    private static bool $haveSetShutdownFunction = false;

    /**
     * @internal
     */
    private static bool $showNoReplacementNotices = false;

    /**
     * Enable throwing deprecation warnings. By default, this excludes warnings for
     * deprecated code which is called by core Silverstripe modules.
     *
     * This will be overriden by the SS_DEPRECATION_ENABLED environment variable if present.
     *
     * @param bool $showNoReplacementNotices If true, deprecation warnings will also be thrown
     * for deprecated code which is called by core Silverstripe modules.
     */
    public static function enable(bool $showNoReplacementNotices = false): void
    {
        static::$currentlyEnabled = true;
        static::$showNoReplacementNotices = $showNoReplacementNotices;
    }

    /**
     * Disable throwing deprecation warnings.
     *
     * This will be overriden by the SS_DEPRECATION_ENABLED environment variable if present.
     */
    public static function disable(): void
    {
        static::$currentlyEnabled = false;
    }

    /**
     * Used to wrap deprecated methods and deprecated config get()/set() that will be removed
     * in the next major version with no replacement. This is done to surpress deprecation notices
     * by for calls from the vendor dir to deprecated code that projects have no ability to change
     *
     * @return mixed
     */
    public static function withNoReplacement(callable $func)
    {
        if (self::$insideWithNoReplacement) {
            return $func();
        }
        self::$insideWithNoReplacement = true;
        try {
            return $func();
        } finally {
            self::$insideWithNoReplacement = false;
        }
    }

    /**
     * This method is no longer used
     *
     * @static
     * @param $ver string -
     *     A php standard version string, see http://php.net/manual/en/function.version-compare.php for details.
     * @param null $forModule string -
     *    The name of a module. The passed version will be used as the check value for
     *    calls directly from this module rather than the global value
     * @return void
     * @deprecated 4.12.0 Use enable() instead
     */
    public static function notification_version($ver, $forModule = null)
    {
        static::notice('4.12.0', 'Use enable() instead');
        // noop
    }

    /**
     * This method is no longer used
     *
     * @param array $backtrace A backtrace as returned from debug_backtrace
     * @return Module The module being called
     * @deprecated 4.12.0 Will be removed without equivalent functionality to replace it
     */
    protected static function get_calling_module_from_trace($backtrace)
    {
        static::notice('4.12.0', 'Will be removed without equivalent functionality to replace it');
        // noop
    }

    /**
     * Given a backtrace, get the method name from the immediate parent caller (the caller of #notice)
     *
     * @static
     * @param $backtrace array - a backtrace as returned from debug_backtrace
     * @param $level - 1 (default) will return immediate caller, 2 will return caller's caller, etc.
     * @return string - the name of the method
     */
    protected static function get_called_method_from_trace($backtrace, $level = 1)
    {
        $level = (int)$level;
        if (!$level) {
            $level = 1;
        }
        $newLevel = $level;
        // handle closures inside withNoReplacement()
        if (self::$insideWithNoReplacement
            && substr($backtrace[$newLevel]['function'], -strlen('{closure}')) === '{closure}'
        ) {
            $newLevel = $newLevel + 2;
        }
        // handle call_user_func
        if ($level === 4 && strpos($backtrace[2]['function'] ?? '', 'call_user_func') !== false) {
            $newLevel = 5;
        } elseif (strpos($backtrace[$level]['function'] ?? '', 'call_user_func') !== false) {
            $newLevel = $level + 1;
        }
        // handle InjectionCreator
        if ($level == 4 && ($backtrace[$newLevel]['class'] ?? '') === InjectionCreator::class) {
            $newLevel = $newLevel + 4;
        }
        $called = $backtrace[$newLevel] ?? [];
        return ($called['class'] ?? '') . ($called['type'] ?? '') . ($called['function'] ?? '');
    }

    /**
     * This method is no longer used
     *
     * @return bool
     * @deprecated 4.12.0 Will be removed without equivalent functionality to replace it
     */
    public static function get_enabled()
    {
        static::notice('4.12.0', 'Will be removed without equivalent functionality to replace it');
        // noop
    }

    public static function isEnabled(): bool
    {
        if (!Director::isDev()) {
            return false;
        }
        if (Environment::hasEnv('SS_DEPRECATION_ENABLED')) {
            $envVar = Environment::getEnv('SS_DEPRECATION_ENABLED');
            return self::varAsBoolean($envVar);
        }
        return static::$currentlyEnabled;
    }

    /**
     * This method is no longer used
     *
     * @param bool $enabled
     * @deprecated 4.12.0 Use enable() instead
     */
    public static function set_enabled($enabled)
    {
        static::notice('4.12.0', 'Use enable() instead');
        // noop
    }

    /**
     * If true, any E_USER_DEPRECATED errors should be treated as coming
     * directly from this class.
     */
    public static function isTriggeringError(): bool
    {
        return self::$isTriggeringError;
    }

    /**
     * Determine whether deprecation warnings should be included in HTTP responses.
     * Does not affect logging.
     *
     * This will be overriden by the SS_DEPRECATION_SHOW_HTTP environment variable if present.
     */
    public static function setShouldShowForHttp(bool $value): void
    {
        self::$shouldShowForHttp = $value;
    }

    /**
     * Determine whether deprecation warnings should be included in CLI responses.
     * Does not affect logging.
     *
     * This will be overriden by the SS_DEPRECATION_SHOW_CLI environment variable if present.
     */
    public static function setShouldShowForCli(bool $value): void
    {
        self::$shouldShowForCli = $value;
    }

    /**
     * If true, deprecation warnings should be included in HTTP responses.
     * Does not affect logging.
     */
    public static function shouldShowForHttp(): bool
    {
        if (Environment::hasEnv('SS_DEPRECATION_SHOW_HTTP')) {
            $envVar = Environment::getEnv('SS_DEPRECATION_SHOW_HTTP');
            return self::varAsBoolean($envVar);
        }
        return self::$shouldShowForHttp;
    }

    /**
     * If true, deprecation warnings should be included in CLI responses.
     * Does not affect logging.
     */
    public static function shouldShowForCli(): bool
    {
        if (Environment::hasEnv('SS_DEPRECATION_SHOW_CLI')) {
            $envVar = Environment::getEnv('SS_DEPRECATION_SHOW_CLI');
            return self::varAsBoolean($envVar);
        }
        return self::$shouldShowForCli;
    }

    public static function outputNotices(): void
    {
        if (!self::isEnabled()) {
            return;
        }

        $count = 0;
        $origCount = count(self::$userErrorMessageBuffer);
        while ($origCount > $count) {
            $count++;
            $arr = array_shift(self::$userErrorMessageBuffer);
            $message = $arr['message'];
            $calledInsideWithNoReplacement = $arr['calledInsideWithNoReplacement'];
            if ($calledInsideWithNoReplacement && !self::$showNoReplacementNotices) {
                continue;
            }
            self::$isTriggeringError = true;
            user_error($message, E_USER_DEPRECATED);
            self::$isTriggeringError = false;
        }
        // Make absolutely sure the buffer is empty - array_shift seems to leave an item in the array
        // if we're not using numeric keys.
        self::$userErrorMessageBuffer = [];
    }

    /**
     * Raise a notice indicating the method is deprecated if the version passed as the second argument is greater
     * than or equal to the check version set via ::notification_version
     *
     * @param string $atVersion The version at which this notice should start being raised
     * @param string $string The notice to raise
     * @param int $scope Notice relates to the method or class context its called in.
     */
    public static function notice($atVersion, $string = '', $scope = Deprecation::SCOPE_METHOD)
    {
        if (static::$insideNotice) {
            return;
        }
        static::$insideNotice = true;
        // try block needs to wrap all code in case anything inside the try block
        // calls something else that calls Deprecation::notice()
        try {
            $data = null;
            if ($scope === self::SCOPE_CONFIG) {
                // Deprecated config set via yaml will only be shown in the browser when using ?flush=1
                // It will not show in CLI when running dev/build flush=1
                $data = [
                    'key' => sha1($string),
                    'message' => $string,
                    'calledInsideWithNoReplacement' => self::$insideWithNoReplacement
                ];
            } else {
                if (!self::isEnabled()) {
                    // Do not add to self::$userErrorMessageBuffer, as the backtrace is too expensive
                    return;
                }

                // Getting a backtrace is slow, so we only do it if we need it
                $backtrace = null;

                // Get the calling scope
                if ($scope == Deprecation::SCOPE_METHOD) {
                    $backtrace = debug_backtrace(0);
                    $caller = self::get_called_method_from_trace($backtrace, 1);
                } elseif ($scope == Deprecation::SCOPE_CLASS) {
                    $backtrace = debug_backtrace(0);
                    $caller = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : '(unknown)';
                } else {
                    $caller = false;
                }

                if (substr($string, -1) != '.') {
                    $string .= ".";
                }

                $level = self::$insideWithNoReplacement ? 4 : 2;
                $string .= " Called from " . self::get_called_method_from_trace($backtrace, $level) . '.';

                if ($caller) {
                    $string = $caller . ' is deprecated.' . ($string ? ' ' . $string : '');
                }
                $data = [
                    'key' => sha1($string),
                    'message' => $string,
                    'calledInsideWithNoReplacement' => self::$insideWithNoReplacement
                ];
            }
            if ($data && !array_key_exists($data['key'], self::$userErrorMessageBuffer)) {
                // Store de-duplicated data in a buffer to be outputted when outputNotices() is called
                self::$userErrorMessageBuffer[$data['key']] = $data;

                // Use a shutdown function rather than immediately calling user_error() so that notices
                // do not interfere with setting session varibles i.e. headers already sent error
                // it also means the deprecation notices appear below all phpunit output in CI
                // which is far nicer than having it spliced between phpunit output
                if (!self::$haveSetShutdownFunction && self::isEnabled()) {
                    register_shutdown_function(function () {
                        self::outputNotices();
                    });
                    self::$haveSetShutdownFunction = true;
                }
            }
        } catch (BadMethodCallException $e) {
            if ($e->getMessage() === InjectorLoader::NO_MANIFESTS_AVAILABLE) {
                // noop
                // this can happen when calling Deprecation::notice() before manifests are available, i.e.
                // some of the code involved in creating the manifests calls Deprecation::notice()
            } else {
                throw $e;
            }
        } finally {
            static::$insideNotice = false;
        }
    }

    /**
     * This method is no longer used
     *
     * @return array Opaque array that should only be used to pass to {@see Deprecation::restore_settings()}
     * @deprecated 4.12.0 Will be removed without equivalent functionality to replace it
     */
    public static function dump_settings()
    {
        static::notice('4.12.0', 'Will be removed without equivalent functionality to replace it');
        // noop
    }

    /**
     * This method is no longer used
     *
     * @param $settings array An array as returned by {@see Deprecation::dump_settings()}
     * @deprecated 4.12.0 Will be removed without equivalent functionality to replace it
     */
    public static function restore_settings($settings)
    {
        static::notice('4.12.0', 'Will be removed without equivalent functionality to replace it');
        // noop
    }

    private static function varAsBoolean($val): bool
    {
        if (is_string($val)) {
            $truthyStrings = [
                'on',
                'true',
                '1',
            ];

            if (in_array(strtolower($val), $truthyStrings, true)) {
                return true;
            }

            $falsyStrings = [
                'off',
                'false',
                '0',
            ];

            if (in_array(strtolower($val), $falsyStrings, true)) {
                return false;
            }
        }

        return (bool) $val;
    }
}
