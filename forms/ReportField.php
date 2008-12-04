<?php
/**
 * Displays complex reports based on the list of tables and fields provided to
 * the object.
 * @deprecated 2.3 Use TableListField
 * @package forms
 * @subpackage fields-reports
 */
class ReportField extends FormField{

	protected $tables;
	protected $primaryKeys;
	protected $fields;
	protected $filter;
	protected $primaryClass;
	protected $sort;
	protected $export = true;

	/**
	 * Construct a report field.
	 *
	 * @param name Name of the report field
	 * @param title Title of the report field as displayed in the CMS.
	 * @param tables Array of primary keys indexed by the names of the tables.
	 * @param fields Array of fields indexed by the name of the table.
	 */
	function __construct($name, $title, $tables, $fields, $hideByDefault = null, $defaultSort = null, $form = null) {

		parent::__construct( $name, $title, "", $form );

		if( !is_array( $tables ) )
			user_error( "Third parameter must be an array. Table Name => Primary Key Column", E_USER_ERROR );
		else {
			$this->tables = array_keys( $tables );
			$this->primaryKeys = $tables;
			$this->primaryClass = $this->tables[0];
		}

		if( !is_array( $fields ) )
			$this->fields = array( $fields );
		else
			$this->fields = $this->expandWildcards( $fields );
	}


	public function setExport($export){
		$this->export = $export;
	}


	protected function expandWildcards( $fields ) {

		$newFields = array();

		foreach( $fields as $field )
			if( preg_match( '/.*\.\*/', $field ) )
				$newFields = $newFields + $this->expandWildcard( $field );
			else
				$newFields[] = $field;

		return $newFields;
	}


	protected function expandWildcard( $field ) {
		list( $table, $column ) = $this->parseField( $field );

		foreach( $this->getColumnsInTable( $table ) as $newColumn )
			$columns[] = $table.'.'.$newColumn;

		return $columns;
	}


	function exportToCSV( $fileName ) {

		$fileData = $this->columnheaders( 'csvRow', 'csvCell' ) . $this->datacells( 'csvRow', 'csvCell' );

		header("Content-Type: text/csv; name=\"" . addslashes($fileName) . "\"");
		header("Content-Disposition: attachment; filename=\"" . addslashes($fileName) . "\"");
		header("Content-length: " . strlen($fileData));

		echo $fileData;
		exit();
	}


	function FieldHolder() {
		Requirements::javascript(SAPPHIRE_DIR . "/javascript/ReportField.js");

		$headerHTML = $this->columnheaders();
		$dataCellHTML = $this->datacells();
		$id = $this->id() . '_exportToCSV';
		if($this->export){
			$exportButton =<<<HTML
<input name="$id" type="submit" id="$id" class="ReportField_ExportToCSVButton" value="Export to CSV" />
HTML
;
		}else{
			$exportButton = "";
		}

		// display the table of results
		$html = <<<HTML
<div style="width: 98%; overflow: auto">
	$exportButton
<table class="ReportField" summary="">
	<thead>
		$headerHTML
	</thead>
	<tbody>
		$dataCellHTML
	</tbody>
</table>
</div>
HTML;
		return $html;
	}


	/**
	 * Returns the HTML for the data cells for the current report.
	 * This can be used externally via ajax as the report might be filtered per column.
	 * It is also used internally to display the data cells.
	 */
	public function datacells( $rowCallBack = 'htmlTableRow', $cellCallBack = 'htmlTableCell' ) {

		// get the primary records in the database, sorted according to the current sort
		// this will need to be corrected later on
		$primaryRecords = $this->getRecords();

		/*echo "ERROR:";
		Debug::show( $primaryRecords );
		die();*/

		$html = "";

		foreach( $primaryRecords as $record ) {
			$rowOutput = "";

			foreach( $this->fields as $field ) {
				if( $field{0} == '!' ) $field = substr( $field, 1 );

				list( $table, $column ) = $this->parseField( $field );

				if( $this->filter && !$this->filter->showColumn( $table, $column ) )
					continue;


				$rowOutput .= $this->$cellCallBack( $record[$field], $table, $column );
			}

			$html .= $this->$rowCallBack( $rowOutput, $table, null );

		}

		return $html;
	}


	/**
	 * Returns the HTML for the headers of the columns.
	 * Can also be called via ajax to reload the headers.
	 */
	public function columnheaders( $rowCallBack = 'htmlTableRow', $cellCallBack = 'htmlHeaderCell' ) {

		foreach( $this->fields as $field ) {
			list( $table, $column ) = $this->parseField( $field );

			if( $this->filter && !$this->filter->showColumn( $table, $column ) )
				continue; // replace this with some code to show a 'hidden' column

			/*if( $column == '*' )
				foreach( $this->getColumnsInTable( $table ) as $extraColumn )
					$html .= $this->$cellCallBack( $extraColumn, $table, $extraColumn );
			else	*/
				$html .= $this->$cellCallBack( $column, $table, $column );
		}

		return $this->$rowCallBack( $html, null, null );
	}


	protected function getColumnsInTable( $table ) {
		$result = DB::query( "SELECT * FROM `$table` LIMIT 1" );
		return array_keys( $result->next() );
	}


	protected function parseField( $field ) {
		if( $field{0} == '!' )
			$field = substr( $field, 1 );

		if( strpos( $field, '.' ) !== FALSE )
			return explode( '.', $field );
		else
			return $field;
	}


	/**
	 * Joins the given record together with the extra information in the other tables.
	 * This is only used in a situation in which the database can't do the join and I'll
	 * correct it when I figure out how to use buildSQL
	 */
	protected function joinRecord( $object ) {

		return $object;

		// split the list of fields into table, column and group by table.
		/*$tableColumns = array();

		foreach( $this->fields as $field ) {
			list( $table, $column ) = $this->parseField( $field );
			$tableColumns[$table][] = $column;
		}

		$primaryKey = $this->primaryKeys[$object->class];

		if( !$primaryKey ) foreach( ClassInfo::ancestry( $object->class ) as $baseClass )
			$primaryKey = $this->primaryKeys[$baseClass];

		$primaryKeyValue = $object->$primaryKey;

		// get the fields from the object
		$completeRecord = $this->joinFields( $object, $tableColumn[$this->primaryClass] );

		foreach( $tableColumns as $className => $classFields ) {
			$joinKey = $this->primaryKeys[$className];

			// get the all the extra fields.
			$recordObj = DataObject::get_one( $className, "`$className`.`$joinKey`='$primaryKeyValue'" );

			$completeRecord = $completeRecord + $this->joinFields( $recordObj, $fields );
		}

		return $completeRecord;*/
	}


	protected function joinFields( $object, $fields ) {
		$partialRecord = array();
		foreach( $fields as $field )
			$partialRecord[$object->class.'.'.$field] = $object->$field;

		return $partialRecord;
	}


	/**
	 * Sort the data in the cells
	 */
	public function sortdata() {

	}


	/**
	 * Get the primary set of records for the cells. This returns a data object set.
	 */
	protected function getRecords() {

		// $_REQUEST['showqueries'] = 1;

		$tableColumns = array();
		$selectFields = array();
		$joins = array( "`{$this->primaryClass}`" );

		foreach( $this->fields as $field ) {
			if( $field{0} == '!' )
				continue;

			list( $table, $column ) = $this->parseField( $field );
			$tableColumns[$table][] = $column;

			if( $column == '*' )
				$selectFields[] = "`$table`.*";
			else
				$selectFields[] = "`$table`.`$column` AS '$table.$column'";
		}

		foreach( array_keys( $tableColumns ) as $table ) {
			$tableKey = $this->primaryKeys[$table];
			$primaryKey = $this->primaryKeys[$this->primaryClass];

			if( $table != $this->primaryClass )
				$joins[] = "LEFT JOIN `$table` ON `$table`.`$tableKey`=`{$this->primaryClass}`.`$primaryKey`";
		}

		$query = new SQLQuery( $selectFields, $joins );
		return $query->execute();
	}


	function htmlHeaderCell( $value, $table, $column ) {
		return "<th>" . htmlentities( $value ) . "</th>";
	}


	function htmlTableCell( $value, $table, $column ) {
		return "<td>" . htmlentities( $value ) . "</td>";
	}


	function htmlTableRow( $value, $table, $column ) {
		return "<tr>" . $value . "</tr>";
	}


	function csvCell( $value, $table, $column ) {
		return '"' . str_replace( '"', '""', $value ) . '",';
	}


	function csvRow( $value, $table, $column ) {
		return substr( $value, 0, strlen( $value ) - 1 )."\n";
	}
}



/**
 * Assisting class. Determines whether or not a column is hidden.
 * Not so helpful here, but we could overload it in other classes.
 * @deprecated 2.3 Use TableListField
 * @package forms
 * @subpackage fields-reports
 */
class ReportField_SimpleFilter extends Object {

	protected $hiddenFields;

	function __construct( $hiddenColumns ) {
		$this->hiddenFields = $hiddenColumns;
	}

	function columnIsVisible( $table, $column ) {
		return !isset( $this->hiddenFields[$table.'.'.$column] );
	}

	function showColumn( $table, $column ) {
		unset( $this->hiddenFields[$table.'.'.$column] );
	}

	function hideColumn( $table, $column ) {
		$this->hiddenFields[$table.'.'.$column] = 1;
	}
}



/**
 * This class instantiates an instance of the report field and receives ajax requests
 * to the report field.
 * @deprecated 2.3 Use TableListField
 * @package forms
 * @subpackage fields-reports
 */
class ReportField_Controller extends Controller {

	function exporttocsv() {

		if( $this->urlParams['Type'] != 'ReportField' && ClassInfo::exists( $this->urlParams['Type'].'_Controller' ) ) {
			$type = $this->urlParams['Type'].'_Controller';
			$controller = new $type();
			return $controller->exporttocsv( $this->urlParams['ID'] );
		}

		$pageID = $this->urlParams['ID'];

		if( !$pageID )
			return "ERROR:Page does not exist";

		$page = DataObject::get_by_id( 'SiteTree', $pageID );

		if( !$page )
			return "ERROR:Page does not exist";

		$formName = substr( $this->urlParams['OtherID'], 0, -4 );

		$reportField = $page->getReportField( $formName );

		// apply filters

		$fileName = $page->URLSegment . "-report.csv";

		$reportField->exportToCSV( $fileName );
	}


	function Link() {
		return "";
	}
}

?>