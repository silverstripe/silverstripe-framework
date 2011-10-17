<?php
/**
 * @package    sapphire
 * @subpackage model
 * @deprecated 3.0 Use {@link DataExtension}.
 */
abstract class DataObjectDecorator extends DataExtension {

	public function __construct() {
		// TODO Re-enable before we release 3.0 beta, for now it "breaks" too many modules
		// user_error(
		// 	'DataObjectDecorator is deprecated, please use DataExtension instead.',
		// 	E_USER_NOTICE
		// );
		parent::__construct();
	}

}