<?php

class GenericTemplateGlobalProvider implements TemplateGlobalProvider {
	/**
	 * @return array Returns an array of strings of the method names of methods on the call that should be exposed
	 * as global variables in the templates.
	 */
	public static function get_template_global_variables() {
		return array(
			'FrameworkDir',
			'FrameworkAdminDir',
			'ThirdpartyDir',
			'AssetsDir',
		);
	}

	public static function FrameworkDir() {
		return FRAMEWORK_DIR;
	}

	public static function FrameworkAdminDir() {
		return FRAMEWORK_ADMIN_DIR;
	}

	public static function ThirdpartyDir() {
		return THIRDPARTY_DIR;
	}

	public static function AssetsDir() {
		return ASSETS_DIR;
	}
}

