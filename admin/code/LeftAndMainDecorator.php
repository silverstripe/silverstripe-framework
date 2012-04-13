<?php
/**
 * @package framework
 * @subpackage admin
 * @deprecated 3.0 Use {@link LeftAndMainExtension}
 */
abstract class LeftAndMainDecorator extends LeftAndMainExtension {
	
	public function __construct() {
		Deprecation::notice('3.0', 'Use LeftAndMainExtension instead.');
		parent::__construct();
	}
	
}
