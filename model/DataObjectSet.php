<?php
/**
 * @deprecated Please use {@link DataList} or {@link ArrayList} instead.
 * @package    sapphire
 * @subpackage model
 */
class DataObjectSet extends ArrayList {

	public function __construct($items = array()) {
		user_error(
			'DataObjectSet is deprecated, please use DataList or ArrayList instead.',
			E_USER_NOTICE
		);

		if ($items) {
			if (!is_array($items) || func_num_args() > 1) {
				$items = func_get_args();
			}

			foreach ($items as $i => $item) {
				if ($item instanceof ViewableData) {
					continue;
				}

				if (is_object($item) || ArrayLib::is_associative($item)) {
					$items[$i] = new ArrayData($item);
				} else {
					user_error(
						"DataObjectSet::__construct: Passed item #{$i} is not an"
						. ' and object or associative array, can\'t be properly'
						. ' iterated on in templates', E_USER_WARNING
					);
				}
			}
		}

		parent::__construct($items);
	}

}