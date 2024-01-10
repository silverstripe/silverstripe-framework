<?php

namespace SilverStripe\View;

use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\ORM\DataList;

class GenericTemplateGlobalProvider implements TemplateGlobalProvider
{

    public static function get_template_global_variables()
    {
        return [
            'ModulePath',
            'List' => 'getDataList'
        ];
    }

    /**
     * Given some pre-defined modules, return the filesystem path of the module.
     * @param string $name Name of module to find path of
     * @return string
     */
    public static function ModulePath($name)
    {
        // BC for a couple of the key modules in the old syntax. Reduces merge brittleness but can
        // be removed before 4.0 stable
        $legacyMapping = [
            'framework' => 'silverstripe/framework',
            'frameworkadmin' => 'silverstripe/admin',
        ];
        if (isset($legacyMapping[$name])) {
            $name = $legacyMapping[$name];
        }

        return ModuleLoader::getModule($name)->getRelativePath();
    }

    /**
     * This allows templates to create a new `DataList` from a known
     * DataObject class name, and call methods such as aggregates.
     *
     * The common use case is for partial caching:
     * <code>
     *    <% cached List(Member).max(LastEdited) %>
     *        loop members here
     *    <% end_cached %>
     * </code>
     *
     * @template T of DataObject
     * @param class-string<T> $className
     * @return DataList<T>
     */
    public static function getDataList($className)
    {
        return DataList::create($className);
    }
}
