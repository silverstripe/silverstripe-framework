<?php
/**
 * @package    sapphire
 * @subpackage admin
 * @deprecated 3.0 Use {@link LeftAndMainExtension}
 */
abstract class LeftAndMainDecorator extends LeftAndMainExtension {
	
	public function __construct() {
		user_error(
			'LeftAndMainDecorator is deprecated, please use LeftAndMainExtension instead.',
			E_USER_NOTICE
		);
		parent::__construct();
	}
	
}