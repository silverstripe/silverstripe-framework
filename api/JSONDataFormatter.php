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
	 * Generate an XML representation of the given {@link DataObject}.
	 * 
	 * @param DataObject $obj
	 * @param $includeHeader Include <?xml ...?> header (Default: true)
	 * @return String XML
	 */
	public function convertDataObject(DataObjectInterface $obj, $fields = null, $relations = null) {
		$className = $obj->class;
		$id = $obj->ID;
		
		$json = "{\n  \"className\" : \"$className\",\n";
		foreach($this->getFieldsForObj($obj) as $fieldName => $fieldType) {
			// Field filtering
			if($fields && !in_array($fieldName, $fields)) continue;

			$fieldValue = $obj->$fieldName;
			if(is_object($fieldValue) && is_subclass_of($fieldValue, 'Object') && $fieldValue->hasMethod('toJSON')) {
				$jsonParts[] = "\"$fieldName\" : " . $fieldValue->toJSON();
			} else {
				$jsonParts[] = "\"$fieldName\" : " . Convert::raw2json($fieldValue);
			}
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
				$jsonParts[] = "\"$relName\" : { \"className\" : \"$relClass\", \"href\" : \"$href.json\", \"id\" : \"{$obj->$fieldName}\" }";
			}
	
			foreach($obj->has_many() as $relName => $relClass) {
				if(!singleton($relClass)->stat('api_access')) continue;
				
				// Field filtering
				if($fields && !in_array($relName, $fields)) continue;
				if($this->customRelations && !in_array($relName, $this->customRelations)) continue;

				$jsonInnerParts = array();
				$items = $obj->$relName();
				foreach($items as $item) {
					//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
					$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
					$jsonInnerParts[] = "{ \"className\" : \"$relClass\", \"href\" : \"$href.json\", \"id\" : \"{$obj->$fieldName}\" }";
				}
				$jsonParts[] = "\"$relName\" : [\n    " . implode(",\n    ", $jsonInnerParts) . "  \n  ]";
			}
	
			foreach($obj->many_many() as $relName => $relClass) {
				if(!singleton($relClass)->stat('api_access')) continue;
				
				// Field filtering
				if($fields && !in_array($relName, $fields)) continue;
				if($this->customRelations && !in_array($relName, $this->customRelations)) continue;

				$jsonInnerParts = array();
				$items = $obj->$relName();
				foreach($items as $item) {
					//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
					$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
					$jsonInnerParts[] = "    { \"className\" : \"$relClass\", \"href\" : \"$href.json\", \"id\" : \"{$obj->$fieldName}\" }";
				}
				$jsonParts[] = "\"$relName\" : [\n    " . implode(",\n    ", $jsonInnerParts) . "\n  ]";
			}
		}
		
		return "{\n  " . implode(",\n  ", $jsonParts) . "\n}";	}

	/**
	 * Generate an XML representation of the given {@link DataObjectSet}.
	 * 
	 * @param DataObjectSet $set
	 * @return String XML
	 */
	public function convertDataObjectSet(DataObjectSet $set, $fields = null) {
		$jsonParts = array();
		foreach($set as $item) {
			if($item->canView()) $jsonParts[] = $this->convertDataObject($item, $fields);
		}
		$json = "{\n";
		$json .= '"totalSize": ';
		$json .= (is_numeric($this->totalSize)) ? $this->totalSize : 'null';
		$json .= ",\n";
		$json .= "\"items\": [\n" . implode(",\n", $jsonParts) . "\n]\n";
		$json .= "}\n";

		return $json;
	}
	
	public function convertStringToArray($strData) {
		return Convert::json2array($strData);
	}
	
}