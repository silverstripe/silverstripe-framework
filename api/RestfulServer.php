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
 * You can trigger searches based on the fields specified on {@link DataObject::searchable_fields} and passed
 * through {@link DataObject::getDefaultSearchContext()}. Just add a key-value pair with the search-term
 * to the url, e.g. /api/v1/(ClassName)/?Title=mytitle 
 * 
 * Other url-modifiers:
 * - &limit=<numeric>: Limit the result set
 * - &relationdepth=<numeric>: Displays links to existing has-one and has-many relationships to a certain depth (Default: 1)
 * - &fields=<string>: Comma-separated list of fields on the output object (defaults to all database-columns)
 * 
 * @todo Finish RestfulServer_Item and RestfulServer_List implementation and re-enable $url_handlers
 * @todo Make SearchContext specification customizeable for each class
 * @todo Allow for range-searches (e.g. on Created column)
 */
class RestfulServer extends Controller {
	static $url_handlers = array(
		'$ClassName/$ID/$Relation' => 'handleAction'
		#'$ClassName/#ID' => 'handleItem',
		#'$ClassName' => 'handleList',
	);

	protected static $api_base = "api/v1/";

	/**
	 * If no extension is given in the request, resolve to this extension
	 * (and subsequently the {@link self::$default_mimetype}.
	 *
	 * @var string
	 */
	protected static $default_extension = "xml";
	
	/**
	 * If no extension is given, resolve the request to this mimetype.
	 *
	 * @var string
	 */
	protected static $default_mimetype = "text/xml";
	
	/**
	 * Maps common extensions to their mimetype representations.
	 *
	 * @var array
	 */
	protected static $mimetype_map = array(
		'xml' => 'text/xml',
		'json' => 'text/json',
		'js' => 'text/json',
		'xhtml' => 'text/html',
		'html' => 'text/html',
	);
	
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
		
		switch($requestMethod) {
			case 'GET':
				return $this->getHandler($className, $id, $relation);
			
			case 'PUT':
				return $this->putHandler($className, $id, $relation);
			
			case 'DELETE':
				return $this->deleteHandler($className, $id, $relation);
			
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
	 * @return String The serialized representation of the requested object(s) - usually XML or JSON.
	 */
	protected function getHandler($className, $id, $relation) {
		$sort = array(
			'sort' => $this->request->getVar('sort'),
			'dir' => $this->request->getVar('dir')
		);
		$limit = array(
			'start' => $this->request->getVar('start'),
			'limit' => $this->request->getVar('limit')
		);
		
		$formatter = $this->getDataFormatter();

		if($id) {
			$obj = DataObject::get_by_id($className, $id);
			if(!$obj) {
				return $this->notFound();
			}
			
			if(!$obj->stat('api_access') || !$obj->canView()) {
				return $this->permissionFailure();
			}
			
			if($relation) {
				if($relationClass = $obj->many_many($relation)) {
					$query = $obj->getManyManyComponentsQuery($relation);
				} elseif($relationClass = $obj->has_many($relation)) {
					$query = $obj->getComponentsQuery($relation);
				} elseif($relationClass = $obj->has_one($relation)) {
					$query = null;
				} elseif($obj->hasMethod("{$relation}Query")) {
					// @todo HACK Switch to ComponentSet->getQuery() once we implement it (and lazy loading)
					$query = $obj->{"{$relation}Query"}(null, $sort, null, $limit);
					$relationClass = $obj->{"{$relation}Class"}();
				} else {
					return $this->notFound();
				}
				
				// get all results
				$obj = $this->search($relationClass, $this->request->getVars(), $sort, $limit, $query);
				if(!$obj) $obj = new DataObjectSet();
			} 
			
		} else {
			if(!singleton($className)->stat('api_access')) {
				return $this->permissionFailure();
			}
			$obj = $this->search($className, $this->request->getVars(), $sort, $limit);
			// show empty serialized result when no records are present
			if(!$obj) $obj = new DataObjectSet();
		}
		
		if($obj instanceof DataObjectSet) return $formatter->convertDataObjectSet($obj);
		else return $formatter->convertDataObject($obj);
	}
	
	/**
	 * Uses the default {@link SearchContext} specified through
	 * {@link DataObject::getDefaultSearchContext()} to augument
	 * an existing query object (mostly a component query from {@link DataObject})
	 * with search clauses. 
	 * 
	 * @todo Allow specifying of different searchcontext getters on model-by-model basis
	 *
	 * @param string $className
	 * @param array $params
	 * @return DataObjectSet
	 */
	protected function search($className, $params = null, $sort = null, $limit = null, $existingQuery = null) {
		if(singleton($className)->hasMethod('getRestfulSearchContext')) {
			$searchContext = singleton($className)->{'getRestfulSearchContext'}();
		} else {
			$searchContext = singleton($className)->getDefaultSearchContext();
		}
		$query = $searchContext->getQuery($params, $sort, $limit, $existingQuery);

		return singleton($className)->buildDataObjectSet($query->execute());
	}

	protected function getDataFormatter() {
		$extension = $this->request->getExtension();
		
		// Determine mime-type from extension
		$contentType = isset(self::$mimetype_map[$extension]) ? self::$mimetype_map[$extension] : self::$default_mimetype;
		
		if(!$extension) $extension = self::$default_extension;
		$formatter = DataFormatter::for_extension($extension); //$this->dataFormatterFromMime($contentType);
		if($customFields = $this->request->getVar('fields')) $formatter->setCustomFields(explode(',',$customFields));
		$relationDepth = $this->request->getVar('relationdepth');
		if(is_numeric($relationDepth)) $formatter->relationDepth = (int)$relationDepth;
		
		return $formatter;		
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
