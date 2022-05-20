<?php

namespace SilverStripe\i18n;

use SilverStripe\i18n\TextCollection\i18nTextCollector;

/**
 * Dynamically provide translatable entities for the {@link i18n} logic.
 * This is particularly handy for natural language strings in static variables
 * of a class definition, as the _t() method can only be used in a runtime/instance
 * context. The provideI18nEntities() method enables you to define your own entities
 * with your custom naming, mostly involving either the variable name or the array
 * key. With this in place, you can use a getter method to trigger translation
 * of your values.
 * For any statics containing natural language, never use the static directly -
 * always wrap it in a getter.
 *
 * Classes must be able to be constructed without mandatory arguments, otherwise
 * this interface will have no effect.
 *
 * @uses i18nTextCollector::collectFromEntityProviders()
 */
interface i18nEntityProvider
{

    /**
     * Returns the list of provided translations for this object.
     *
     * Note: Pluralised forms are always returned in array format.
     *
     * Example usage:
     * <code>
     * class MyTestClass implements i18nEntityProvider
     * {
     *   public function provideI18nEntities()
     *   {
     *     $entities = [];
     *     foreach($this->config()->get('my_static_array') as $key => $value) {
     *       $entities["MyTestClass.my_static_array_{$key}"] = $value;
     *     }
     *     $entities["MyTestClass.PLURALS"] = [
     *       'one' => 'A test class',
     *       'other' => '{count} test classes',
     *     ]
     *     return $entities;
     *   }
     * }
     * </code>
     *
     * Example usage in {@link DataObject->provideI18nEntities()}.
     *
     * You can ask textcollector to add the provided entity to a different module.
     * Simply wrap the returned value for any item in an array with the format:
     * [ 'default' => $defaultValue, 'module' => $module ]
     *
     * <code>
     * class MyTestClass implements i18nEntityProvider
     * {
     *   public function provideI18nEntities()
     *   {
     *     $entities = [
     *       'MyOtherModuleClass.MYENTITY' => [
     *         'default' => $value,
     *         'module' => 'myothermodule',
     *       ]
     *     ];
     *   }
     *   return $entities;
     * }
     * </code>
     *
     * @return array Map of keys to default values, which are strings in the default case,
     * and array-form for pluralisations.
     */
    public function provideI18nEntities();
}
