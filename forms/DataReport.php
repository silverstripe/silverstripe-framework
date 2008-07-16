<?php
/**
 * Displays complex reports based on base Table of DataObject and available functions/fields provided to
 * the object.
 * @package forms
 * @subpackage fields-reports
 * @deprecated This should be considered alpha code; reporting needs a clean-up.
 */
class DataReport extends FormField {
	
	protected $baseClass, $filter, $sort, $join, $dateFilter;
	protected $headFields, $dataFields;
	protected $export=true;
	protected $joinedTables;
	
	/**
	 * @var array Adds extra columns to the end of the table (e.g. printlinks)
	 */
	protected $extraFields = array();
	
	/**
	 * @var array Due to the arbitrary array-format of the desired input, we can't add custom SQL through the normal $filter
	 */
	protected $customSelect = array();
	
	/**
	 * @var array Specify castings with fieldname as the key, and the desired casting as value.
	 * Example: array("MyCustomDate"=>"Date->Nice")
	 */
	protected $fieldCasting = array();
	
	/**
	 * Construct a OrderReport.
	 *
	 * @param name Name of the data report
	 * @param title Title of the data report as displayed in the CMS.
	 * @param value The value of the data report as a form field, usually could be a empty string
	 * @param form The form that the containing the date report, usually dose matter if set null
	 * @param baseClass the base object that this report works oon
	 * @param fieldMap A mapthat works out Header cell of report table and which content will be for the colums, using array of "$k=>$v"s
	 * @param headFields If keys in fieldMap is numerical, header of the report table can be worked out here
	 * @param filter The filter on the object for the report, usually this filter is "=" type filter for the database table field.
	 * @param dateFilter The dateFitler on the object for the report, usually this filter is for time and works on Created using "Between ... And ..."
	 * @param join The joined database table is put in this parameter
	 * @param sort The sort clause will be made from this parameter
	 */
	function __construct($name, $title, $value, $form, $baseClass, $fieldMap=null, $headFields=null, $filter=null, $dateFilter=null, $sort=null, $join=null) {
		
		$this->baseClass = $baseClass;
		
		//Work out $filters, $sort, $join, $filters need to be further processed in getRecords();
		if($filter) $this->filter = $filter;

		if($dateFilter) $this->dateFilter=$dateFilter;
		if($sort) $this->sort = $sort;
		if($join) $this->join = $join;
		
		//Work out $headFields, $dataFields
		if($fieldMap){
			
			if(is_array($fieldMap)){
				foreach($fieldMap as $k => $v){
					$fields[] = $v;
					if(!$headFields)
						$heads[] = $k;
				}
			}
		}
		
		if($fields)
			$this->dataFields = $fields;

		if(!$headFields)
			$this->headFields = $heads;
		else
			$this->headFields = $headFields;
		
		parent::__construct($name, $title, $value, $form);
	}
	
	/*protected function expandWildcards($fieldMap){
		$records = $this->getRecords();
		if($records){
			foreach($records as $record){
				Debug::show($record);
				die('here');
			}
		}
	}*/
	
	/**
		* @todo: to set export flag to be $export 
		*/
	public function setExport($export){
		$this->export = $export;
	}
	
	/**
		*	Todo: to export the reported table as a CSV
		*/
	function exportToCSV( $fileName ) {
		$fileData = $this->columnheaders( 'csvRow', 'csvHeadCell' ) . $this->datacells('csvRow', 'csvDataCell' );
		HTTP::sendFileToBrowser($fileData, $fileName);
	}
	
	/** 
		* @todo: to overwrite its parent's FieldHolder, the returned HTML <div> section contains the reported Table and a export button.
		*/
	function FieldHolder() {
		//Requirements::javascript( "sapphire/javascript/DataReport.js" );
		
		$reportList = $this->htmlReportList();
		$exportButton = $this->htmlExportButton();

		return <<<HTML
<div style="margin-bottom: 30px; width: 98%;" class="tab" id="DataReport">
	<input type="hidden" id="DataReport_Type" name="DataReport_Type" value="{$this->getReportType()}" />
	$reportList 
	$exportButton
</div>
HTML
;
	}
	
	/**
		* @todo: to return a export button in HTML Style if $this->export flag is true (it defaults as true)
		*/
	protected function htmlExportButton(){
		$idexport = $this->id() . '_exportToCSV';
		$idtype = $this->id() . '_Type';
		$class = $this->class;
		if($this->export){
		  $value = _t('DataReport.EXPORTCSV', 'Export to CSV');
		  $exportButton =<<<HTML
<input name="$idexport" style="width: 12em" type="button" id="$idexport" class="DataReport_ExportToCSVButton" value="$value" />
<input name="Type" type="hidden" value="$class" id="$idtype" />
HTML
;
		}else{
			$exportButton = "";
		}
		
		return $exportButton;
	}
	
	/**
		*Todo: to return block of HTML code, containing a table wrapped with a <div> section for Ajax Updator loading the table inside.
		*/
	protected function htmlReportList(){
	

		$table = $this->htmlReportTable();
		// display the table of results
		$html = <<<HTML
<div id="ReportList_Loader" class="TableListField">
		$table
</div>
HTML;
		return $html;
}

	/**
		*Todo: return the reported table in HTML format
		*/
	protected function htmlReportTable(){
		$headerHTML = $this->columnheaders();
		$dataCellHTML = $this->datacells();
		
		return <<<HTML
<table class="ReportField data" id="ReportList">
	<thead>
		$headerHTML
	</thead>
	<tbody>
		$dataCellHTML
	</tbody>
</table>
HTML
;
	}
		
	
 /**
	 * @todo: Returns the HTML for the headers of the columns.
	 */
	protected function columnheaders( $rowCallBack = 'htmlTableRow', $cellCallBack = 'htmlTableHeadCell' ) {
		$html = "";
		foreach( $this->headFields as $field ) {
			$html .= $this->$cellCallBack($field);
		}
		foreach( $this->extraFields as $fieldHeader => $fieldContent ) {
			$html .= $this->$cellCallBack($fieldHeader);
		}
		return $this->$rowCallBack($html); 
	}
	
	/**
		*Todo: Returns the HTML for the body of the reported table, excluding table header
		* @param rowCallBack: the function name for how the rows are formated
		* @param cellCallBack: the function name for how the cells are formated
		*/
	protected function datacells($rowCallBack = 'htmlTableRow', $cellCallBack = 'htmlTableDataCell' ) {
		$records = $this->getRecords();
		
		$body = "";
		if($records){

			foreach($records as $record){
				$html = "";
				$fieldInd = 0;
				foreach( $this->dataFields as $field ) {
					$html .= $this->$cellCallBack($record, $field, $fieldInd);
					$fieldInd++;
				}
				foreach( $this->extraFields as $fieldHeader => $fieldContent ) {
					$html .= $this->htmlTableExtraDataCell($record, $fieldContent, $fieldInd);
				}
				$row = $this->$rowCallBack($html);
				$body .= $row;
			}
		}
		return $body;
	}
	
	/**
		* @todo: Return the HTML for one cell of the table header
		*/
	function htmlTableHeadCell($value) {
		return "<th>" . htmlentities( $value ) . "</th>";
	}
	
	/**
		* @todo: Return one cell of the table header in csv format
		*/
	function csvHeadCell($value) {
		return $this->csvCell( $value );
	}
	
	/**
		* @todo: Return the HTML for one cell of one row of the table
		*/
	function htmlTableDataCell($record, $field, $fieldIndex=null){
		$value = $this->getRecordFieldValue($record, $field);
		
		return "<td>".Convert::raw2xml($value)."</td>";
	}
	
	function htmlTableExtraDataCell($record, $fieldContent, $fieldIndex=null){
		eval("\$content = \"$fieldContent\";");
		return "<td>$content</td>";
	}
	
	/**
		*Todo: return the value of one filed of a record
		*/
	function getRecordFieldValue($record, $field){
		// $field can be array: eg: array("FirstName", "Surname"), in which case two fields in database table should
		//	concate to one cell for report table
		if(is_array($field)){
			$i=0;
			foreach($field as $each){
				$value .= $i==0?"":" ";
				$value .= $record->$each;
				$i++;
			}
		}else{
			//The field could be "Total->Nice" or "Order.Created->Date", intending to show its specific format.
			//Caster then is "Nice" "Date" etc.
			list($field, $caster) = explode("->", $field);
			if(preg_match('/^(.+)\.(.+)$/', $field, $matches)){
				$field = $matches[2];
			}

			if(is_a($record, 'DataObject')) { // Simple field, no casting
				// choose existing field on a DataObject, or custom record-column
				$value = ($record->val($field)) ? $record->val($field) : $record->$field;
			} elseif(is_array($record)) {
				$value = $record[$field];
			} else {
				$value = $record;
			}

			// casting by combined string
			if($caster){
				// When the intending value is Created.Date, the obj need to be casted as Datetime explicitely.
				if($field == "Created" || $field == "LastEdited"){
					$created = Object::create('Datetime', "Created"); 
					$created->setVal($record->Created); 
					$value = $created->val($caster);  
				}
				else{
					 // Dealing with other field like "Total->Nice", etc.
					 // Also makes sure the field is present
					if($record && $record->obj($field)){
						$value = $record->obj($field)->val($caster);
					}
				}
			// casting by separate array 
			} else if(array_key_exists($field, $this->fieldCasting)) {
				$fieldType = $this->fieldCasting[$field];
				if(strpos($fieldType,'->') === false) {
					$castingFieldType = $fieldType;
					$castingField = new $castingFieldType($field);
					$castingField->setValue($value);
					$value = $castingField->XML();
				} else {
					$fieldTypeParts = explode('->', $fieldType);
					$castingFieldType = $fieldTypeParts[0];	
					$castingMethod = $fieldTypeParts[1];
					$castingField = new $castingFieldType($field);
					$castingField->setValue($value);
					$value = $castingField->$castingMethod();
				}
			}
		}
		
		return $value;
	}
	
	/**
		*Todo:: return a date cell in CSV format
		*/
	function csvDataCell($record, $field){
		$value=$this->csvCell( $this->getRecordFieldValue($record, $field) );
		//return str_replace("\n", "", str_replace("\n", "", str_replace(",", "", $value))).",";
		return $value;
	}
	
	function csvCell( $value, $table = null, $column = null ) {
		return '"' . str_replace( '"', '""', $value ) . '",';
	}
	
	function csvRow( $value ) {
		return substr( $value, 0, strlen( $value ) - 1 )."\n";
	}
	
	/**
		*Todo: wrap a row of data by <tr> tag
		*/
	function htmlTableRow( $value) {
		return "<tr>" . $value . "</tr>";
	}
	
	/**
		*Todo: to add newline feed to a row of data for csv export
		*/
	/*function csvRow($value){
		return str_replace("\n", "", $value)."\n";
	}*/
	
	/**
		*Todo: get all records of base table that meet the $this->filter, $this->join, $this->datefilter, sort by $this->sort
		*/
	function getRecords(){
		$join = '';
		$filter = '';
		$sort = '';
		$orclause = '';
		
		if($this->filter){
			$i=0;
			//$this->filter should be an array, such as array("MemberID"=>array("1", "2), "OrderType"=>"normal")
			foreach($this->filter as $k => $v){
				if($v !== "All" && $v !== 'all'){
					$join .= ($i==0) ? "" :" ";
					//if $v is array, then treat it as an "OR" statement in where claues, 
					//eg. $this->filter = array("MemberID"=>array("1", "2), "OrderType"=>"normal"), then MemberID need to be either 1 or 2.
					if(is_array($v)){
						$j = 0;
						foreach($v as $orvalue){
							$orclause .=($j==0) ? "($k = '$orvalue'" : " OR $k = '$orvalue'";
							$j++;
						}
						$orclause .=")";
						$filter .= ($i==0) ? "$orclause" : " AND $orclause";
						$i++;
					} else if(is_numeric($k)) {
						// accept a simple string...
						$filter .= $v;  						
						$filter .= ($i==0) ? "$orclause" : " AND $orclause";
					} else {
						//$v is not an array, concat it to where clause as an "AND" statement.
						//eg. $this->filter = array("MemberID"=>array("1", "2), "OrderType"=>"normal"), then concat "OrderType"="normal".
					
						$filter .= ($i==0) ? "$k = '$v'" : " AND $k = '$v'";
					}
					$i++;
				}
			}
		}
				
		// Concat DateRange to Where clause using "Between ... And ..." on Created field.
		if($this->dateFilter){
			$from = preg_replace('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-90-9]{2,4})/', '\\3-\\2-\\1', $this->dateFilter['From']);
			$to = preg_replace('/^([0-9]{1,2})\/([0-9]{1,2})\/([0-90-9]{2,4})/', '\\3-\\2-\\1', $this->dateFilter['To']);
			$filter .= $filter?"AND ":"";
			$filter .="`{$this->baseClass}`.Created BETWEEN '$from' AND ('$to' + INTERVAL 1 DAY)";
		}
		
		// Work out Ordered By clause.
		if($this->sort){
			$i=0;
			foreach($this->sort as $k => $v){
				$sort .= ($i==0)?"$k $v":", $k $v";
				$i++;
			}
		}
		
		// Work out Join Clause.
		if($this->join){
			$i=0;
			
			//$k is the key of base table, $v is an array with joined table and join key, such as
			// $v = array("ID"=>array("table"=>"Payment", "field"=>"OrderID", "joinclass" => "Order")).
			// otherwise it treats it as a SQL-string ("LEFT JOIN x ON  x=y")
			foreach($this->join as $k => $v){
				$join .= ( $i==0 ) ? "" :" ";

				if(is_string($v)) {
					$join .= $v;
				} else {
					// This means we join multiple tables on the same column name
					if(!$k || !is_string($k)) $k = $v['joinColumn'];
					if(!$v['howtojoin']) $v['howtojoin'] = "LEFT JOIN";
					$this->joinedTables[] = $v['table'];
					// FIX Stupid arbitrary array-structure makes this neccessary
					$joinClass = ($v['joinclass']) ? $v['joinclass'] : $this->baseClass;
					$join .="$v[howtojoin] `$v[table]` on `{$joinClass}`.`{$k}` = `$v[table]`.`$v[field]`";
				}
				$i++;
			}
		}

		$instance = singleton($this->baseClass);

		$query = $instance->buildSQL($filter, $sort, null, $join);
		$selected = array();
		
		$this->buildSelected($this->dataFields, $selected);
		$query->select = $selected;
		
		$query->select[] = "`$this->baseClass`.ClassName";
		$query->select[] = "`$this->baseClass`.ClassName AS RecordClassName";
		$records = $query->execute();
		$dataobject = new DataObject();
		$ret = $dataobject->buildDataObjectSet($records, 'DataObjectSet');

		return $ret;
	}
	
	protected function buildSelected($fields, &$ret, $includeAllFields = true){
	
		if($includeAllFields){
			$ret1 = array();

			foreach(ClassInfo::subclassesFor($this->baseClass) as $subClass){
				if(ClassInfo::hastable($subClass)){
					if($columns = $this->getColumnsInTable($subClass)){
						foreach ($columns as $column){
							if(!in_array($column, $ret1)){
								$ret[] = "`{$subClass}`.`$column`";
								$ret1[] = $column;
							}
						}
					}
				}
			}
			
			if($this->joinedTables){
				foreach($this->joinedTables as $table){
					$columns = $this->getColumnsInTable($table);
					if($columns) foreach($columns as $column){
						if(!in_array($column, $ret1)){
							$ret[] = "`{$table}`.`{$column}`";
							$ret1[] = $column;
						}
					}
				}
			}
		} else {
			
			foreach($fields as $field) {
				if(is_array($field)) {
					$this->buildSelected($field, $ret);
				} else {
					if($count=preg_match('/^(.+)->(.+)$/', $field, $matches)) {
						$field =$matches[1];
					}
					
					if(!preg_match('/^(.+)\.(.+)$/', $field, $matches)) {
						if($this->is_inTables($field)) {
							$ret[] = $field;
						}
					} else {
						$ret[] = $field;
					}
				}
			}
		}
		
		foreach($this->customSelect as $filter) {
			$ret[] = $filter;
		}
	}
	
	protected function is_inTables($field){
		if(in_array($field, $this->getColumnsInTable($this->baseClass))){
			return 1;
		}else{
			if($this->joinedTables){
				foreach($this->joinedTables as $table){
					if(in_array($field, $this->getColumnsInTable($table)))
						return 1;
				}
			}
		}
		return 0;
	}
	

	protected function getColumnsInTable( $table ) {
		$result = DB::query( "SELECT * FROM `$table` LIMIT 1" );
		if($nextResult = $result->next()){
			return array_keys($nextResult);	
		}		
	}
	
	protected function getReportType() {
		if(class_exists($this->name)) return $this->name;
		else return $this->class;
	}
	
	/**
	 * @param array
	 */
	function setExtraFields($fields) {
		$this->extraFields = $fields;
	}
	
	/**
	 * @param array
	 */
	function setCustomSelect($filters) {
		$this->customSelect = $filters;		
	}
	
	/**
	 * @param array
	 */
	function setFieldCasting($fieldCasting) {
		$this->fieldCasting = $fieldCasting;
	}
}


/**
 * A controller class work for exporting reported table to CSV format.
 * @package forms
 * @subpackage fields-reports
 * @deprecated This should be considered alpha code; reporting needs a clean-up.
 */
class DataReport_Controller extends Controller{

	/**The function is an action for making $Controller/$action/$ID works when click on Exporting button.
		*Todo: Declear a new OrderReport Object with null filter, then set its filter using $_GET global, then call its export function.
		*/
	function exporttocsv() {
		$id = $this->urlParams['ID'];
		$now = strftime("%d/%m/%Y", time());
	
		$orderReport = new $id("FindOrderReport",null,"",null,"Order",
			$filedMap = array("Order Number"=>"Order.ID",
						"Order Date"=>"Order.Created->Date",
						"Order Type"=>"ParentsOrder.OrderType",
						"Customer Name"=>array("FirstName", "Surname"),
						"Order Total"=>"Total->Nice",
			),
			$headField = null,
			$filter = null,
			$dateFilter = array("From"=>"20/07/2006", "To"=>$now),
			$sord = array("Order.ID"=>"DESC"),
			$join = array("MemberID"=>array("table"=>"Member", "field"=>"ID"))
		);
		
		if($orderReport->hasMethod('getReportField')) $orderReport = $orderReport->getReportField();
		
		if($orderReport->hasMethod('filter_onchange')) $orderReport->filter_onchange();
		$orderReport->exportToCSV("report.csv");
	}
}
?>