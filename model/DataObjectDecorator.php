<?php
/**
 * @package framework
 * @subpackage model
 * @deprecated 3.0 Use {@link DataExtension}.
 */
abstract class DataObjectDecorator extends DataExtension {

	public function __construct() {
		Deprecation::notice('3.0', 'DataObjectDecorator is deprecated. Use DataExtension instead.', Deprecation::SCOPE_CLASS);
		parent::__construct();
	}

}
