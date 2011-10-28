<?php
/**
 * @deprecated 3.0 Please use {@link FieldList}.
 *
 * @package    forms
 * @subpackage fields-structural
 */
class FieldSet extends FieldList {

	/**
	 * @deprecated 3.0.0 Use FieldList instead
	 */
	public function __construct($items = array()) {
		Deprecation::notice('FieldSet is deprecated. Use FieldList instead', '3.0.0');
		parent::__construct(!is_array($items) || func_num_args() > 1 ? func_get_args(): $items);
	}
}
