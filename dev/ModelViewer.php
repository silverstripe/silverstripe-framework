<?php
/**
 * Gives you a nice way of viewing your data model.
 * Access at dev/viewmodel.
 *
 * Requirements: http://graphviz.org/
 * 
 * @package sapphire
 * @subpackage tools
 */
class ModelViewer extends Controller {
	static $url_handlers = array(
		'$Module!' => 'handleModule',
	);

	protected $module = null;
	
	function handleModule($request) {
		return new ModelViewer_Module($request->param('Module'));
	}
	
	function init() {
		parent::init();

		$canAccess = (Director::isDev() || Director::is_cli() || Permission::check("ADMIN"));
		if(!$canAccess) return Security::permissionFailure($this);

		// check for graphviz dependencies
		$returnCode = 0;
		$output = array();
		exec("which neato", $output, $returnCode);
		if($returnCode != 0) {
			user_error(
				'You don\'t seem to have the GraphViz library (http://graphviz.org/) and the "neato" command-line utility available',
				E_USER_ERROR
			);
		}
	}

	/**
	 * Model classes
	 */
	function Models() {
		$classes = ClassInfo::subclassesFor('DataObject');
		array_shift($classes);
		$output = new DataObjectSet();
		foreach($classes as $class) {
			$output->push(new ModelViewer_Model($class));
		}
		return $output;
	}

	/**
	 * Model classes, grouped by Module
	 */
	function Modules() {
		$classes = ClassInfo::subclassesFor('DataObject');
		array_shift($classes);
		
		$modules = array();
		foreach($classes as $class) {
			$model = new ModelViewer_Model($class);
			if(!isset($modules[$model->Module])) $modules[$model->Module] = new DataObjectSet();
			$modules[$model->Module]->push($model);
		}
		ksort($modules);
		unset($modules['userforms']);
		
		if($this->module) {
			$modules = array($this->module => $modules[$this->module]);
		}

		$output = new DataObjectSet();
		foreach($modules as $moduleName => $models) {
			$output->push(new ArrayData(array(
				'Link' => 'dev/viewmodel/' . $moduleName,
				'Name' => $moduleName,
				'Models' => $models,
			)));
		}
		
		return $output;
	}
}

/**
 * @package sapphire
 * @subpackage tools
 */
class ModelViewer_Module extends ModelViewer {
	static $url_handlers = array(
		'graph' => 'graph',
	);

	/**
	 * ModelViewer can be optionally constructed to restrict its output to a specific module
	 */
	function __construct($module = null) {
		$this->module = $module;
		
		parent::__construct();
	}
	
	function graph() {
		SSViewer::set_source_file_comments(false);
		$dotContent = $this->renderWith("ModelViewer_dotsrc");
		$CLI_dotContent = escapeshellarg($dotContent);

		$output= `echo $CLI_dotContent | neato -Tpng:gd &> /dev/stdout`;
		if(substr($output,1,3) == 'PNG') header("Content-type: image/png");
		else header("Content-type: text/plain");
		echo $output;
	}
}

/**
 * Represents a single model in the model viewer 
 * 
 * @package sapphire
 * @subpackage tools
 */
class ModelViewer_Model extends ViewableData {
	protected $className;
	
	function __construct($className) {
		$this->className = $className;
		parent::__construct();
	}
	
	function getModule() {
		global $_CLASS_MANIFEST;
		$className = strtolower($this->className);
		if(($pos = strpos($className,'_')) !== false) $className = substr($className,0,$pos);
		if(isset($_CLASS_MANIFEST[$className])) {
			if(preg_match('/^'.str_replace('/','\/',preg_quote(BASE_PATH)).'\/([^\/]+)\//', $_CLASS_MANIFEST[$className], $matches)) {
				return $matches[1];
			}
		}
	}
	
	function getName() {
		return $this->className;
	}
	
	function getParentModel() {
		$parentClass = get_parent_class($this->className);
		if($parentClass != "DataObject") return $parentClass;
	}
	
	function Fields() {
		$output = new DataObjectSet();
		
		$output->push(new ModelViewer_Field($this,'ID', 'PrimaryKey'));
		if(!$this->ParentModel) {
			$output->push(new ModelViewer_Field($this,'Created', 'Datetime'));
			$output->push(new ModelViewer_Field($this,'LastEdited', 'Datetime'));
		}

		$db = singleton($this->className)->uninherited('db',true);
		if($db) foreach($db as $k => $v) {
			$output->push(new ModelViewer_Field($this, $k, $v));
		}
		return $output;		
	}
	
	function Relations() {
		$output = new DataObjectSet();
		
		foreach(array('has_one','has_many','many_many') as $relType) {
			$items = singleton($this->className)->uninherited($relType,true);
			if($items) foreach($items as $k => $v) {
				$output->push(new ModelViewer_Relation($this, $k, $v, $relType));
			}
		}
		return $output;
	}
}

/**
 * @package sapphire
 * @subpackage tools
 */
class ModelViewer_Field extends ViewableData {
	public $Model, $Name, $Type;
	
	function __construct($model, $name, $type) {
		$this->Model = $model;
		$this->Name = $name;
		$this->Type = $type;
		
		parent::__construct();
	}
}

/**
 * @package sapphire
 * @subpackage tools
 */
class ModelViewer_Relation extends ViewableData {
	public $Model, $Name, $RelationType, $RelatedClass;
	
	function __construct($model, $name, $relatedClass, $relationType) {
		$this->Model = $model;
		$this->Name = $name;
		$this->RelatedClass = $relatedClass;
		$this->RelationType = $relationType;
		
		parent::__construct();
	}
	
}

?>