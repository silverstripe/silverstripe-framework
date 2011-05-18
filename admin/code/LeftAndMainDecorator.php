<?php
/**
 * @package    sapphire
 * @subpackage admin
 * @deprecated 3.0 Use {@link LeftAndMainExtension}
 */
abstract class LeftAndMainDecorator extends LeftAndMainExtension {
	
	public function __construct() {
		// TODO Re-enable before we release 3.0 beta, for now it "breaks" too many modules
		// user_error(
		// 	'LeftAndMainDecorator is deprecated, please use LeftAndMainExtension instead.',
		// 	E_USER_NOTICE
		// );
		parent::__construct();
	}
	
}