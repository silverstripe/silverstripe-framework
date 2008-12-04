<?php

/**
 * Gives you a nice way of viewing your data model.
 * Access at dev/viewmodel
 */
class ModelViewer extends Controller {
	function init() {
		parent::init();
		if(!Permission::check("ADMIN")) Security::permissionFailure();
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
		
		if(isset($this->urlParams['Action']) && isset($modules[$this->urlParams['Action']])) {
			$module = $this->urlParams['Action'];
			$modules = array($module => $modules[$module]);
		}

		$output = new DataObjectSet();
		foreach($modules as $moduleName => $models) {
			$output->push(new ArrayData(array(
				'Name' => $moduleName,
				'Models' => $models,
			)));
		}
		
		return $output;
	}

}

/**
 * Represents a single model in the model viewer 
 */
class ModelViewer_Model extends ViewableData {
	protected $className;
	
	function __construct($className) {
		$this->className = $className;
	}
	
	function getModule() {
		global $_CLASS_MANIFEST;
		$className = $this->className;
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
		$db = singleton($this->className)->db();
		$output = new DataObjectSet();
		$output->push(new ModelViewer_Field('ID', 'PrimaryKey'));
		$output->push(new ModelViewer_Field('Created', 'Datetime'));
		$output->push(new ModelViewer_Field('LastEdited', 'Datetime'));
		foreach($db as $k => $v) {
			$output->push(new ModelViewer_Field($k, $v));
		}
		return $output;		
	}
	
	function Relations() {
		$output = new DataObjectSet();
		
		foreach(array('has_one','has_many','many_many') as $relType) {
			$items = singleton($this->className)->$relType();
			foreach($items as $k => $v) {
				$output->push(new ModelViewer_Relation($k, $v, $relType));
			}
		}
		return $output;
	}
}

class ModelViewer_Field extends ViewableData {
	public $Name, $Type;
	
	function __construct($name, $type) {
		$this->Name = $name;
		$this->Type = $type;
	}
}

class ModelViewer_Relation extends ViewableData {
	public $Name, $RelationType, $RelatedClass;
	
	function __construct($name, $relatedClass, $relationType) {
		$this->Name = $name;
		$this->RelatedClass = $relatedClass;
		$this->RelationType = $relationType;
	}
	
}


?>