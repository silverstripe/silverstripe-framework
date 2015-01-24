<?php
/**
 * @package framework
 * @subpackage model
 */

/**
 * Represents a single year field.
 *
 * @package framework
 * @subpackage model
 */
class Year extends DBField {

	public function requireField() {
		$parts=Array('datatype'=>'year', 'precision'=>4, 'arrayValue'=>$this->arrayValue);
		$values=Array('type'=>'year', 'parts'=>$parts);
		DB::require_field($this->tableName, $this->name, $values);
	}

	public function scaffoldFormField($title = null, $params = null) {
		$selectBox = new DropdownField($this->name, $title);
		$selectBox->setSource($this->getDefaultOptions());
		return $selectBox;
	}

	/**
	 * Returns a list of default options that can
	 * be used to populate a select box, or compare against
	 * input values. Starts by default at the current year,
	 * and counts back to 1900.
	 *
	 * @param int $start starting date to count down from
	 * @param int $end end date to count down to
	 * @return array
	 */
	private function getDefaultOptions($start=false, $end=false) {
		if (!$start) $start = (int)date('Y');
		if (!$end) $end = 1900;
		$years = array();
		for($i=$start;$i>=$end;$i--) {
			$years[$i] = $i;
		}
		return $years;
	}

}
