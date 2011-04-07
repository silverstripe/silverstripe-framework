<?php
/**
 * Interface that is implemented by any classes that want to expose a method that can be called in a template.
 * SSViewer_BasicIteratorSupport is an example of this. See also @TemplateGlobalProvider
 * @package sapphire
 * @subpackage core
 */
interface TemplateIteratorProvider {
	/**
	 * @abstract
	 * @return array Returns an array of strings of the method names of methods on the call that should be exposed
	 * as global variables in the templates. A map (template-variable-name => method-name) can optionally be supplied
	 * if the template variable name is different from the name of the method to call. The case of the first character
	 * in the method name called from the template does not matter, although names specified in the map should
	 * correspond to the actual method name in the relevant class.
	 * Note that the template renderer must be able to call these methods statically.
	 */
	public static function getExposedVariables();

	/**
	 * Set the current iterator properties - where we are on the iterator.
	 * @abstract
	 * @param int $pos position in iterator
	 * @param int $totalItems total number of items
	 */
	public function iteratorProperties($pos, $totalItems);
}

?>