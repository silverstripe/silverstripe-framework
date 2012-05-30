<?php
class GenericTemplateGlobalProvider implements TemplateGlobalProvider {

	public static function get_template_global_variables() {
		return array(
			'ModulePath',
			'List' => 'getDataList'
		);
	}

	/**
	 * @var array Module paths
	 */
	public static $modules = array(
		'framework' => FRAMEWORK_DIR,
		'frameworkadmin' => FRAMEWORK_ADMIN_DIR,
		'thirdparty' => THIRDPARTY_DIR,
		'assets' => ASSETS_DIR
	);

	/**
	 * Given some pre-defined modules, return the filesystem path of the module.
	 * @param string $name Name of module to find path of
	 * @return string
	 */
	public static function ModulePath($name) {
		if(isset(self::$modules[$name])) {
			return self::$modules[$name];
		} else {
			throw new InvalidArgumentException(sprintf('%s is not a supported argument. Possible values: %s', $name, implode(', ', self::$modules)));
		}
	}

	/**
	 * This allows templates to create a new `DataList` from a known
	 * DataObject class name, and call methods such as aggregates.
	 *
	 * The common use case is for partial caching:
	 * <code>
	 *	<% cached List(Member).max(LastEdited) %>
	 *		loop members here
	 *	<% end_cached %>
	 * </code>
	 *
	 * @return DataList
	 */
	public static function getDataList($className) {
		$list = new DataList($className);
		$list->setDataModel(DataModel::inst());
		return $list;
	}

}

