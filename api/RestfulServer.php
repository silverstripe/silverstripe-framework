<?php

/**
 * Sapphire's generic RESTful server.
 * 
 * NOTE: This is an alpha module and its API is currently very volatile.  It functions, but it might change radically
 * before the next release!
 * 
 * This class gives your application a RESTful API for free.  All you have to do is define static $api_access = true on
 * the appropriate DataObjects.  You will need to ensure that all of your data manipulation and security is defined in
 * your model layer (ie, the DataObject classes) and not in your Controllers.  This is the recommended design for Sapphire
 * applications.
 * 
 *  - GET /api/v1/(ClassName)/(ID) - gets a database record
 *  - GET /api/v1/(ClassName)/(ID)/(Relation) - get all of the records linked to this database record by the given reatlion (NOT IMPLEMENTED YET)
 *  - GET /api/v1/(ClassName)?(Field)=(Val)&(Field)=(Val) - searches for matching database records (NOT IMPLEMENTED YET)
 * 
 *  - PUT /api/v1/(ClassName)/(ID) - updates a database record (NOT IMPLEMENTED YET)
 *  - PUT /api/v1/(ClassName)/(ID)/(Relation) - updates a relation, replacing the existing record(s) (NOT IMPLEMENTED YET)
 *  - POST /api/v1/(ClassName)/(ID)/(Relation) - updates a relation, appending to the existing record(s) (NOT IMPLEMENTED YET)
 * 
 *  - DELETE /api/v1/(ClassName)/(ID) - deletes a database record (NOT IMPLEMENTED YET)
 *  - DELETE /api/v1/(ClassName)/(ID)/(Relation)/(ForeignID) - remove the relationship between two database records, but don't actually delete the foreign object (NOT IMPLEMENTED YET)
 *
 *  - POST /api/v1/(ClassName)/(ID)/(MethodName) - executes a method on the given object (e.g, publish)
 *
 * @package sapphire
 * @subpackage api
 */
class RestfulServer extends Controller {
	protected static $api_base = "api/v1/";
	
	/**
	 * This handler acts as the switchboard for the controller.
	 * Since no $Action url-param is set, all requests are sent here.
	 */
	function index() {
		ContentNegotiator::disable();

		$requestMethod = $_SERVER['REQUEST_METHOD'];
		
		if(!isset($this->urlParams['ClassName'])) return $this->notFound();
		$className = $this->urlParams['ClassName'];
		$id = (isset($this->urlParams['ID'])) ? $this->urlParams['ID'] : null;
		
		switch($requestMethod) {
			case 'GET':
				return $this->getHandler($className, $id);
			
			case 'PUT':
				return $this->putHandler($className, $id);
			
			case 'DELETE':
				return $this->deleteHandler($className, $id);
			
			case 'POST':
		}
	}
	
	/**
	 * Handler for object read.
	 * 
	 * The data object will be returned in the following format:
	 *
	 * <ClassName>
	 *   <FieldName>Value</FieldName>
	 *   ...
	 *   <HasOneRelName id="ForeignID" href="LinkToForeignRecordInAPI" />
	 *   ...
	 *   <HasManyRelName>
	 *     <ForeignClass id="ForeignID" href="LinkToForeignRecordInAPI" />
	 *     <ForeignClass id="ForeignID" href="LinkToForeignRecordInAPI" />
	 *   </HasManyRelName>
	 *   ...
	 *   <ManyManyRelName>
	 *     <ForeignClass id="ForeignID" href="LinkToForeignRecordInAPI" />
	 *     <ForeignClass id="ForeignID" href="LinkToForeignRecordInAPI" />
	 *   </ManyManyRelName>
	 * </ClassName>
	 *
	 * Access is controlled by two variables:
	 * 
	 *   - static $api_access must be set. This enables the API on a class by class basis
	 *   - $obj->canView() must return true. This lets you implement record-level security
	 */
	protected function getHandler($className, $id) {
		$obj = DataObject::get_by_id($className, $id);
		if(!$obj) {
			return $this->notFound();
		}
		
		// TO DO - inspect that Accept header as well.  $_GET['accept'] can still be checked, as it's handy for debugging
		$contentType = isset($_GET['accept']) ? $_GET['accept'] : 'text/xml';
		
		if($obj->stat('api_access') && $obj->canView()) {
			switch($contentType) {
				case "text/xml":
					$this->getResponse()->addHeader("Content-type", "text/xml");
					return $this->dataObjectAsXML($obj);

				case "text/json":
					$this->getResponse()->addHeader("Content-type", "text/json");
					return $this->dataObjectAsJSON($obj);

				case "text/html":
				case "application/xhtml+xml":
					$this->getResponse()->addHeader("Content-type", "text/json");
					return $this->dataObjectAsXHTML($obj);
			}
		} else {
			return $this->permissionFailure();
		}
	}
	
	/**
	 * Generate an XML representation of the given DataObject.
	 */
	protected function dataObjectAsXML(DataObject $obj) {
		$className = $obj->class;
		$id = $obj->ID;
		
		$json = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<$className>\n";
		foreach($obj->db() as $fieldName => $fieldType) {
			$json .= "<$fieldName>" . Convert::raw2xml($obj->$fieldName) . "</$fieldName>\n";
		}

		foreach($obj->has_one() as $relName => $relClass) {
			$fieldName = $relName . 'ID';
			if($obj->$fieldName) {
				$href = Director::absoluteURL(self::$api_base . "$relClass/" . $obj->$fieldName);
			} else {
				$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName");
			}
			$json .= "<$relName linktype=\"has_one\" href=\"$href\" id=\"{$obj->$fieldName}\" />\n";
		}

		foreach($obj->has_many() as $relName => $relClass) {
			$json .= "<$relName linktype=\"has_many\">\n";
			$items = $obj->$relName();
			foreach($items as $item) {
				//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
				$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
				$json .= "<$relClass href=\"$href\" id=\"{$item->ID}\" />\n";
			}
			$json .= "</$relName>\n";
		}

		foreach($obj->many_many() as $relName => $relClass) {
			$json .= "<$relName linktype=\"many_many\">\n";
			$items = $obj->$relName();
			foreach($items as $item) {
				//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
				$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
				$json .= "<$relClass href=\"$href\" id=\"{$item->ID}\" />\n";
			}
			$json .= "</$relName>\n";
		}

		$json .= "</$className>";
		
		return $json;
	}

	
	/**
	 * Generate an XML representation of the given DataObject.
	 */
	protected function dataObjectAsJSON(DataObject $obj) {
		$className = $obj->class;
		$id = $obj->ID;
		
		$json = "{\n  className : \"$className\",\n";
		foreach($obj->db() as $fieldName => $fieldType) {
			$jsonParts[] = "$fieldName : \"" . Convert::raw2js($obj->$fieldName) . "\"";
		}

		foreach($obj->has_one() as $relName => $relClass) {
			$fieldName = $relName . 'ID';
			if($obj->$fieldName) {
				$href = Director::absoluteURL(self::$api_base . "$relClass/" . $obj->$fieldName);
			} else {
				$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName");
			}
			$jsonParts[] = "$relName : { className : \"$relClass\", href : \"$href\", id : \"{$obj->$fieldName}\" }";
		}

		foreach($obj->has_many() as $relName => $relClass) {
			$jsonInnerParts = array();
			$items = $obj->$relName();
			foreach($items as $item) {
				//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
				$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
				$jsonInnerParts[] = "{ className : \"$relClass\", href : \"$href\", id : \"{$obj->$fieldName}\" }";
			}
			$jsonParts[] = "$relName : [\n    " . implode(",\n    ", $jsonInnerParts) . "  \n  ]";
		}

		foreach($obj->many_many() as $relName => $relClass) {
			$jsonInnerParts = array();
			$items = $obj->$relName();
			foreach($items as $item) {
				//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
				$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
				$jsonInnerParts[] = "    { className : \"$relClass\", href : \"$href\", id : \"{$obj->$fieldName}\" }";
			}
			$jsonParts[] = "$relName : [\n    " . implode(",\n    ", $jsonInnerParts) . "\n  ]";
		}
		
		return "{\n  " . implode(",\n  ", $jsonParts) . "\n}";
	}	
	/**
	 * Handler for object delete
	 */
	protected function deleteHandler($className, $id) {
		if($id) {
			$obj = DataObject::get_by_id($className, $id);
			if($obj->stat('api_access') && $obj->canDelete()) {
				$obj->delete();
			} else {
				return $this->permissionFailure();
			}
			
		}
	}

	/**
	 * Handler for object write
	 */
	protected function putHandler($className, $id) {
		return $this->permissionFailure();
	}

	/**
	 * Handler for object append / method call
	 */
	protected function postHandler($className, $id) {
		return $this->permissionFailure();
	}


	protected function permissionFailure() {
		// return a 401
		$this->getResponse()->setStatusCode(403);
		return "You don't have access to this item through the API.";
	}

	protected function notFound() {
		// return a 404
		$this->getResponse()->setStatusCode(404);
		return "That object wasn't found";
	}
	
}