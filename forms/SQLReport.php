<?php
/**
 * @package forms
 * @subpackage fields-reports
 * @deprecated This should be considered alpha code; reporting needs a clean-up.
 */
class SQLReport extends DataReport {
	protected $sql;
	
	function __construct($name, $title, $value, $form, $fieldMap=null, $headFields = null, $sql) {
		parent::__construct($name, $title, $value, $form, null, $fieldMap, $headFields);
		$this->sql = $sql;
	}
	
	function getRecords(){
		$records = DB::query($this->sql);
		return $records;
		/*$dataobject = new DataObject();
		return $dataobject->buildDataObjectSet($records, 'DataObjectSet');*/
	}
	
	
}

?>