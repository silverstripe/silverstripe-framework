<?php

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\i18n\i18n;
use SilverStripe\Core\Manifest\ModuleManifest;
use SilverStripe\SiteConfig\SiteConfig;

///////////////////////////////////////////////////////////////////////////////
// HELPER FUNCTIONS

/**
 * Creates a class instance by the "singleton" design pattern.
 * It will always return the same instance for this class,
 * which can be used for performance reasons and as a simple
 * way to access instance methods which don't rely on instance
 * data (e.g. the custom SilverStripe static handling).
 *
 * @param string $className
 * @return mixed
 */
function singleton($className)
{
    if ($className === Config::class) {
        throw new InvalidArgumentException("Don't pass Config to singleton()");
    }
    if (!isset($className)) {
        throw new InvalidArgumentException("singleton() Called without a class");
    }
    if (!is_string($className)) {
        throw new InvalidArgumentException(
            "singleton() passed bad class_name: " . var_export($className, true)
        );
    }
    return Injector::inst()->get($className);
}

function project()
{
    return ModuleManifest::config()->get('project');
}

/**
 * This is the main translator function. Returns the string defined by $entity according to the
 * currently set locale.
 *
 * Also supports pluralisation of strings. Pass in a `count` argument, as well as a
 * default value with `|` pipe-delimited options for each plural form.
 *
 * @param string $entity Entity that identifies the string. It must be in the form
 * "Namespace.Entity" where Namespace will be usually the class name where this
 * string is used and Entity identifies the string inside the namespace.
 * @param mixed $arg Additional arguments are parsed as such:
 *  - Next string argument is a default. Pass in a `|` pipe-delimeted value with `{count}`
 *    to do pluralisation.
 *  - Any other string argument after default is context for i18nTextCollector
 *  - Any array argument in any order is an injection parameter list. Pass in a `count`
 *    injection parameter to pluralise.
 * @return string
 */
function _t($entity, $arg = null)
{
    // Pass args directly to handle deprecation
    return call_user_func_array([i18n::class, '_t'], func_get_args());
}

/**
 * ExpressionLanguage service getter function. Substitute for '%$' in spec expression 
 * based syntax.
 *
 * @param string $service
 * @return object
 */
function srv($service)
{
    return Injector::inst()->get($service);
}

/**
 * ExpressionLanguage environment variable getter function.
 *
 * @param string $name
 * @return string|false
 */
function env($name)
{
    return Environment::getEnv($name);
}

/**
 * ExpressionLanguage current site config getter function.
 *
 * @param string $key
 * @return string
 */
function site($key)
{
    return SiteConfig::current_site_config()->{$key};
}
