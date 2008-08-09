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
 * @todo Finish RestfulServer_Item and RestfulServer_List implementation and re-enable $url_handlers
 * 
 * @package sapphire
 * @subpackage api
 */
class RestfulServer extends Controller {
	static $url_handlers = array(
		'$ClassName/$ID/$Relation' => 'handleAction'
		#'$ClassName/#ID' => 'handleItem',
		#'$ClassName' => 'handleList',
	);

	protected static $api_base = "api/v1/";

	/*
	function handleItem($request) {
		return new RestfulServer_Item(DataObject::get_by_id($request->param("ClassName"), $request->param("ID")));
	}

	function handleList($request) {
		return new RestfulServer_List(DataObject::get($request->param("ClassName"),""));
	}
	*/
	
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
		$relation = (isset($this->urlParams['Relation'])) ? $this->urlParams['Relation'] : null;
		
		// This is a little clumsy and should be improved with the new TokenisedURL that's coming
		if(strpos($relation,'.') !== false) list($relation, $extension) = explode('.', $relation, 2);
		else if(strpos($id,'.') !== false) list($id, $extension) = explode('.', $id, 2);
		else if(strpos($className,'.') !== false) list($className, $extension) = explode('.', $className, 2);
		else $extension = null;

		// Determine mime-type from extension
		$contentMap = array(
			'xml' => 'text/xml',
			'json' => 'text/json',
			'js' => 'text/json',
			'xhtml' => 'text/html',
			'html' => 'text/html',
		);
		$contentType = isset($contentMap[$extension]) ? $contentMap[$extension] : 'text/xml';
		
		switch($requestMethod) {
			case 'GET':
				return $this->getHandler($className, $id, $relation, $contentType);
			
			case 'PUT':
				return $this->putHandler($className, $id, $relation, $contentType);
			
			case 'DELETE':
				return $this->deleteHandler($className, $id, $relation, $contentType);
			
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
	 * 
	 * @param String $className
	 * @param Int $id
	 * @param String $relation
	 * @param String $contentType
	 * @return String The serialized representation of the requested object(s) - usually XML or JSON.
	 */
	protected function getHandler($className, $id, $relation, $contentType) {
		if($id) {
			$obj = DataObject::get_by_id($className, $id);
			if(!$obj) {
				return $this->notFound();
			}
			
			if(!$obj->stat('api_access') || !$obj->canView()) {
				return $this->permissionFailure();
			}
			
			if($relation) {
				if($obj->hasMethod($relation)) $obj = $obj->$relation();
				else return $this->notFound();
			} 
			
		} else {
			$obj = DataObject::get($className, "");
			if(!singleton($className)->stat('api_access')) {
				return $this->permissionFailure();
			}
		}
						
		// TO DO - inspect that Accept header as well.  $_GET['accept'] can still be checked, as it's handy for debugging
		switch($contentType) {
			case "text/xml":
				$this->getResponse()->addHeader("Content-type", "text/xml");
				if($obj instanceof DataObjectSet) return $this->dataObjectSetAsXML($obj);
				else return $this->dataObjectAsXML($obj);

			case "text/json":
				//$this->getResponse()->addHeader("Content-type", "text/json");
				if($obj instanceof DataObjectSet) return $this->dataObjectSetAsJSON($obj);
				else return $this->dataObjectAsJSON($obj);

			case "text/html":
			case "application/xhtml+xml":
				if($obj instanceof DataObjectSet) return $this->dataObjectSetAsXHTML($obj);
				else return $this->dataObjectAsXHTML($obj);
		}
	}
	
	/**
	 * Generate an XML representation of the given {@link DataObject}.
	 * 
	 * @param DataObject $obj
	 * @param $includeHeader Include <?xml ...?> header (Default: true)
	 * @return String XML
	 */
	protected function dataObjectAsXML(DataObject $obj, $includeHeader = true) {
		$className = $obj->class;
		$id = $obj->ID;
		$objHref = Director::absoluteURL(self::$api_base . "$obj->class/$obj->ID");
		
		$json = "";
		if($includeHeader) $json .= "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
		$json .= "<$className href=\"$objHref.xml\">\n";
		foreach($obj->db() as $fieldName => $fieldType) {
			if(is_object($obj->$fieldName)) {
				$json .= $obj->$fieldName->toXML();
			} else {
				$json .= "<$fieldName>" . Convert::raw2xml($obj->$fieldName) . "</$fieldName>\n";
			}
		}
		

		foreach($obj->has_one() as $relName => $relClass) {
			$fieldName = $relName . 'ID';
			if($obj->$fieldName) {
				$href = Director::absoluteURL(self::$api_base . "$relClass/" . $obj->$fieldName);
			} else {
				$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName");
			}
			$json .= "<$relName linktype=\"has_one\" href=\"$href.xml\" id=\"{$obj->$fieldName}\" />\n";
		}

		foreach($obj->has_many() as $relName => $relClass) {
			$json .= "<$relName linktype=\"has_many\" href=\"$objHref/$relName.xml\">\n";
			$items = $obj->$relName();
			foreach($items as $item) {
				//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
				$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
				$json .= "<$relClass href=\"$href.xml\" id=\"{$item->ID}\" />\n";
			}
			$json .= "</$relName>\n";
		}

		foreach($obj->many_many() as $relName => $relClass) {
			$json .= "<$relName linktype=\"many_many\" href=\"$objHref/$relName.xml\">\n";
			$items = $obj->$relName();
			foreach($items as $item) {
				$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
				$json .= "<$relClass href=\"$href.xml\" id=\"{$item->ID}\" />\n";
			}
			$json .= "</$relName>\n";
		}

		$json .= "</$className>";
		
		return $json;
	}

	/**
	 * Generate an XML representation of the given {@link DataObjectSet}.
	 * 
	 * @param DataObjectSet $set
	 * @return String XML
	 */
	protected function dataObjectSetAsXML(DataObjectSet $set) {
		$className = $set->class;
		
		$xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<$className>\n";
		foreach($set as $item) {
			if($item->canView()) $xml .= $this->dataObjectAsXML($item, false);
		}
		$xml .= "</$className>";
		
		return $xml;
	}
	
	/**
	 * Generate an JSON representation of the given {@link DataObject}.
	 * 
	 * @see http://json.org
	 * 
	 * @param DataObject $obj
	 * @return String JSON
	 */
	protected function dataObjectAsJSON(DataObject $obj) {
		$className = $obj->class;
		$id = $obj->ID;
		
		$json = "{\n  className : \"$className\",\n";
		foreach($obj->db() as $fieldName => $fieldType) {
			if(is_object($obj->$fieldName)) {
				$jsonParts[] = "$fieldName : " . $obj->$fieldName->toJSON();
			} else {
				$jsonParts[] = "$fieldName : \"" . Convert::raw2js($obj->$fieldName) . "\"";
			}
		}

		foreach($obj->has_one() as $relName => $relClass) {
			$fieldName = $relName . 'ID';
			if($obj->$fieldName) {
				$href = Director::absoluteURL(self::$api_base . "$relClass/" . $obj->$fieldName);
			} else {
				$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName");
			}
			$jsonParts[] = "$relName : { className : \"$relClass\", href : \"$href.json\", id : \"{$obj->$fieldName}\" }";
		}

		foreach($obj->has_many() as $relName => $relClass) {
			$jsonInnerParts = array();
			$items = $obj->$relName();
			foreach($items as $item) {
				//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
				$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
				$jsonInnerParts[] = "{ className : \"$relClass\", href : \"$href.json\", id : \"{$obj->$fieldName}\" }";
			}
			$jsonParts[] = "$relName : [\n    " . implode(",\n    ", $jsonInnerParts) . "  \n  ]";
		}

		foreach($obj->many_many() as $relName => $relClass) {
			$jsonInnerParts = array();
			$items = $obj->$relName();
			foreach($items as $item) {
				//$href = Director::absoluteURL(self::$api_base . "$className/$id/$relName/$item->ID");
				$href = Director::absoluteURL(self::$api_base . "$relClass/$item->ID");
				$jsonInnerParts[] = "    { className : \"$relClass\", href : \"$href.json\", id : \"{$obj->$fieldName}\" }";
			}
			$jsonParts[] = "$relName : [\n    " . implode(",\n    ", $jsonInnerParts) . "\n  ]";
		}
		
		return "{\n  " . implode(",\n  ", $jsonParts) . "\n}";
	}	

	/**
	 * Generate an JSON representation of the given {@link DataObjectSet}.
	 * 
	 * @param DataObjectSet $set
	 * @return String JSON
	 */
	protected function dataObjectSetAsJSON(DataObjectSet $set) {
		$jsonParts = array();
		foreach($set as $item) {
			if($item->canView()) $jsonParts[] = $this->dataObjectAsJSON($item);
		}
		return "[\n" . implode(",\n", $jsonParts) . "\n]";
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


/**
 * Restful server handler for a DataObjectSet
 */
class RestfulServer_List {
	static $url_handlers = array(
		'#ID' => 'handleItem',
	);

	function __construct($list) {
		$this->list = $list;
	}
	
	function handleItem($request) {
		return new RestulServer_Item($this->list->getById($request->param('ID')));
	}
}

/**
 * Restful server handler for a single DataObject
 */
class RestfulServer_Item {
	static $url_handlers = array(
		'$Relation' => 'handleRelation',
	);

	function __construct($item) {
		$this->item = $item;
	}
	
	function handleRelation($request) {
		$funcName = $request('Relation');
		$relation = $this->item->$funcName();

		if($relation instanceof DataObjectSet) return new RestfulServer_List($relation);
		else return new RestfulServer_Item($relation);
	}
}
