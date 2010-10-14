<?php
/**
 * Represents a decimal field from 0-1 containing a percentage value.
 * 
 * Example instantiation in {@link DataObject::$db}:
 * <code>
 * static $db = array(
 * 	"SuccessRatio" => "Percentage",
 * 	"ReallyAccurate" => "Percentage(6)",
 * );
 * </code>
 * 
 * @package sapphire
 * @subpackage model
 */
class Percentage extends Decimal {
	
	/**
	 * Create a new Decimal field.
	 */
	function __construct($name, $precision = 4) {
		if(!$precision) $precision = 4;
	
		parent::__construct($name, $precision + 1, $precision);
	}
	
	/**
	 * Returns the number, expressed as a percentage. For example, “36.30%”
	 */
	function Nice() {
		return number_format($this->value * 100, $this->decimalSize - 2) . '%';
	}
	
	function saveInto($dataObject) {
		parent::saveInto($dataObject);
		
		$fieldName = $this->name;
		if($fieldName && $dataObject->$fieldName > 1.0) {
			$dataObject->$fieldName = 1.0;
		}
	}
}

?>
