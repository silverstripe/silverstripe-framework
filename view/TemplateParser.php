<?php

/**
 * This interface needs to be implemented by any template parser that is used in SSViewer
 *
 * @package framework
 * @subpackage view
 */
interface TemplateParser {
	/**
	 * Compiles some passed template source code into the php code that will execute as per the template source.
	 *
	 * @param  $string The source of the template
	 * @param string $templateName The name of the template, normally the filename the template source was loaded from
	 * @param bool $includeDebuggingComments True is debugging comments should be included in the output
	 * @return mixed|string The php that, when executed (via include or exec) will behave as per the template source
	 */
	public function compileString($string, $templateName = "", $includeDebuggingComments = false);
}