<?php

namespace SilverStripe\Dev;

use BadMethodCallException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\InjectorLoader;
use SilverStripe\Core\Manifest\Module;

/**
 * Handles raising an notice when accessing a deprecated method
 *
 * A pattern used in SilverStripe when deprecating a method is to add something like
 *   user_error('This method is deprecated', E_USER_NOTICE);
 * to the method
 *
 * However sometimes we want to mark that a method will be deprecated in some future version and shouldn't be used in
 * new code, but not forbid in the current version - for instance when that method is still heavily used in framework
 * or cms.
 *
 * This class abstracts the above pattern and adds a way to do that.
 *
 * Each call to notice passes a version that the notice will be valid from. Additionally this class has a notion of the
 * version it should use when deciding whether to raise the notice. If that version is equal to or greater than the
 * notices version (and SilverStripe is in dev mode) a deprecation message will be raised.
 *
 * Normally the checking version will be the release version of SilverStripe, but a developer can choose to set it to a
 * future version, to see how their code will behave in future versions.
 *
 * Modules can also set the version for calls they make - either setting it to a future version in order to ensure
 * forwards compatibility or setting it backwards if a module has not yet removed references to deprecated methods.
 *
 * When set per-module, only direct calls to deprecated methods from those modules are considered - if the module
 * calls a non-module method which then calls a deprecated method, that call will use the global check version, not
 * the module specific check version.
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
     * @deprecated 4.12.0 Use $is_enabled instead
     */
    protected static $enabled = null;

    /**
     * Must be configured outside of the config API, as deprecation API
     * must be available before this to avoid infinite loops.
     *
     * This will be overriden by the SS_DEPRECATION_ENABLED environment if present
     *
     * @internal - Marked as internal so this and other private static's are not treated as config
     */
    private static bool $is_enabled = false;

    /**
     * @var array
     * @deprecated 4.12.0 Will be removed without equivalent functionality to replace it
     */
    protected static $module_version_overrides = [];

    /**
     * @internal
     */
    private static bool $inside_notice = false;

    /**
     * @var array
     * @deprecated 4.12.0 Will be removed without equivalent functionality to replace it
     */
    public static $notice_level = E_USER_DEPRECATED;

    /**
     * Buffer of user_errors to be raised when enabled() is called
     *
     * This is used when setting deprecated config via yaml, before Deprecation::enable() has been called in _config.php
     * Deprecated config set via yaml will only be shown in the browser when using ?flush=1
     * It will not show in CLI when running dev/build flush=1
     *
     * @internal
     */
    private static array $user_error_message_buffer = [];

    public static function enable(): void
    {
        static::$is_enabled = true;
        foreach (self::$user_error_message_buffer as $message) {
            user_error($message, E_USER_DEPRECATED);
        }
        self::$user_error_message_buffer = [];
    }

    public static function disable(): void
    {
        static::$is_enabled = false;
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
        $called = $backtrace ? $backtrace[$level] : [];

        if (isset($called['class'])) {
            return $called['class'] . $called['type'] . $called['function'];
        }
        return $called['function'] ?? '';
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

    private static function get_is_enabled(): bool
    {
        if (!Director::isDev()) {
            return false;
        }
        return static::$is_enabled || Environment::getEnv('SS_DEPRECATION_ENABLED');
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
     * Raise a notice indicating the method is deprecated if the version passed as the second argument is greater
     * than or equal to the check version set via ::notification_version
     *
     * @param string $atVersion The version at which this notice should start being raised
     * @param string $string The notice to raise
     * @param int $scope Notice relates to the method or class context its called in.
     */
    public static function notice($atVersion, $string = '', $scope = Deprecation::SCOPE_METHOD)
    {
        if (static::$inside_notice) {
            return;
        }
        static::$inside_notice = true;
        // try block needs to wrap all code in case anything inside the try block
        // calls something else that calls Deprecation::notice()
        try {
            if ($scope === self::SCOPE_CONFIG) {
                if (self::get_is_enabled()) {
                    user_error($string, E_USER_DEPRECATED);
                } else {
                    self::$user_error_message_buffer[] = $string;
                }
            } else {
                if (!self::get_is_enabled()) {
                    // Do not add to self::$user_error_message_buffer, as the backtrace is too expensive
                    return;
                }
    
                // Getting a backtrace is slow, so we only do it if we need it
                $backtrace = null;
    
                // Get the calling scope
                if ($scope == Deprecation::SCOPE_METHOD) {
                    $backtrace = debug_backtrace(0);
                    $caller = self::get_called_method_from_trace($backtrace);
                } elseif ($scope == Deprecation::SCOPE_CLASS) {
                    $backtrace = debug_backtrace(0);
                    $caller = isset($backtrace[1]['class']) ? $backtrace[1]['class'] : '(unknown)';
                } else {
                    $caller = false;
                }
    
                // Then raise the notice
                if (substr($string, -1) != '.') {
                    $string .= ".";
                }

                $string .= " Called from " . self::get_called_method_from_trace($backtrace, 2) . '.';

                if ($caller) {
                    user_error($caller . ' is deprecated.' . ($string ? ' ' . $string : ''), E_USER_DEPRECATED);
                } else {
                    user_error($string, E_USER_DEPRECATED);
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
            static::$inside_notice = false;
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
}
