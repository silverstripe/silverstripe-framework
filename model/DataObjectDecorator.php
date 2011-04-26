<?php
/**
 * @package    sapphire
 * @subpackage model
 * @deprecated 3.0 Use {@link DataExtension}.
 */
abstract class DataObjectDecorator extends DataExtension {

	public function __construct() {
		user_error(
			'DataObjectDecorator is deprecated, please use DataExtension instead.',
			E_USER_NOTICE
		);
		parent::__construct();
	}

}