<?php
/**
 * Class to handle parsing of CSV files, where the column headers are in the
 * first row.
 *
 * The idea is that you pass it another object to handle the actual processing
 * of the data in the CSV file.
 *
 * Usage:
 *
 * <code>
 * $parser = new CSVParser('myfile.csv');
 * $parser->mapColumns(array(
 *    'first name' => 'FirstName'
 *    'lastname' => 'Surname',
 *    'last name' => 'Surname',
 * ));
 * foreach($parser as $row) {
 * 	 // $row is a map of column name => column value
 *   $obj = new MyDataObject();
 *   $obj->update($row);
 *   $obj->write();
 * }
 * </code>
 *
 * @package framework
 * @subpackage bulkloading
 */
class CSVParser extends Object implements Iterator {

	/**
	 * @var string $filename
	 */
	protected $filename;

	/**
	 * @var resource $fileHandle
	 */
	protected $fileHandle;

	/**
	 * Map of source columns to output columns.
	 *
	 * Once they get into this variable, all of the source columns are in
	 * lowercase.
	 *
	 * @var array
	 */
	protected $columnMap = array();

	/**
	 * The header row used to map data in the CSV file.
	 *
	 * To begin with, this is null.  Once it has been set, data will get
	 * returned from the CSV file.
	 *
	 * @var array
	 */
	protected $headerRow = null;

	/**
	 * A custom header row provided by the caller.
	 *
	 * @var array
	 */
	protected $providedHeaderRow = null;

	/**
	 * The data of the current row.
	 *
	 * @var array
	 */
	protected $currentRow = null;

	/**
	 * The current row number.
	 *
	 * 1 is the first data row in the CSV file; the header row, if it exists,
	 * is ignored.
	 *
	 * @var int
	 */
	protected $rowNum = 0;

	/**
	 * The character for separating columns.
	 *
	 * @var string
	 */
	protected $delimiter = ",";

	/**
	 * The character for quoting columns.
	 *
	 * @var string
	 */
	protected $enclosure = '"';

	/**
	 * Open a CSV file for parsing.
	 *
	 * You can use the object returned in a foreach loop to extract the data.
	 *
	 * @param $filename The name of the file.  If relative, it will be relative to the site's base dir
	 * @param $delimiter The character for seperating columns
	 * @param $enclosure The character for quoting or enclosing columns
	 */
	public function __construct($filename, $delimiter = ",", $enclosure = '"') {
		$filename = Director::getAbsFile($filename);
		$this->filename = $filename;
		$this->delimiter = $delimiter;
		$this->enclosure = $enclosure;

		parent::__construct();
	}

	/**
	 * Re-map columns in the CSV file.
	 *
	 * This can be useful for identifying synonyms in the file. For example:
	 *
	 * <code>
	 * $csv->mapColumns(array(
	 *   'firstname' => 'FirstName',
	 *   'last name' => 'Surname',
	 * ));
	 * </code>
	 *
	 * @param array
	 */
	public function mapColumns($columnMap) {
		if($columnMap) {
			$lowerColumnMap = array();

			foreach($columnMap as $k => $v) {
				$lowerColumnMap[strtolower($k)] = $v;
			}

			$this->columnMap = array_merge($this->columnMap, $lowerColumnMap);
		}
	}

	/**
	 * If your CSV file doesn't have a header row, then you can call this
	 * function to provide one.
	 *
	 * If you call this function, then the first row of the CSV will be
	 * included in the data returned.
	 *
	 * @param array
	 */
	public function provideHeaderRow($headerRow) {
		$this->providedHeaderRow = $headerRow;
	}

	/**
	 * Open the CSV file for reading.
	 */
	protected function openFile() {
		ini_set('auto_detect_line_endings',1);
		$this->fileHandle = fopen($this->filename,'r');

		if($this->providedHeaderRow) {
			$this->headerRow = $this->remapHeader($this->providedHeaderRow);
		}
	}

	/**
	 * Close the CSV file and re-set all of the internal variables.
	 */
	protected function closeFile() {
		if($this->fileHandle) {
			fclose($this->fileHandle);
		}

		$this->fileHandle = null;
		$this->rowNum = 0;
		$this->currentRow = null;
		$this->headerRow = null;
	}


	/**
	 * Get a header row from the CSV file.
	 */
	protected function fetchCSVHeader() {
		$srcRow = fgetcsv(
			$this->fileHandle,
			0,
			$this->delimiter,
			$this->enclosure
		);

		$this->headerRow = $this->remapHeader($srcRow);
	}

	/**
	 * Map the contents of a header array using $this->mappedColumns.
	 *
	 * @param array
	 *
	 * @return array
	 */
	protected function remapHeader($header) {
		$mappedHeader = array();

		foreach($header as $item) {
			if(isset($this->columnMap[strtolower($item)])) {
				$item = $this->columnMap[strtolower($item)];
			}

			$mappedHeader[] = $item;
		}
		return $mappedHeader;
	}

	/**
	 * Get a row from the CSV file and update $this->currentRow;
	 *
	 * @return array
	 */
	protected function fetchCSVRow() {
		if(!$this->fileHandle) {
			$this->openFile();
		}

		if(!$this->headerRow) {
			$this->fetchCSVHeader();
		}

		$this->rowNum++;

		$srcRow = fgetcsv(
			$this->fileHandle,
			0,
			$this->delimiter,
			$this->enclosure
		);

		if($srcRow) {
			$row = array();

			foreach($srcRow as $i => $value) {
				// Allow escaping of quotes and commas in the data
				$value = str_replace(
					array('\\'.$this->enclosure,'\\'.$this->delimiter),
					array($this->enclosure, $this->delimiter),
					$value
				);

				if(array_key_exists($i, $this->headerRow)) {
					if($this->headerRow[$i]) {
						$row[$this->headerRow[$i]] = $value;
					}
				} else {
					user_error("No heading for column $i on row $this->rowNum", E_USER_WARNING);
				}
			}

			$this->currentRow = $row;
		} else {
			$this->closeFile();
		}

		return $this->currentRow;
	}

	/**
	 * @ignore
	 */
	public function __destruct() {
		$this->closeFile();
	}

	//// ITERATOR FUNCTIONS

	/**
	 * @ignore
	 */
	public function rewind() {
		$this->closeFile();
		$this->fetchCSVRow();
	}

	/**
	 * @ignore
	 */
	public function current() {
		return $this->currentRow;
	}

	/**
	 * @ignore
	 */
	public function key() {
		return $this->rowNum;
	}

	/**
	 * @ignore
	 */
	public function next() {
		$this->fetchCSVRow();

		return $this->currentRow;
	}

	/**
	 * @ignore
	 */
	public function valid() {
		return $this->currentRow ? true : false;
	}
}
