<?php
/**
 * @package framework
 * @subpackage formatters
 */
class JSONDataFormatter extends DataFormatter {
	/**
	 * @todo pass this from the API to the data formatter somehow
	 */
	static $api_base = "api/v1/";
	
	protected $outputContentType = 'application/json';
	
	public function supportedExtensions() {
		return array(
			'json', 
			'js'
		);
	}

	public function supportedMimeTypes() {
		return array(
			'application/json', 
			'text/x-json'
		);
	}
	
	/**
	 * Generate a JSON representation of the given {@link DataObject}.
	 *
	 * @param DataObject $obj	The object
	 * @param Array $fields		If supplied, only fields in the list will be returned
	 * @param $relations		Not used
	 * @return String JSON
	 */
	public function convertDataObject(DataObjectInterface $obj, $fields = null, $relations = null) {
		return Convert::array2json($this->convertDataObjectToJSONObject($obj, $fields, $relations));
	}

	/**
	 * Internal function to do the conversion of a single data object. It builds an empty object and dynamically
	 * adds the properties it needs to it. If it's done as a nested array, json_encode or equivalent won't use
	 * JSON object notation { ... }.
	 * @param DataObjectInterface $obj
	 * @param  $fields
	 * @param  $relations
	 * @return EmptyJSONObject
	 */
	public function convertDataObjectToJSONObject(DataObjectInterface $obj, $fields = null, $relations = null) {
		$className = $obj->class;
		$id = $obj->ID;

		$serobj = ArrayData::array_to_object();

		foreach($this->getFieldsForObj($obj) as $fieldName => $fieldType) {
			// Field filtering
			if($fields && !in_array($fieldName, $fields)) continue;

			$fieldValue = $obj->$fieldName;
			$serobj->$fieldName = $fieldValue;
		}

		if($this->relationDepth > 0) {
			foreach($obj->has_one() as $relName => $relClass) {
				if(!singleton($relClass)->stat('api_access')) continue;
				
				// Field filtering
				if($fields && !in_array($relName, $fields)) continue;
				if($this->customRelations && !in_array($relName, $this->customRelations)) continue;

				$fieldName = $relName . 'ID';
				if($obj->$fieldName) {
					$href = Director::absoluteURL(self::$api_base . "$relClass/" . $obj->$fieldName);
				} else {
					$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName");
				}
				$serobj->$relName = ArrayData::array_to_object(array("className" => $relClass, "href" => "$href.json", "id" => $obj->$fieldName));
			}

			foreach($obj->has_many() as $relName => $relClass) {
				if(!singleton($relClass)->stat('api_access')) continue;

				// Field filtering
				if($fields && !in_array($relName, $fields)) continue;
				if($this->customRelations && !in_array($relName, $this->customRelations)) continue;

				$innerParts = array();
				$items = $obj->$relName();
				foreach($items as $item) {
					//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
					$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
					$innerParts[] = ArrayData::array_to_object(array("className" => $relClass, "href" => "$href.json", "id" => $obj->$fieldName));
				}
				$serobj->$relName = $innerParts;
			}
	
			foreach($obj->many_many() as $relName => $relClass) {
				if(!singleton($relClass)->stat('api_access')) continue;
				
				// Field filtering
				if($fields && !in_array($relName, $fields)) continue;
				if($this->customRelations && !in_array($relName, $this->customRelations)) continue;

				$innerParts = array();
				$items = $obj->$relName();
				foreach($items as $item) {
					//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
					$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
					$innerParts[] = ArrayData::array_to_object(array("className" => $relClass, "href" => "$href.json", "id" => $obj->$fieldName));
				}
				$serobj->$relName = $innerParts;
			}
		}
		
		return $serobj;
	}

	/**
	 * Generate a JSON representation of the given {@link SS_List}.
	 * 
	 * @param SS_List $set
	 * @return String XML
	 */
	public function convertDataObjectSet(SS_List $set, $fields = null) {
		$items = array();
		foreach ($set as $do) $items[] = $this->convertDataObjectToJSONObject($do, $fields);

		$serobj = ArrayData::array_to_object(array(
			"totalSize" => (is_numeric($this->totalSize)) ? $this->totalSize : null,
			"items" => $items
		));

		return Convert::array2json($serobj);
	}
	
	public function convertStringToArray($strData) {
		return Convert::json2array($strData);
	}

}
