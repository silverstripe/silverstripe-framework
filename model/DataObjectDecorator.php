<?php
/**
 * @package framework
 * @subpackage model
 * @deprecated 3.0 Use {@link DataExtension}.
 */
abstract class DataObjectDecorator extends DataExtension {

	public function __construct() {
		Deprecation::notice('3.0', 'Use DataExtension instead.');
		parent::__construct();
	}

}
