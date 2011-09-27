<?php

/**
 * Description of Datagrid
 *
 */
class Datagrid extends FormField {

	/**
	 *
	 * @var SS_list
	 */
	protected $datasource = null;

	/**
	 * Creates a new datagrid field
	 *
	 * @param string $name
	 * @param string $title
	 * @param SS_list $source
	 * @param Form $form 
	 */
	function __construct($name, $title = null, SS_list $source = null, Form $form = null) {
		parent::__construct($name, $title, null, $form);
	}

	/**
	 * Set the datasource
	 *
	 * @param SS_List $datasource
	 */
	public function setDatasource(SS_List $datasource ) {
		$this->datasource = $datasource;
	}

	/**
	 * Get the datasource
	 *
	 * @return SS_list
	 */
	public function getDatasource() {
		return $this->datasource;
	}

	/**
	 * Get the headers or column names for this grid
	 *
	 * The returning array will have the format of
	 * array(
	 *     'FirstName' => 'First name',
	 *     'Description' => 'A nice description'
	 * )
	 *
	 * @return array 
	 */
	public function getHeaders() {

		if($this->datasource instanceof DataList ) {
			return singleton($this->datasource->dataClass)->summaryFields();
		} else {
			$firstItem = $this->datasource->first();
			if(!$firstItem) {
				return array();
			}
			return array_combine(array_keys($firstItem),array_keys($firstItem));
		}
	}
}
