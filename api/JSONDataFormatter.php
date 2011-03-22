<?php
/**
 * @package sapphire
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
		
		$serobj = new EmptyJSONObject();

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
				$serobj->$relName = new EmptyJSONObject(array("className" => $relClass, "href" => "$href.json", "id" => $obj->$fieldName));
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
					$innerParts[] = new EmptyJSONObject(array("className" => $relClass, "href" => "$href.json", "id" => $obj->$fieldName));
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
					$innerParts[] = new EmptyJSONObject(array("className" => $relClass, "href" => "$href.json", "id" => $obj->$fieldName));
				}
				$serobj->$relName = $innerParts;
			}
		}
		
		return $serobj;
	}

	/**
	 * Generate a JSON representation of the given {@link DataObjectSet}.
	 * 
	 * @param DataObjectSet $set
	 * @return String XML
	 */
	public function convertDataObjectSet(DataObjectSet $set, $fields = null) {
		$items = array();
		foreach ($set as $do) $items[] = $this->convertDataObjectToJSONObject($do, $fields);

		$serobj = new EmptyJSONObject(array(
			"totalSize" => (is_numeric($this->totalSize)) ? $this->totalSize : null,
			"items" => $items
		));

		return Convert::array2json($serobj);
	}
	
	public function convertStringToArray($strData) {
		return Convert::json2array($strData);
	}
	
}

/**
 * Empty class with no behaviour or properties, so we can give plain objects to the json encoder.
 */
class EmptyJSONObject {
	/**
	 * @param  $args		An assoc array used to dynamically initialise properties of the new object.
	 * @return void
	 */
	function __construct($args = null) {
		if ($args) foreach($args as $name => $value) $this->$name = $value;
	}
}

?>