<?php

class Int extends DBField {	
	function Formatted() {
		return number_format($this->value);
	}
	function nullValue() {
		return "0";
	}

	function requireField() {
		DB::requireField($this->tableName, $this->name, "int(11) not null default '0'");
	}
	
	function Times() {
		$output = new DataObjectSet();
		for( $i = 0; $i < $this->value; $i++ )
			$output->push( new ArrayData( array( 'Number' => $i + 1 ) ) );
			
		return $output;
	}
	
	function Nice() {
		return sprintf( '%d', $this->value );
	}
}

?>