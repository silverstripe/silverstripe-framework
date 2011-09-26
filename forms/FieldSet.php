<?php
/**
 * @deprecated 3.0 Please use {@link FieldList}.
 *
 * @package    forms
 * @subpackage fields-structural
 */
class FieldSet extends FieldList {

	public function __construct($items = array()) {
		user_error(
			'FieldSet is deprecated, please use FieldList instead.', E_USER_NOTICE
		);

		parent::__construct(!is_array($items) || func_num_args() > 1 ? func_get_args(): $items);
	}
}
