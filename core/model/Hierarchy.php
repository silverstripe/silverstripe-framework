<?php
/**
 * DataObjects that use the Hierachy decorator can be be organised as a hierachy, with children and parents.
 * The most obvious example of this is SiteTree.
 * @package sapphire
 * @subpackage model
 */
class Hierarchy extends DataObjectDecorator {
	
	protected $markedNodes;
	
	protected $markingFilter;
	
	/**
	 * @var Int
	 */
	protected $_cache_numChildren;
	
	function augmentSQL(SQLQuery &$query) {
	}

	function augmentDatabase() {
	}
	
	function augmentWrite(&$manipulation) {
	}
	
	function extraStatics($class = null) {
		return array(
			'has_one' => array(
				// TODO this method is called *both* statically and on an instance
				"Parent" => ($class) ? $class : $this->owner->class
			)
		);
	}

	/**
	 * Returns the children of this DataObject as an XHTML UL. This will be called recursively on each child,
	 * so if they have children they will be displayed as a UL inside a LI.
	 * @param string $attributes Attributes to add to the UL.
	 * @param string $titleEval PHP code to evaluate to start each child - this should include '<li>'
	 * @param string $extraArg Extra arguments that will be passed on to children, for if they overload this function.
	 * @param boolean $limitToMarked Display only marked children.
	 * @param string $childrenMethod The name of the method used to get children from each object
	 * @param boolean $rootCall Set to true for this first call, and then to false for calls inside the recursion. You should not change this.
	 * @param int $minNodeCount
	 * @return string
	 */
	public function getChildrenAsUL($attributes = "", $titleEval = '"<li>" . $child->Title', $extraArg = null, $limitToMarked = false, $childrenMethod = "AllChildrenIncludingDeleted", $numChildrenMethod = "numChildren", $rootCall = true, $minNodeCount = 30) {
		if($limitToMarked && $rootCall) {
			$this->markingFinished($numChildrenMethod);
		}
		
		if($this->owner->hasMethod($childrenMethod)) {
			$children = $this->owner->$childrenMethod($extraArg);
		} else {
			user_error(sprintf("Can't find the method '%s' on class '%s' for getting tree children", 
				$childrenMethod, get_class($this->owner)), E_USER_ERROR);
		}

		if($children) {
			if($attributes) {
				$attributes = " $attributes";
			}
			
			$output = "<ul$attributes>\n";
		
			foreach($children as $child) {
				if(!$limitToMarked || $child->isMarked()) {
					$foundAChild = true;
					$output .= eval("return $titleEval;") . "\n" . 
					$child->getChildrenAsUL("", $titleEval, $extraArg, $limitToMarked, $childrenMethod, $numChildrenMethod, false, $minNodeCount) . "</li>\n";
				}
			}
			
			$output .= "</ul>\n";
		}
		
		if(isset($foundAChild) && $foundAChild) {
			return $output;
		}
	}
	
	/**
	 * Mark a segment of the tree, by calling mark().
	 * The method performs a breadth-first traversal until the number of nodes is more than minCount.
	 * This is used to get a limited number of tree nodes to show in the CMS initially.
	 * 
	 * This method returns the number of nodes marked.  After this method is called other methods 
	 * can check isExpanded() and isMarked() on individual nodes.
	 * 
	 * @param int $minNodeCount The minimum amount of nodes to mark.
	 * @return int The actual number of nodes marked.
	 */
	public function markPartialTree($minNodeCount = 30, $context = null, $childrenMethod = "AllChildrenIncludingDeleted", $numChildrenMethod = "numChildren") {
		if(!is_numeric($minNodeCount)) $minNodeCount = 30;

		$this->markedNodes = array($this->owner->ID => $this->owner);
		$this->owner->markUnexpanded();

		// foreach can't handle an ever-growing $nodes list
		while(list($id, $node) = each($this->markedNodes)) {
			$this->markChildren($node, $context, $childrenMethod, $numChildrenMethod);
			if($minNodeCount && sizeof($this->markedNodes) >= $minNodeCount) {
				break;
			}
		}		
		return sizeof($this->markedNodes);
	}
	
	/**
	 * Filter the marking to only those object with $node->$parameterName = $parameterValue
	 * @param string $parameterName The parameter on each node to check when marking.
	 * @param mixed $parameterValue The value the parameter must be to be marked.
	 */
	public function setMarkingFilter($parameterName, $parameterValue) {
		$this->markingFilter = array(
			"parameter" => $parameterName,
			"value" => $parameterValue
		);
	}

	/**
	 * Filter the marking to only those where the function returns true.
	 * The node in question will be passed to the function.
	 * @param string $funcName The function name.
	 */
	public function setMarkingFilterFunction($funcName) {
		$this->markingFilter = array(
			"func" => $funcName,
		);
	}

	/**
	 * Returns true if the marking filter matches on the given node.
	 * @param DataObject $node Node to check.
	 * @return boolean
	 */
	public function markingFilterMatches($node) {
		if(!$this->markingFilter) {
			return true;
		}

		if(isset($this->markingFilter['parameter']) && $parameterName = $this->markingFilter['parameter']) {
			if(is_array($this->markingFilter['value'])){
				$ret = false;
				foreach($this->markingFilter['value'] as $value) {
					$ret = $ret||$node->$parameterName==$value;
					if($ret == true) {
						break;
					}
				}
				return $ret;		
			} else {
				return ($node->$parameterName == $this->markingFilter['value']);
			}
		} else if ($func = $this->markingFilter['func']) {
			return call_user_func($func, $node);
		}
	}
	
	/**
	 * Mark all children of the given node that match the marking filter.
	 * @param DataObject $node Parent node.
	 */
	public function markChildren($node, $context = null, $childrenMethod = "AllChildrenIncludingDeleted", $numChildrenMethod = "numChildren") {
		if($node->hasMethod($childrenMethod)) {
			$children = $node->$childrenMethod($context);
		} else {
			user_error(sprintf("Can't find the method '%s' on class '%s' for getting tree children", 
				$childrenMethod, get_class($this->owner)), E_USER_ERROR);
		}
		
		$node->markExpanded();
		if($children) {
			foreach($children as $child) {
				if(!$this->markingFilter || $this->markingFilterMatches($child)) {
					if($child->$numChildrenMethod()) {
						$child->markUnexpanded();
					} else {
						$child->markExpanded();
					}
					$this->markedNodes[$child->ID] = $child;
				}
			}
		}
	}
	
	/**
	 * Ensure marked nodes that have children are also marked expanded.
	 * Call this after marking but before iterating over the tree.
	 */
	protected function markingFinished($numChildrenMethod = "numChildren") {
		// Mark childless nodes as expanded.
		if($this->markedNodes) {
			foreach($this->markedNodes as $id => $node) {
				if(!$node->isExpanded() && !$node->$numChildrenMethod()) {
					$node->markExpanded();
				}
			}
		}
	}
	
	/**
	 * Return CSS classes of 'unexpanded', 'closed', both, or neither, depending on
	 * the marking of this DataObject.
	 */
	public function markingClasses() {
		$classes = '';
		if(!$this->isExpanded()) {
			$classes .= " unexpanded";
		}
		if(!$this->isTreeOpened()) {
			$classes .= " closed";
		}
		return $classes;
	}
	
	/**
	 * Mark the children of the DataObject with the given ID.
	 * @param int $id ID of parent node.
	 * @param boolean $open If this is true, mark the parent node as opened.
	 */
	public function markById($id, $open = false) {
		if(isset($this->markedNodes[$id])) {
			$this->markChildren($this->markedNodes[$id]);
			if($open) {
				$this->markedNodes[$id]->markOpened();
			}
			return true;
		} else {
			return false;
		}
	}

	/**
	 * Expose the given object in the tree, by marking this page and all it ancestors.
	 * @param DataObject $childObj 
	 */
	public function markToExpose($childObj) {
		if(is_object($childObj)){
			$stack = array_reverse($childObj->parentStack());
			foreach($stack as $stackItem) {
				$this->markById($stackItem->ID, true);
			}
		}
	}
	
	/**
	 * Return the IDs of all the marked nodes
	 */
	public function markedNodeIDs() {
		return array_keys($this->markedNodes);
	}

	/**
	 * Return an array of this page and its ancestors, ordered item -> root.
	 * @return array
	 */
	public function parentStack() {
		$p = $this->owner;
		
		while($p) {
			$stack[] = $p;
			$p = $p->ParentID ? $p->Parent() : null;
		}
		
		return $stack;
	}
	
	/**
	 * True if this DataObject is marked.
	 * @var boolean
	 */
	protected static $marked = array();
	
	/**
	 * True if this DataObject is expanded.
	 * @var boolean
	 */
	protected static $expanded = array();
	
	/**
	 * True if this DataObject is opened.
	 * @var boolean
	 */
	protected static $treeOpened = array();
	
	/**
	 * Mark this DataObject as expanded.
	 */
	public function markExpanded() {
		self::$marked[ClassInfo::baseDataClass($this->owner->class)][$this->owner->ID] = true;
		self::$expanded[ClassInfo::baseDataClass($this->owner->class)][$this->owner->ID] = true;
	}
	
	/**
	 * Mark this DataObject as unexpanded.
	 */
	public function markUnexpanded() {
		self::$marked[ClassInfo::baseDataClass($this->owner->class)][$this->owner->ID] = true;
		self::$expanded[ClassInfo::baseDataClass($this->owner->class)][$this->owner->ID] = false;
	}
	
	/**
	 * Mark this DataObject's tree as opened.
	 */
	public function markOpened() {
		self::$marked[ClassInfo::baseDataClass($this->owner->class)][$this->owner->ID] = true;
		self::$treeOpened[ClassInfo::baseDataClass($this->owner->class)][$this->owner->ID] = true;
	}
	
	/**
	 * Check if this DataObject is marked.
	 * @return boolean
	 */
	public function isMarked() {
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		$id = $this->owner->ID;
		return isset(self::$marked[$baseClass][$id]) ? self::$marked[$baseClass][$id] : false;
	}
	
	/**
	 * Check if this DataObject is expanded.
	 * @return boolean
	 */
	public function isExpanded() {
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		$id = $this->owner->ID;
		return isset(self::$expanded[$baseClass][$id]) ? self::$expanded[$baseClass][$id] : false;
	}
	
	/**
	 * Check if this DataObject's tree is opened.
	 */
	public function isTreeOpened() {
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		$id = $this->owner->ID;
		return isset(self::$treeOpened[$baseClass][$id]) ? self::$treeOpened[$baseClass][$id] : false;
	}

	/**
	 * Return a partial tree as an HTML UL.
	 */
	public function partialTreeAsUL($minCount = 50) {
		$children = $this->owner->AllChildren();
		if($children) {
			if($attributes) $attributes = " $attributes";
			$output = "<ul$attributes>\n";
		
			foreach($children as $child) {
				$output .= eval("return $titleEval;") . "\n" . 
					$child->getChildrenAsUL("", $titleEval, $extraArg) . "</li>\n";
			}
			$output .= "</ul>\n";
		}
		return $output;
	}
	
	/**
	 * Get a list of this DataObject's and all it's descendants IDs.
	 * @return int
	 */
	public function getDescendantIDList() {
		$idList = array();
		$this->loadDescendantIDListInto($idList);
		return $idList;
	}
	
	/**
	 * Get a list of this DataObject's and all it's descendants ID, and put it in $idList.
	 * @var array $idList Array to put results in.
	 */
	public function loadDescendantIDListInto(&$idList) {
		if($children = $this->AllChildren()) {
			foreach($children as $child) {
				if(in_array($child->ID, $idList)) {
					continue;
				}
				$idList[] = $child->ID;
				$ext = $child->getExtensionInstance('Hierarchy');
				$ext->setOwner($child);
				$ext->loadDescendantIDListInto($idList);
				$ext->clearOwner();
			}
		}
	}
	
	/**
	 * Get the children for this DataObject.
	 * @return DataObjectSet
	 */
	public function Children() {
		if(!(isset($this->_cache_children) && $this->_cache_children)) { 
			$result = $this->owner->stageChildren(false); 
		 	if(isset($result)) { 
		 		$this->_cache_children = new DataObjectSet(); 
		 		foreach($result as $child) { 
		 			if($child->canView()) { 
		 				$this->_cache_children->push($child); 
		 			} 
		 		} 
		 	} 
		} 
		return $this->_cache_children;
	}

	/**
	 * Return all children, including those 'not in menus'.
	 * @return DataObjectSet
	 */
	public function AllChildren() {
		return $this->owner->stageChildren(true);
	}

	/**
	 * Return all children, including those that have been deleted but are still in live.
	 * Deleted children will be marked as "DeletedFromStage"
	 * Added children will be marked as "AddedToStage"
	 * Modified children will be marked as "ModifiedOnStage"
	 * Everything else has "SameOnStage" set, as an indicator that this information has been looked up.
	 * @return DataObjectSet
	 */
	public function AllChildrenIncludingDeleted($context = null) {
		return $this->doAllChildrenIncludingDeleted($context);
	}
	
	/**
	 * @see AllChildrenIncludingDeleted
	 *
	 * @param unknown_type $context
	 * @return DataObjectSet
	 */
	public function doAllChildrenIncludingDeleted($context = null) {
		if(!$this->owner) user_error('Hierarchy::doAllChildrenIncludingDeleted() called without $this->owner');
		
		$idxStageChildren = array();
		$idxLiveChildren = array();
		
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		if($baseClass) {
			$stageChildren = $this->owner->stageChildren(true);
			
			// Add live site content that doesn't exist on the stage site, if required.
			if($this->owner->hasExtension('Versioned')) {
				// Next, go through the live children.  Only some of these will be listed					
				$liveChildren = $this->owner->liveChildren(true, true);
				if($liveChildren) {
					foreach($liveChildren as $child) {
						$stageChildren->push($child);
					}
				}
			}

			$this->owner->extend("augmentAllChildrenIncludingDeleted", $stageChildren, $context); 
			
		} else {
			user_error("Hierarchy::AllChildren() Couldn't determine base class for '{$this->owner->class}'", E_USER_ERROR);
		}
		
		return $stageChildren;
	}
	
	/**
	 * Return all the children that this page had, including pages that were deleted
	 * from both stage & live.
	 */
	public function AllHistoricalChildren() {
		$baseClass=ClassInfo::baseDataClass($this->owner->class);
		return Versioned::get_including_deleted($baseClass, 
			"\"ParentID\" = " . (int)$this->owner->ID, "\"$baseClass\".\"ID\" ASC");
	}
	
	/**
	 * Return the number of children that this page ever had, including pages that were deleted
	 */
	public function numHistoricalChildren() {
		$query = Versioned::get_including_deleted_query(ClassInfo::baseDataClass($this->owner->class), 
			"\"ParentID\" = " . (int)$this->owner->ID);
			
		return $query->unlimitedRowCount();
	}

	/**
	 * Return the number of direct children.
	 * By default, values are cached after the first invocation.
	 * Can be augumented by {@link augmentNumChildrenCountQuery()}.
	 * 
	 * @param Boolean $cache
	 * @return int
	 */
	public function numChildren($cache = true) {
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		
		// Build the cache for this class if it doesn't exist.
		if(!$cache || !is_numeric($this->_cache_numChildren)) {
			// We build the query in an extension-friendly way.
			$query = new SQLQuery(
				"COUNT(*)",
				"\"$baseClass\"", 
				sprintf('"ParentID" = %d', $this->owner->ID)
			);
			$this->owner->extend('augmentSQL', $query);
			$this->owner->extend('augmentNumChildrenCountQuery', $query);
			 
			$this->_cache_numChildren = (int)$query->execute()->value();
		}

		// If theres no value in the cache, it just means that it doesn't have any children.
		return $this->_cache_numChildren;
	}

	/**
	 * Return children from the stage site
	 * 
	 * @param showAll Inlcude all of the elements, even those not shown in the menus.
	 *   (only applicable when extension is applied to {@link SiteTree}).
	 * @return DataObjectSet
	 */
	public function stageChildren($showAll = false) {
		if($this->owner->db('ShowInMenus')) {
			$extraFilter = ($showAll) ? '' : " AND \"ShowInMenus\"=1";
		} else {
			$extraFilter = '';
		}
		
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		
		$staged = DataObject::get($baseClass, "\"{$baseClass}\".\"ParentID\" = " 
			. (int)$this->owner->ID . " AND \"{$baseClass}\".\"ID\" != " . (int)$this->owner->ID
			. $extraFilter, "");
			
		if(!$staged) $staged = new DataObjectSet();
		$this->owner->extend("augmentStageChildren", $staged, $showAll);
		return $staged;
	}

	/**
	 * Return children from the live site, if it exists.
	 * 
	 * @param boolean $showAll Include all of the elements, even those not shown in the menus.
	 *   (only applicable when extension is applied to {@link SiteTree}).
	 * @param boolean $onlyDeletedFromStage Only return items that have been deleted from stage
	 * @return DataObjectSet
	 */
	public function liveChildren($showAll = false, $onlyDeletedFromStage = false) {
		if($this->owner->db('ShowInMenus')) {
			$extraFilter = ($showAll) ? '' : " AND \"ShowInMenus\"=1";
		} else {
			$extraFilter = '';
		}
		$join = "";

		$baseClass = ClassInfo::baseDataClass($this->owner->class);

		$filter = "\"{$baseClass}\".\"ParentID\" = " . (int)$this->owner->ID 
			. " AND \"{$baseClass}\".\"ID\" != " . (int)$this->owner->ID;
		
		if($onlyDeletedFromStage) {
			// Note that the lack of double-quotes around $baseClass are the only thing preventing
			// it from being rewritten to {$baseClass}_Live.  This is brittle and a little clumsy
			$join = "LEFT JOIN {$baseClass} ON {$baseClass}.\"ID\" = \"{$baseClass}\".\"ID\"";
			$filter .=  " AND {$baseClass}.\"ID\" IS NULL";
		}

		$oldStage = Versioned::current_stage();
		Versioned::reading_stage('Live');
		
		// Singleton is necessary and not $this->owner so as not to muck with Translatable's
		// behaviour.
		$query = singleton($baseClass)->extendedSQL($filter, null, null, $join);

		// Since we didn't include double quotes in the join & filter, we need to add them into the
		// SQL now, after Versioned has done is query rewriting
		$correctedSQL = str_replace(array("LEFT JOIN {$baseClass}", "{$baseClass}.\"ID\""),
			array("LEFT JOIN \"{$baseClass}\"", "\"{$baseClass}\".\"ID\""), $query->sql());

		$result = $this->owner->buildDataObjectSet(DB::query($correctedSQL));
		
		Versioned::reading_stage($oldStage);
		
		return $result;
	}
	
	/**
	 * Get the parent of this class.
	 * @return DataObject
	 */
	public function getParent($filter = '') {
		if($p = $this->owner->__get("ParentID")) {
			$tableClasses = ClassInfo::dataClassesFor($this->owner->class);
			$baseClass = array_shift($tableClasses);
			$filter .= ($filter) ? " AND " : ""."\"$baseClass\".\"ID\" = $p";
			return DataObject::get_one($this->owner->class, $filter);
		}
	}
	
	/**
	 * Return all the parents of this class in a set ordered from the lowest to highest parent.
	 *
	 * @return DataObjectSet
	 */
	public function getAncestors() {
		$ancestors = new DataObjectSet();
		$object    = $this->owner;
		
		while($object = $object->getParent()) {
			$ancestors->push($object);
		}
		
		return $ancestors;
	}
	
	/**
	 * Get the next node in the tree of the type. If there is no instance of the className descended from this node,
	 * then search the parents.
	 * 
	 * @todo Write!
	 */
	public function naturalPrev( $className, $afterNode = null ) {
		return null;
	}

	/**
	 * Get the next node in the tree of the type. If there is no instance of the className descended from this node,
	 * then search the parents.
	 * @param string $className Class name of the node to find.
	 * @param string|int $root ID/ClassName of the node to limit the search to
	 * @param DataObject afterNode Used for recursive calls to this function
	 * @return DataObject
	 */
	public function naturalNext( $className = null, $root = 0, $afterNode = null ) {
		// If this node is not the node we are searching from, then we can possibly return this
		// node as a solution
		if($afterNode && $afterNode->ID != $this->owner->ID) {
			if(!$className || ($className && $this->owner->class == $className)) {
				return $this->owner;
			}
		}
			
		$nextNode = null;
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		
		$children = DataObject::get(ClassInfo::baseDataClass($this->owner->class), "\"$baseClass\".\"ParentID\"={$this->owner->ID}" . ( ( $afterNode ) ? " AND \"Sort\" > " . sprintf( '%d', $afterNode->Sort ) : "" ), '"Sort" ASC');
		
		// Try all the siblings of this node after the given node
		/*if( $siblings = DataObject::get( ClassInfo::baseDataClass($this->owner->class), "\"ParentID\"={$this->owner->ParentID}" . ( $afterNode ) ? "\"Sort\" > {$afterNode->Sort}" : "" , '\"Sort\" ASC' ) )
			$searchNodes->merge( $siblings );*/
		
		if($children) {
			foreach($children as $node) {
				if($nextNode = $node->naturalNext($className, $node->ID, $this->owner)) {
					break;
				}
			}
			
			if($nextNode) {
				return $nextNode;
			}
		}
		
		// if this is not an instance of the root class or has the root id, search the parent
		if(!(is_numeric($root) && $root == $this->owner->ID || $root == $this->owner->class) && ($parent = $this->owner->Parent())) {
			return $parent->naturalNext( $className, $root, $this->owner );
		}
		
		return null;
	}
	
	function flushCache() {
		$this->_cache_children = null;
		$this->_cache_numChildren = null;
		self::$marked = array();
		self::$expanded = array();
		self::$treeOpened = array();
	}
}

?>
