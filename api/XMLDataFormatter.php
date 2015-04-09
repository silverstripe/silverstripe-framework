<?php
/**
 * @package framework
 * @subpackage formatters
 */
class XMLDataFormatter extends DataFormatter {

	/**
	 * @config
	 * @todo pass this from the API to the data formatter somehow
	 */
	private static $api_base = "api/v1/";

	protected $outputContentType = 'text/xml';

	public function supportedExtensions() {
		return array(
			'xml'
		);
	}

	public function supportedMimeTypes() {
		return array(
			'text/xml',
			'application/xml',
		);
	}

	/**
	 * Generate an XML representation of the given {@link DataObject}.
	 *
	 * @param DataObject $obj
	 * @param $includeHeader Include <?xml ...?> header (Default: true)
	 * @return String XML
	 */
	public function convertDataObject(DataObjectInterface $obj, $fields = null) {
		$response = Controller::curr()->getResponse();
		if($response) {
			$response->addHeader("Content-Type", "text/xml");
		}

		return "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n" . $this->convertDataObjectWithoutHeader($obj, $fields);
	}

	public function convertDataObjectWithoutHeader(DataObject $obj, $fields = null, $relations = null) {
		$className = $obj->class;
		$id = $obj->ID;
		$objHref = Director::absoluteURL($this->config()->api_base . "$obj->class/$obj->ID");

		$xml = "<$className href=\"$objHref.xml\">\n";
		foreach($this->getFieldsForObj($obj) as $fieldName => $fieldType) {
			// Field filtering
			if($fields && !in_array($fieldName, $fields)) continue;
			$fieldValue = $obj->obj($fieldName)->forTemplate();
			if(!mb_check_encoding($fieldValue,'utf-8')) $fieldValue = "(data is badly encoded)";

			if(is_object($fieldValue) && is_subclass_of($fieldValue, 'Object') && $fieldValue->hasMethod('toXML')) {
				$xml .= $fieldValue->toXML();
			} else {
				if('HTMLText' == $fieldType) {
					// Escape HTML values using CDATA
					$fieldValue = sprintf('<![CDATA[%s]]>', str_replace(']]>', ']]]]><![CDATA[>', $fieldValue));
				} else {
					$fieldValue = Convert::raw2xml($fieldValue);
				}
				$xml .= "<$fieldName>$fieldValue</$fieldName>\n";
			}
		}

		if($this->relationDepth > 0) {
			foreach($obj->hasOne() as $relName => $relClass) {
				if(!singleton($relClass)->stat('api_access')) continue;

				// Field filtering
				if($fields && !in_array($relName, $fields)) continue;
				if($this->customRelations && !in_array($relName, $this->customRelations)) continue;

				$fieldName = $relName . 'ID';
				if($obj->$fieldName) {
					$href = Director::absoluteURL($this->config()->api_base . "$relClass/" . $obj->$fieldName);
				} else {
					$href = Director::absoluteURL($this->config()->api_base . "$className/$id/$relName");
				}
				$xml .= "<$relName linktype=\"has_one\" href=\"$href.xml\" id=\"" . $obj->$fieldName
					. "\"></$relName>\n";
			}

			foreach($obj->hasMany() as $relName => $relClass) {
				if(!singleton($relClass)->stat('api_access')) continue;

				// Field filtering
				if($fields && !in_array($relName, $fields)) continue;
				if($this->customRelations && !in_array($relName, $this->customRelations)) continue;

				$xml .= "<$relName linktype=\"has_many\" href=\"$objHref/$relName.xml\">\n";
				$items = $obj->$relName();
				if ($items) {
					foreach($items as $item) {
						$href = Director::absoluteURL($this->config()->api_base . "$relClass/$item->ID");
						$xml .= "<$relClass href=\"$href.xml\" id=\"{$item->ID}\"></$relClass>\n";
					}
				}
				$xml .= "</$relName>\n";
			}

			foreach($obj->manyMany() as $relName => $relClass) {
				if(!singleton($relClass)->stat('api_access')) continue;

				// Field filtering
				if($fields && !in_array($relName, $fields)) continue;
				if($this->customRelations && !in_array($relName, $this->customRelations)) continue;

				$xml .= "<$relName linktype=\"many_many\" href=\"$objHref/$relName.xml\">\n";
				$items = $obj->$relName();
				if ($items) {
					foreach($items as $item) {
						$href = Director::absoluteURL($this->config()->api_base . "$relClass/$item->ID");
						$xml .= "<$relClass href=\"$href.xml\" id=\"{$item->ID}\"></$relClass>\n";
					}
				}
				$xml .= "</$relName>\n";
			}
		}

		$xml .= "</$className>";

		return $xml;
	}

	/**
	 * Generate an XML representation of the given {@link SS_List}.
	 *
	 * @param SS_List $set
	 * @return String XML
	 */
	public function convertDataObjectSet(SS_List $set, $fields = null) {
		Controller::curr()->getResponse()->addHeader("Content-Type", "text/xml");
		$className = $set->class;

		$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		$xml .= (is_numeric($this->totalSize)) ? "<$className totalSize=\"{$this->totalSize}\">\n" : "<$className>\n";
		foreach($set as $item) {
			$xml .= $this->convertDataObjectWithoutHeader($item, $fields);
		}
		$xml .= "</$className>";

		return $xml;
	}

	public function convertStringToArray($strData) {
		return Convert::xml2array($strData);
	}
}
