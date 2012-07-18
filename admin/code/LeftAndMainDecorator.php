<?php
/**
 * @package framework
 * @subpackage admin
 * @deprecated 3.0 Use {@link LeftAndMainExtension}
 */
abstract class LeftAndMainDecorator extends LeftAndMainExtension {
	
	public function __construct() {
		Deprecation::notice('3.0', 'Use LeftAndMainExtension instead.', Deprecation::SCOPE_CLASS);
		parent::__construct();
	}
	
}
