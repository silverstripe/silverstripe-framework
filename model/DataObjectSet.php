<?php
/**
 * @deprecated Please use {@link DataList} or {@link ArrayList} instead.
 * @package framework
 * @subpackage model
 */
class DataObjectSet extends ArrayList {

	/**
	 * @deprecated 3.0
	 */
	public function __construct($items = array()) {
		Deprecation::notice('3.0', 'DataObjectSet is deprecated. Use DataList or ArrayList instead', Deprecation::SCOPE_CLASS);

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
