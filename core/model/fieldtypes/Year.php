<?php
/**
 * @package sapphire
 * @subpackage model
 */

/**
 * Represents a single year field.
 * 
 * @package sapphire
 * @subpackage model
 */
class Year extends DBField {
	
	function requireField() {
		DB::requireField($this->tableName, $this->name, "year");
	}
	
	public function scaffoldFormField($title = null) {
		$selectBox = new DropdownField($this->name, $title);
		$selectBox->setSource($this->getDefaultOptions());
		return $selectBox;
	}
	
	private function getDefaultOptions() {
		$start = (int)date('Y');
		$end = 1900;
		$years = array();
		for($i=$start;$i>=$end;$i--) {
			$years[] = $i;
		}
		return $years;
	}
	
}
?>