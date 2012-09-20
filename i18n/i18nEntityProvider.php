<?php
/**
 * Dynamically provide translatable entites for the {@link i18n} logic.
 * This is particularly handy for natural language strings in static variables
 * of a class definition, as the _t() method can only be used in a runtime/instance
 * context. The provideI18nEntities() method enables you to define your own entities
 * with your custom naming, mostly involving either the variable name or the array
 * key. With this in place, you can use a getter method to trigger translation
 * of your values.
 * For any statics containing natural language, never use the static directly -
 * always wrap it in a getter.
 * 
 * @package framework
 * @subpackage i18n
 * @uses i18nTextCollector->collectFromEntityProviders()
 */
interface i18nEntityProvider {
	
	/**
	 * Example usage:
	 * <code>
	 * class MyTestClass implements i18nEntityProvider {
	 * function provideI18nEntities() {
	 * 	$entities = array();
	 * 	foreach($this->stat('my_static_array) as $key => $value) {
	 * 		$entities["MyTestClass.my_static_array_{$key}"] = array(
	 * 			$value,
	 * 			
	 * 			'My context description'
	 * 		);
	 * 	}
	 * 	return $entities;
	 * }
	 * 
	 * public static function my_static_array() {
	 * 	$t_my_static_array = array();
	 * 	foreach(self::$my_static_array as $k => $v) {
	 * 		$t_my_static_array[$k] = _t("MyTestClass.my_static_array_{$key}", $v);
	 * 	}
	 * 	return $t_my_static_array;
	 * }
	 * }
	 * </code>
	 * 
	 * Example usage in {@link DataObject->provideI18nEntities()}.
	 * 
	 * You can ask textcollector to add the provided entity to a different module
	 * than the class is contained in by adding a 4th argument to the array:
	 * <code>
	 * class MyTestClass implements i18nEntityProvider {
	 * function provideI18nEntities() {
	 * 	$entities = array();
	 * 		$entities["MyOtherModuleClass.MYENTITY"] = array(
	 * 			$value,
	 * 			
	 * 			'My context description',
	 * 			'myothermodule'
	 * 		);
	 * 	}
	 * 	return $entities;
	 * }
	 * </code>
	 * 
	 * @return array All entites in an associative array, with
	 * entity name as the key, and a numerical array of pseudo-arguments
	 * for _t() as a value.
	 */
	public function provideI18nEntities();
}
