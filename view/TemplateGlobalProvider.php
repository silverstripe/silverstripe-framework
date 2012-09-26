<?php
/**
 * Interface that is implemented by any classes that want to expose a method that can be called in any 
 * scope in a template.
 *
 * Director::AbsoluteBaseURL is an example of this.
 *
 * @package framework
 * @subpackage core
 */
interface TemplateGlobalProvider {

	/**
	 * Called by SSViewer to get a list of global variables to expose to the template, the static method to call on
	 * this class to get the value for those variables, and the class to use for casting the returned value for use
	 * in a template
	 *
	 * If the method to call is not included for a particular template variable, a method named the same as the
	 * template variable will be called
	 *
	 * If the casting class is not specified for a particular template variable, ViewableData::$default_cast is used
	 *
	 * The first letter of the template variable is case-insensitive. However the method name is always case sensitive.
	 *
	 * @abstract
	 * @return array Returns an array of items. Each key => value pair is one of three forms:
	 *  - template name (no key)
	 *  - template name => method name
	 *  - template name => array(), where the array can contain these key => value pairs
	 *     - "method" => method name
	 *     - "casting" => casting class to use (i.e., Varchar, HTMLText, etc)
	 */
	public static function get_template_global_variables();
}

