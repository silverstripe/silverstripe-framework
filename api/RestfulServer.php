<?php
/**
 * Sapphire's generic RESTful server.
 * 
 * This class gives your application a RESTful API for free.  All you have to do is define static $api_access = true on
 * the appropriate DataObjects.  You will need to ensure that all of your data manipulation and security is defined in
 * your model layer (ie, the DataObject classes) and not in your Controllers.  This is the recommended design for Sapphire
 * applications.
 * 
 * Enabling restful access on a model will also enable a SOAP API, see {@link SOAPModelAccess}.
 * 
 * Example DataObject with simple api access, giving full access to all object properties and relations,
 * unless explicitly controlled through model permissions.
 * <code>
 * class Article extends DataObject {
 * 	static $db = array('Title'=>'Text','Published'=>'Boolean');
 * 	static $api_access = true;
 * }
 * </code>
 *
 * * Example DataObject with advanced api access, limiting viewing and editing to Title attribute only:
 * <code>
 * class Article extends DataObject {
 * 	static $db = array('Title'=>'Text','Published'=>'Boolean');
 * 	static $api_access = array(
 * 		'view' => array('Title'),
 * 		'edit' => array('Title'),
 * 	);
 * }
 * </code>
 * 
 * <b>Supported operations</b>
 * 
 *  - GET /api/v1/(ClassName)/(ID) - gets a database record
 *  - GET /api/v1/(ClassName)/(ID)/(Relation) - get all of the records linked to this database record by the given reatlion
 *  - GET /api/v1/(ClassName)?(Field)=(Val)&(Field)=(Val) - searches for matching database records
 *  - POST /api/v1/(ClassName) - create a new database record
 *  - PUT /api/v1/(ClassName)/(ID) - updates a database record
 *  - PUT /api/v1/(ClassName)/(ID)/(Relation) - updates a relation, replacing the existing record(s) (NOT IMPLEMENTED YET)
 *  - POST /api/v1/(ClassName)/(ID)/(Relation) - updates a relation, appending to the existing record(s) (NOT IMPLEMENTED YET)
 * 
 *  - DELETE /api/v1/(ClassName)/(ID) - deletes a database record (NOT IMPLEMENTED YET)
 *  - DELETE /api/v1/(ClassName)/(ID)/(Relation)/(ForeignID) - remove the relationship between two database records, but don't actually delete the foreign object (NOT IMPLEMENTED YET)
 *
 *  - POST /api/v1/(ClassName)/(ID)/(MethodName) - executes a method on the given object (e.g, publish)
 * 
 * <b>Search</b>
 * 
 * You can trigger searches based on the fields specified on {@link DataObject::searchable_fields} and passed
 * through {@link DataObject::getDefaultSearchContext()}. Just add a key-value pair with the search-term
 * to the url, e.g. /api/v1/(ClassName)/?Title=mytitle.
 * 
 * <b>Other url-modifiers</b>
 * 
 * - &limit=<numeric>: Limit the result set
 * - &relationdepth=<numeric>: Displays links to existing has-one and has-many relationships to a certain depth (Default: 1)
 * - &fields=<string>: Comma-separated list of fields on the output object (defaults to all database-columns).
 *   Handy to limit output for bandwidth and performance reasons.
 * - &sort=<myfield>&dir=<asc|desc>
 * - &add_fields=<string>: Comma-separated list of additional fields, for example dynamic getters.
 * 
 * <b>Access control</b>
 *
 * Access control is implemented through the usual Member system with Basicauth authentication only.
 * By default, you have to bear the ADMIN permission to retrieve or send any data.
 *
 * You should override the following built-in methods to customize permission control on a
 * class- and object-level:
 * - {@link DataObject::canView()}
 * - {@link DataObject::canEdit()}
 * - {@link DataObject::canDelete()}
 * - {@link DataObject::canCreate()}
 * See {@link DataObject} documentation for further details.
 * 
 * You can specify the character-encoding for any input on the HTTP Content-Type.
 * At the moment, only UTF-8 is supported. All output is made in UTF-8 regardless of Accept headers.
 * 
 * @todo Finish RestfulServer_Item and RestfulServer_List implementation and re-enable $url_handlers
 * @todo Implement PUT/POST/DELETE for relations
 * @todo Access-Control for relations (you might be allowed to view Members and Groups, but not their relation with each other)
 * @todo Make SearchContext specification customizeable for each class
 * @todo Allow for range-searches (e.g. on Created column)
 * @todo Allow other authentication methods (currently only HTTP BasicAuth)
 * @todo Filter relation listings by $api_access and canView() permissions
 * @todo Exclude relations when "fields" are specified through URL (they should be explicitly requested in this case)
 * @todo Custom filters per DataObject subclass, e.g. to disallow showing unpublished pages in SiteTree/Versioned/Hierarchy
 * @todo URL parameter namespacing for search-fields, limit, fields, add_fields (might all be valid dataobject properties)
 *       e.g. you wouldn't be able to search for a "limit" property on your subclass as its overlayed with the search logic
 * @todo i18n integration (e.g. Page/1.xml?lang=de_DE)
 * @todo Access to decoratable methods/relations like SiteTree/1/Versions or SiteTree/1/Version/22
 * @todo Respect $api_access array notation in search contexts
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

	/**
	 * If no extension is given in the request, resolve to this extension
	 * (and subsequently the {@link self::$default_mimetype}.
	 *
	 * @var string
	 */
	public static $default_extension = "xml";
	
	/**
	 * If no extension is given, resolve the request to this mimetype.
	 *
	 * @var string
	 */
	protected static $default_mimetype = "text/xml";
	
	/**
	 * @uses authenticate()
	 * @var Member
	 */
	protected $member;
	
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
		if(!isset($this->urlParams['ClassName'])) return $this->notFound();
		$className = $this->urlParams['ClassName'];
		$id = (isset($this->urlParams['ID'])) ? $this->urlParams['ID'] : null;
		$relation = (isset($this->urlParams['Relation'])) ? $this->urlParams['Relation'] : null;
		
		// Check input formats
		if(!class_exists($className)) return $this->notFound();
		if($id && !is_numeric($id)) return $this->notFound();
		if($relation && !preg_match('/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/', $relation)) return $this->notFound();
		
		// if api access is disabled, don't proceed
		$apiAccess = singleton($className)->stat('api_access');
		if(!$apiAccess) return $this->permissionFailure();

		// authenticate through HTTP BasicAuth
		$this->member = $this->authenticate();

		// handle different HTTP verbs
		if($this->request->isGET() || $this->request->isHEAD()) return $this->getHandler($className, $id, $relation);
		if($this->request->isPOST()) return $this->postHandler($className, $id, $relation);
		if($this->request->isPUT()) return $this->putHandler($className, $id, $relation);
		if($this->request->isDELETE()) return $this->deleteHandler($className, $id, $relation);

		// if no HTTP verb matches, return error
		return $this->methodNotAllowed();
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
	 * @todo Access checking
	 * 
	 * @param String $className
	 * @param Int $id
	 * @param String $relation
	 * @return String The serialized representation of the requested object(s) - usually XML or JSON.
	 */
	protected function getHandler($className, $id, $relationName) {
		$sort = array(
			'sort' => $this->request->getVar('sort'),
			'dir' => $this->request->getVar('dir')
		);
		$limit = array(
			'start' => $this->request->getVar('start'),
			'limit' => $this->request->getVar('limit')
		);
		
		$params = $this->request->getVars();
		
		$responseFormatter = $this->getResponseDataFormatter();
		if(!$responseFormatter) return $this->unsupportedMediaType();
		
		// $obj can be either a DataObject or a DataObjectSet,
		// depending on the request
		if($id) {
			// Format: /api/v1/<MyClass>/<ID>
			$query = $this->getObjectQuery($className, $id, $params);
			$obj = singleton($className)->buildDataObjectSet($query->execute());
			if(!$obj) return $this->notFound();
			$obj = $obj->First();
			if(!$obj->canView()) return $this->permissionFailure();

			// Format: /api/v1/<MyClass>/<ID>/<Relation>
			if($relationName) {
				$query = $this->getObjectRelationQuery($obj, $params, $sort, $limit, $relationName);
				if($query === false) return $this->notFound();
				$obj = singleton($className)->buildDataObjectSet($query->execute());
			} 
			
		} else {
			// Format: /api/v1/<MyClass>
			$query = $this->getObjectsQuery($className, $params, $sort, $limit);
			$obj = singleton($className)->buildDataObjectSet($query->execute());

			// show empty serialized result when no records are present
			if(!$obj) $obj = new DataObjectSet();
		}
		
		$this->getResponse()->addHeader('Content-Type', $responseFormatter->getOutputContentType());
		
		$rawFields = $this->request->getVar('fields');
		$fields = $rawFields ? explode(',', $rawFields) : null;

		if($obj instanceof DataObjectSet) {
			$responseFormatter->setTotalSize($query->unlimitedRowCount());
			return $responseFormatter->convertDataObjectSet($obj, $fields);
		} else if(!$obj) {
			$responseFormatter->setTotalSize(0);
			return $responseFormatter->convertDataObjectSet(new DataObjectSet(), $fields);
		} else {
			return $responseFormatter->convertDataObject($obj, $fields);
		}
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
	protected function getSearchQuery($className, $params = null, $sort = null, $limit = null, $existingQuery = null) {
		if(singleton($className)->hasMethod('getRestfulSearchContext')) {
			$searchContext = singleton($className)->{'getRestfulSearchContext'}();
		} else {
			$searchContext = singleton($className)->getDefaultSearchContext();
		}
		$query = $searchContext->getQuery($params, $sort, $limit, $existingQuery);
		
		return $query;
	}
	
	/**
	 * Returns a dataformatter instance based on the request
	 * extension or mimetype. Falls back to {@link self::$default_extension}.
	 * 
	 * @param boolean $includeAcceptHeader Determines wether to inspect and prioritize any HTTP Accept headers 
	 * @return DataFormatter
	 */
	protected function getDataFormatter($includeAcceptHeader = false) {
		$extension = $this->request->getExtension();
		$contentTypeWithEncoding = $this->request->getHeader('Content-Type');
		preg_match('/([^;]*)/',$contentTypeWithEncoding, $contentTypeMatches);
		$contentType = $contentTypeMatches[0];
		$accept = $this->request->getHeader('Accept');
		$mimetypes = $this->request->getAcceptMimetypes();

		// get formatter
		if(!empty($extension)) {
			$formatter = DataFormatter::for_extension($extension);
		}elseif($includeAcceptHeader && !empty($accept) && $accept != '*/*') {
			$formatter = DataFormatter::for_mimetypes($mimetypes);
			if(!$formatter) $formatter = DataFormatter::for_extension(self::$default_extension);
		} elseif(!empty($contentType)) {
			$formatter = DataFormatter::for_mimetype($contentType);
		} else {
			$formatter = DataFormatter::for_extension(self::$default_extension);
		}

		if(!$formatter) return false;
		
		// set custom fields
		if($customAddFields = $this->request->getVar('add_fields')) $formatter->setCustomAddFields(explode(',',$customAddFields));
		if($customFields = $this->request->getVar('fields')) $formatter->setCustomFields(explode(',',$customFields));
		$formatter->setCustomRelations($this->getAllowedRelations($this->urlParams['ClassName']));
		
		$apiAccess = singleton($this->urlParams['ClassName'])->stat('api_access');
		if(is_array($apiAccess)) {
			$formatter->setCustomAddFields(array_intersect((array)$formatter->getCustomAddFields(), (array)$apiAccess['view']));
			if($formatter->getCustomFields()) {
				$formatter->setCustomFields(array_intersect((array)$formatter->getCustomFields(), (array)$apiAccess['view']));
			} else {
				$formatter->setCustomFields((array)$apiAccess['view']);
			}
			if($formatter->getCustomRelations()) {
				$formatter->setCustomRelations(array_intersect((array)$formatter->getCustomRelations(), (array)$apiAccess['view']));
			} else {
				$formatter->setCustomRelations((array)$apiAccess['view']);
			}
			
		}

		// set relation depth
		$relationDepth = $this->request->getVar('relationdepth');
		if(is_numeric($relationDepth)) $formatter->relationDepth = (int)$relationDepth;
		
		return $formatter;		
	}
	
	protected function getRequestDataFormatter() {
		return $this->getDataFormatter(false);
	}
	
	protected function getResponseDataFormatter() {
		return $this->getDataFormatter(true);
	}
	
	/**
	 * Handler for object delete
	 */
	protected function deleteHandler($className, $id) {
		$obj = DataObject::get_by_id($className, $id);
		if(!$obj) return $this->notFound();
		if(!$obj->canDelete()) return $this->permissionFailure();
		
		$obj->delete();
		
		$this->getResponse()->setStatusCode(204); // No Content
		return true;
	}

	/**
	 * Handler for object write
	 */
	protected function putHandler($className, $id) {
		$obj = DataObject::get_by_id($className, $id);
		if(!$obj) return $this->notFound();
		if(!$obj->canEdit()) return $this->permissionFailure();
		
		$reqFormatter = $this->getRequestDataFormatter();
		if(!$reqFormatter) return $this->unsupportedMediaType();
		
		$responseFormatter = $this->getResponseDataFormatter();
		if(!$responseFormatter) return $this->unsupportedMediaType();
		
		$obj = $this->updateDataObject($obj, $reqFormatter);
		
		$this->getResponse()->setStatusCode(200); // Success
		$this->getResponse()->addHeader('Content-Type', $responseFormatter->getOutputContentType());

		// Append the default extension for the output format to the Location header
		// or else we'll use the default (XML)
		$types = $responseFormatter->supportedExtensions();
		$type = '';
		if (count($types)) {
			$type = ".{$types[0]}";
		}

		$objHref = Director::absoluteURL(self::$api_base . "$obj->class/$obj->ID" . $type);
		$this->getResponse()->addHeader('Location', $objHref);
		
		return $responseFormatter->convertDataObject($obj);
	}

	/**
	 * Handler for object append / method call.
	 * 
	 * @todo Posting to an existing URL (without a relation)
	 * current resolves in creatig a new element,
	 * rather than a "Conflict" message.
	 */
	protected function postHandler($className, $id, $relation) {
		if($id) {
			if(!$relation) {
				$this->response->setStatusCode(409);
				return 'Conflict';
			}
			
			$obj = DataObject::get_by_id($className, $id);
			if(!$obj) return $this->notFound();
			
			if(!$obj->hasMethod($relation)) {
				return $this->notFound();
			}
			
			if(!$obj->stat('allowed_actions') || !in_array($relation, $obj->stat('allowed_actions'))) {
				return $this->permissionFailure();
			}
			
			$obj->$relation();
			
			$this->getResponse()->setStatusCode(204); // No Content
			return true;
		} else {
			if(!singleton($className)->canCreate()) return $this->permissionFailure();
			$obj = new $className();
		
			$reqFormatter = $this->getRequestDataFormatter();
			if(!$reqFormatter) return $this->unsupportedMediaType();
		
			$responseFormatter = $this->getResponseDataFormatter();
		
			$obj = $this->updateDataObject($obj, $reqFormatter);
		
			$this->getResponse()->setStatusCode(201); // Created
			$this->getResponse()->addHeader('Content-Type', $responseFormatter->getOutputContentType());

			// Append the default extension for the output format to the Location header
			// or else we'll use the default (XML)
			$types = $responseFormatter->supportedExtensions();
			$type = '';
			if (count($types)) {
				$type = ".{$types[0]}";
			}

			$objHref = Director::absoluteURL(self::$api_base . "$obj->class/$obj->ID" . $type);
			$this->getResponse()->addHeader('Location', $objHref);
		
			return $responseFormatter->convertDataObject($obj);
		}
	}
	
	/**
	 * Converts either the given HTTP Body into an array
	 * (based on the DataFormatter instance), or returns
	 * the POST variables.
	 * Automatically filters out certain critical fields
	 * that shouldn't be set by the client (e.g. ID).
	 *
	 * @param DataObject $obj
	 * @param DataFormatter $formatter
	 * @return DataObject The passed object
	 */
	protected function updateDataObject($obj, $formatter) {
		// if neither an http body nor POST data is present, return error
		$body = $this->request->getBody();
		if(!$body && !$this->request->postVars()) {
			$this->getResponse()->setStatusCode(204); // No Content
			return 'No Content';
		}
		
		if(!empty($body)) {
			$data = $formatter->convertStringToArray($body);
		} else {
			// assume application/x-www-form-urlencoded which is automatically parsed by PHP
			$data = $this->request->postVars();
		}
		
		// @todo Disallow editing of certain keys in database
		$data = array_diff_key($data, array('ID','Created'));
		
		$apiAccess = singleton($this->urlParams['ClassName'])->stat('api_access');
		if(is_array($apiAccess) && isset($apiAccess['edit'])) {
			$data = array_intersect_key($data, array_combine($apiAccess['edit'],$apiAccess['edit']));
		}

		$obj->update($data);
		$obj->write();
		
		return $obj;
	}
	
	/**
	 * Gets a single DataObject by ID,
	 * through a request like /api/v1/<MyClass>/<MyID>
	 * 
	 * @param string $className
	 * @param int $id
	 * @param array $params
	 * @return SQLQuery
	 */
	protected function getObjectQuery($className, $id, $params) {
		$baseClass = ClassInfo::baseDataClass($className);
		return singleton($className)->extendedSQL(
			"\"$baseClass\".\"ID\" = {$id}"
		);
	}
	
	/**
	 * @param DataObject $obj
	 * @param array $params
	 * @param int|array $sort
	 * @param int|array $limit
	 * @return SQLQuery
	 */
	protected function getObjectsQuery($className, $params, $sort, $limit) {
		return $this->getSearchQuery($className, $params, $sort, $limit);
	}
	
	
	/**
	 * @param DataObject $obj
	 * @param array $params
	 * @param int|array $sort
	 * @param int|array $limit
	 * @param string $relationName
	 * @return SQLQuery|boolean
	 */
	protected function getObjectRelationQuery($obj, $params, $sort, $limit, $relationName) {
		if($obj->hasMethod("{$relationName}Query")) {
			// @todo HACK Switch to ComponentSet->getQuery() once we implement it (and lazy loading)
			$query = $obj->{"{$relationName}Query"}(null, $sort, null, $limit);
			$relationClass = $obj->{"{$relationName}Class"}();
		} elseif($relationClass = $obj->many_many($relationName)) {
			// many_many() returns different notation
			$relationClass = $relationClass[1];
			$query = $obj->getManyManyComponentsQuery($relationName);
		} elseif($relationClass = $obj->has_many($relationName)) {
			$query = $obj->getComponentsQuery($relationName);
		} elseif($relationClass = $obj->has_one($relationName)) {
			$query = null;
		} else {
			return false;
		}

		// get all results
 		return $this->getSearchQuery($relationClass, $params, $sort, $limit, $query);
	}
	
	protected function permissionFailure() {
		// return a 401
		$this->getResponse()->setStatusCode(401);
		$this->getResponse()->addHeader('WWW-Authenticate', 'Basic realm="API Access"');
		$this->getResponse()->addHeader('Content-Type', 'text/plain');
		return "You don't have access to this item through the API.";
	}

	protected function notFound() {
		// return a 404
		$this->getResponse()->setStatusCode(404);
		$this->getResponse()->addHeader('Content-Type', 'text/plain');
		return "That object wasn't found";
	}
	
	protected function methodNotAllowed() {
		$this->getResponse()->setStatusCode(405);
		$this->getResponse()->addHeader('Content-Type', 'text/plain');
		return "Method Not Allowed";
	}
	
	protected function unsupportedMediaType() {
		$this->response->setStatusCode(415); // Unsupported Media Type
		$this->getResponse()->addHeader('Content-Type', 'text/plain');
		return "Unsupported Media Type";
	}
	
	protected function authenticate() {
		if(!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW'])) return false;
		
		if($member = Member::currentMember()) return $member;
		$member = MemberAuthenticator::authenticate(array(
			'Email' => $_SERVER['PHP_AUTH_USER'], 
			'Password' => $_SERVER['PHP_AUTH_PW'],
		), null);
		
		if($member) {
			$member->LogIn(false);
			return $member;
		} else {
			return false;
		}
	}
	
	/**
	 * Return only relations which have $api_access enabled.
	 * @todo Respect field level permissions once they are available in core
	 * 
	 * @param string $class
	 * @param Member $member
	 * @return array
	 */
	protected function getAllowedRelations($class, $member = null) {
		$allowedRelations = array();
		$obj = singleton($class);
		$relations = (array)$obj->has_one() + (array)$obj->has_many() + (array)$obj->many_many();
		if($relations) foreach($relations as $relName => $relClass) {
			if(singleton($relClass)->stat('api_access')) {
				$allowedRelations[] = $relName;
			}
		}
		return $allowedRelations;
	}
	
}

/**
 * Restful server handler for a DataObjectSet
 * 
 * @package sapphire
 * @subpackage api
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
 * 
 * @package sapphire
 * @subpackage api
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
