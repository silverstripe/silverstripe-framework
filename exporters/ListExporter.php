<?php

/**
 * Interface for converting SS_List into various formats
 * 
 * @package exporters
 */
interface SS_ListExporter{
	
	/**
	 * Convert a SS_List into a particular format
	 * @return string
	 */
	public function export();

}
