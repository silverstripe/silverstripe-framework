<?php
/**
 * Text field that automatically checks that the value entered is unique for the given
 * set of fields in a given set of tables
 * @deprecated 2.3
 * @package forms
 * @subpackage fields-formattedinput
 */
class UniqueTextField extends TextField {
	
	protected $restrictedField;
	protected $restrictedTable;
	protected $restrictedMessage;
	
	function __construct($name, $restrictedField, $restrictedTable, $restrictedMessage, $title = null, $value = "", $maxLength = null ){
		$this->maxLength = $maxLength;
		
		$this->restrictedField = $restrictedField;
		
		$this->restrictedTable = $restrictedTable;
		$this->restrictedMessage = $restrictedMessage;
		
		parent::__construct($name, $title, $value);	
	}
	 
	function Field() {
		Requirements::add_i18n_javascript(SAPPHIRE_DIR . '/javascript/lang');
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/UniqueFields.js");
		
		/*		
		$restrictedValues = array();
		$restrictedInputs = array();
		
		// if the restrictedFields and tables have been specified,
		// then get the restricted values
		if( !empty( $this->restrictedField ) && !empty( $this->restrictedTable ) ) {
			$result = DB::query("SELECT \"{$this->restrictedField}\" FROM \"{$this->restrictedTable}\"");
			
			$count = 1;
			
			while( $restrictedValue = $result->nextRecord() )
				$restrictedValues[$restrictedValue[$this->restrictedField]] = 1;
				
			$result = DB::query("SELECT \"{$this->restrictedField}\" FROM \"{$this->restrictedTable}_Live\"");
			
			while( $restrictedValue = $result->nextRecord() )
				$restrictedValues[$restrictedValue[$this->restrictedField]] = 1;	
				
			// remove the initial value of this field
			$restrictedValues = array_diff_assoc( $restrictedValues, array( $this->attrValue() => 1 ) );	
				
			foreach( $restrictedValues as $restrictedValue => $discard )
				$restrictedInputs[] = "<input type=\"hidden\" id=\"".$this->id()."-restricted-".($count++)."\" value=\"$restrictedValue\" name=\"restricted-values[".$this->id()."]\" />";
		}
		*/
		
		$fieldSize = $this->maxLength ? min( $this->maxLength, 30 ) : 30;

		if($this->maxLength){
			return /*implode("", $restrictedInputs).*/"<input class=\"".$this->class."\" type=\"text\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" maxlength=\"$this->maxLength\" size=\"$fieldSize\" /><input type=\"hidden\" name=\"restricted-messages[".$this->id()."]\" id=\"".$this->id()."-restricted-message\" value=\"{$this->restrictedMessage}\" />";
		}else{
			return /*implode("", $restrictedInputs).*/"<input class=\"".$this->class."\" type=\"text\" id=\"" . $this->id() . "\" name=\"{$this->name}\" value=\"" . $this->attrValue() . "\" size=\"30\" /><input type=\"hidden\" name=\"restricted-messages[".$this->id()."]\" id=\"".$this->id()."-restricted-message\" value=\"{$this->restrictedMessage}\" />"; 
		}
	}
}
?>