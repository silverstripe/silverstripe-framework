<?php
/**
 * DataObjects that use the Hierarchy extension can be be organised as a hierarchy, with children and parents. The most
 * obvious example of this is SiteTree.
 *
 * @package framework
 * @subpackage model
 *
 * @property int        ParentID
 * @property DataObject owner
 * @method   DataObject Parent
 */
class Hierarchy extends DataExtension {

	protected $markedNodes;

	protected $markingFilter;

	/** @var int */
	protected $_cache_numChildren;

	/**
	 * The lower bounds for the amount of nodes to mark. If set, the logic will expand nodes until it reaches at least
	 * this number, and then stops. Root nodes will always show regardless of this settting. Further nodes can be
	 * lazy-loaded via ajax. This isn't a hard limit. Example: On a value of 10, with 20 root nodes, each having 30
	 * children, the actual node count will be 50 (all root nodes plus first expanded child).
	 *
	 * @config
	 * @var int
	 */
	private static $node_threshold_total = 50;

	/**
	 * Limit on the maximum children a specific node can display. Serves as a hard limit to avoid exceeding available
	 * server resources in generating the tree, and browser resources in rendering it. Nodes with children exceeding
	 * this value typically won't display any children, although this is configurable through the $nodeCountCallback
	 * parameter in {@link getChildrenAsUL()}. "Root" nodes will always show all children, regardless of this setting.
	 *
	 * @config
	 * @var int
	 */
	private static $node_threshold_leaf = 250;

	/**
	 * A list of classnames to exclude from display in both the CMS and front end
	 * displays. ->Children() and ->AllChildren affected.
	 * Especially useful for big sets of pages like listings
	 * If you use this, and still need the classes to be editable
	 * then add a model admin for the class
	 * Note: Does not filter subclasses (non-inheriting)
	 *
	 * @var array
	 * @config
	 */
	private static $hide_from_hierarchy = array();

	/**
	 * A list of classnames to exclude from display in the page tree views of the CMS,
	 * unlike $hide_from_hierarchy above which effects both CMS and front end.
	 * Especially useful for big sets of pages like listings
	 * If you use this, and still need the classes to be editable
	 * then add a model admin for the class
	 * Note: Does not filter subclasses (non-inheriting)
	 *
	 * @var array
	 * @config
	 */
	private static $hide_from_cms_tree = array();

	public static function get_extra_config($class, $extension, $args) {
		return array(
			'has_one' => array('Parent' => $class)
		);
	}

	/**
	 * Validate the owner object - check for existence of infinite loops.
	 *
	 * @param ValidationResult $validationResult
	 */
	public function validate(ValidationResult $validationResult) {
		// The object is new, won't be looping.
		if (!$this->owner->ID) return;
		// The object has no parent, won't be looping.
		if (!$this->owner->ParentID) return;
		// The parent has not changed, skip the check for performance reasons.
		if (!$this->owner->isChanged('ParentID')) return;

		// Walk the hierarchy upwards until we reach the top, or until we reach the originating node again.
		$node = $this->owner;
		while($node) {
			if ($node->ParentID==$this->owner->ID) {
				// Hierarchy is looping.
				$validationResult->error(
					_t(
						'Hierarchy.InfiniteLoopNotAllowed',
						'Infinite loop found within the "{type}" hierarchy. Please change the parent to resolve this',
						'First argument is the class that makes up the hierarchy.',
						array('type' => $this->owner->class)
					),
					'INFINITE_LOOP'
				);
				break;
			}
			$node = $node->ParentID ? $node->Parent() : null;
		}

		// At this point the $validationResult contains the response.
	}

	/**
	 * Returns the children of this DataObject as an XHTML UL. This will be called recursively on each child, so if they
	 * have children they will be displayed as a UL inside a LI.
	 *
	 * @param string          $attributes         Attributes to add to the UL
	 * @param string|callable $titleEval          PHP code to evaluate to start each child - this should include '<li>'
	 * @param string          $extraArg           Extra arguments that will be passed on to children, for if they
	 *                                            overload this function
	 * @param bool            $limitToMarked      Display only marked children
	 * @param string          $childrenMethod     The name of the method used to get children from each object
	 * @param bool            $rootCall           Set to true for this first call, and then to false for calls inside
	 *                                            the recursion. You should not change this.
	 * @param int             $nodeCountThreshold See {@link self::$node_threshold_total}
	 * @param callable        $nodeCountCallback  Called with the node count, which gives the callback an opportunity to
	 *                                            intercept the query. Useful e.g. to avoid excessive children listings
	 *                                            (Arguments: $parent, $numChildren)
	 *
	 * @return string
	 */
	public function getChildrenAsUL($attributes = "", $titleEval = '"<li>" . $child->Title', $extraArg = null,
			$limitToMarked = false, $childrenMethod = "AllChildrenIncludingDeleted",
			$numChildrenMethod = "numChildren", $rootCall = true,
			$nodeCountThreshold = null, $nodeCountCallback = null) {

		if(!is_numeric($nodeCountThreshold)) {
			$nodeCountThreshold = Config::inst()->get('Hierarchy', 'node_threshold_total');
		}

		if($limitToMarked && $rootCall) {
			$this->markingFinished($numChildrenMethod);
		}


		if($nodeCountCallback) {
			$nodeCountWarning = $nodeCountCallback($this->owner, $this->owner->$numChildrenMethod());
			if($nodeCountWarning) return $nodeCountWarning;
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
					if(is_callable($titleEval)) {
						$output .= $titleEval($child, $numChildrenMethod);
					} else {
						$output .= eval("return $titleEval;");
					}
					$output .= "\n";

					$numChildren = $child->$numChildrenMethod();

					if(
						// Always traverse into opened nodes (they might be exposed as parents of search results)
						$child->isExpanded()
						// Only traverse into children if we haven't reached the maximum node count already.
						// Otherwise, the remaining nodes are lazy loaded via ajax.
						&& $child->isMarked()
					) {
						// Additionally check if node count requirements are met
						$nodeCountWarning = $nodeCountCallback ? $nodeCountCallback($child, $numChildren) : null;
						if($nodeCountWarning) {
							$output .= $nodeCountWarning;
							$child->markClosed();
						} else {
							$output .= $child->getChildrenAsUL("", $titleEval, $extraArg, $limitToMarked,
								$childrenMethod,	$numChildrenMethod, false, $nodeCountThreshold);
						}
					} elseif($child->isTreeOpened()) {
						// Since we're not loading children, don't mark it as open either
						$child->markClosed();
					}
					$output .= "</li>\n";
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
	 *
	 * The method performs a breadth-first traversal until the number of nodes is more than minCount. This is used to
	 * get a limited number of tree nodes to show in the CMS initially.
	 *
	 * This method returns the number of nodes marked.  After this method is called other methods can check
	 * {@link isExpanded()} and {@link isMarked()} on individual nodes.
	 *
	 * @param int $nodeCountThreshold See {@link getChildrenAsUL()}
	 * @return int The actual number of nodes marked.
	 */
	public function markPartialTree($nodeCountThreshold = 30, $context = null,
			$childrenMethod = "AllChildrenIncludingDeleted", $numChildrenMethod = "numChildren") {

		if(!is_numeric($nodeCountThreshold)) $nodeCountThreshold = 30;

		$this->markedNodes = array($this->owner->ID => $this->owner);
		$this->owner->markUnexpanded();

		// foreach can't handle an ever-growing $nodes list
		while(list($id, $node) = each($this->markedNodes)) {
			$children = $this->markChildren($node, $context, $childrenMethod, $numChildrenMethod);
			if($nodeCountThreshold && sizeof($this->markedNodes) > $nodeCountThreshold) {
				// Undo marking children as opened since they're lazy loaded
				if($children) foreach($children as $child) $child->markClosed();
				break;
			}
		}
		return sizeof($this->markedNodes);
	}

	/**
	 * Filter the marking to only those object with $node->$parameterName == $parameterValue
	 *
	 * @param string $parameterName  The parameter on each node to check when marking.
	 * @param mixed  $parameterValue The value the parameter must be to be marked.
	 */
	public function setMarkingFilter($parameterName, $parameterValue) {
		$this->markingFilter = array(
			"parameter" => $parameterName,
			"value" => $parameterValue
		);
	}

	/**
	 * Filter the marking to only those where the function returns true. The node in question will be passed to the
	 * function.
	 *
	 * @param string $funcName The name of the function to call
	 */
	public function setMarkingFilterFunction($funcName) {
		$this->markingFilter = array(
			"func" => $funcName,
		);
	}

	/**
	 * Returns true if the marking filter matches on the given node.
	 *
	 * @param DataObject $node Node to check
	 * @return bool
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
	 *
	 * @param DataObject $node              Parent node
	 * @param mixed      $context
	 * @param string     $childrenMethod    The name of the instance method to call to get the object's list of children
	 * @param string     $numChildrenMethod The name of the instance method to call to count the object's children
	 * @return DataList
	 */
	public function markChildren($node, $context = null, $childrenMethod = "AllChildrenIncludingDeleted",
			$numChildrenMethod = "numChildren") {
		if($node->hasMethod($childrenMethod)) {
			$children = $node->$childrenMethod($context);
		} else {
			user_error(sprintf("Can't find the method '%s' on class '%s' for getting tree children",
				$childrenMethod, get_class($node)), E_USER_ERROR);
		}

		$node->markExpanded();
		if($children) {
			foreach($children as $child) {
				$markingMatches = $this->markingFilterMatches($child);
				if($markingMatches) {
					// Mark a child node as unexpanded if it has children and has not already been expanded
					if($child->$numChildrenMethod() && !$child->isExpanded()) {
						$child->markUnexpanded();
					} else {
						$child->markExpanded();
					}
					$this->markedNodes[$child->ID] = $child;
				}
			}
		}

		return $children;
	}

	/**
	 * Ensure marked nodes that have children are also marked expanded. Call this after marking but before iterating
	 * over the tree.
	 *
	 * @param string $numChildrenMethod The name of the instance method to call to count the object's children
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
	 * Return CSS classes of 'unexpanded', 'closed', both, or neither, as well as a 'jstree-*' state depending on the
	 * marking of this DataObject.
	 *
	 * @param string $numChildrenMethod The name of the instance method to call to count the object's children
	 * @return string
	 */
	public function markingClasses($numChildrenMethod="numChildren") {
		$classes = '';
		if(!$this->isExpanded()) {
			$classes .= " unexpanded";
		}

		// Set jstree open state, or mark it as a leaf (closed) if there are no children
		if(!$this->owner->$numChildrenMethod()) {
			$classes .= " jstree-leaf closed";
		} elseif($this->isTreeOpened()) {
			$classes .= " jstree-open";
		} else {
			$classes .= " jstree-closed closed";
		}
		return $classes;
	}

	/**
	 * Mark the children of the DataObject with the given ID.
	 *
	 * @param int  $id   ID of parent node
	 * @param bool $open If this is true, mark the parent node as opened
	 * @return bool
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
	 *
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
	 * Return the IDs of all the marked nodes.
	 *
	 * @return array
	 */
	public function markedNodeIDs() {
		return array_keys($this->markedNodes);
	}

	/**
	 * Return an array of this page and its ancestors, ordered item -> root.
	 *
	 * @return SiteTree[]
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
	 * Cache of DataObjects' marked statuses: [ClassName][ID] = bool
	 * @var array
	 */
	protected static $marked = array();

	/**
	 * Cache of DataObjects' expanded statuses: [ClassName][ID] = bool
	 * @var array
	 */
	protected static $expanded = array();

	/**
	 * Cache of DataObjects' opened statuses: [ClassName][ID] = bool
	 * @var array
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
	 * Mark this DataObject's tree as closed.
	 */
	public function markClosed() {
		if(isset(self::$treeOpened[ClassInfo::baseDataClass($this->owner->class)][$this->owner->ID])) {
			unset(self::$treeOpened[ClassInfo::baseDataClass($this->owner->class)][$this->owner->ID]);
		}
	}

	/**
	 * Check if this DataObject is marked.
	 *
	 * @return bool
	 */
	public function isMarked() {
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		$id = $this->owner->ID;
		return isset(self::$marked[$baseClass][$id]) ? self::$marked[$baseClass][$id] : false;
	}

	/**
	 * Check if this DataObject is expanded.
	 *
	 * @return bool
	 */
	public function isExpanded() {
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		$id = $this->owner->ID;
		return isset(self::$expanded[$baseClass][$id]) ? self::$expanded[$baseClass][$id] : false;
	}

	/**
	 * Check if this DataObject's tree is opened.
	 *
	 * @return bool
	 */
	public function isTreeOpened() {
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		$id = $this->owner->ID;
		return isset(self::$treeOpened[$baseClass][$id]) ? self::$treeOpened[$baseClass][$id] : false;
	}

	/**
	 * Get a list of this DataObject's and all it's descendants IDs.
	 *
	 * @return int[]
	 */
	public function getDescendantIDList() {
		$idList = array();
		$this->loadDescendantIDListInto($idList);
		return $idList;
	}

	/**
	 * Get a list of this DataObject's and all it's descendants ID, and put them in $idList.
	 *
	 * @param array $idList Array to put results in.
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
	 *
	 * @return DataList
	 */
	public function Children() {
		if(!(isset($this->_cache_children) && $this->_cache_children)) {
			$result = $this->owner->stageChildren(false);
			$children = array();
			foreach ($result as $record) {
				if ($record->canView()) {
					$children[] = $record;
				}
			}
			$this->_cache_children = new ArrayList($children);
		}
		return $this->_cache_children;
	}

	/**
	 * Return all children, including those 'not in menus'.
	 *
	 * @return DataList
	 */
	public function AllChildren() {
		return $this->owner->stageChildren(true);
	}

	/**
	 * Return all children, including those that have been deleted but are still in live.
	 * - Deleted children will be marked as "DeletedFromStage"
	 * - Added children will be marked as "AddedToStage"
	 * - Modified children will be marked as "ModifiedOnStage"
	 * - Everything else has "SameOnStage" set, as an indicator that this information has been looked up.
	 *
	 * @param mixed $context
	 * @return ArrayList
	 */
	public function AllChildrenIncludingDeleted($context = null) {
		return $this->doAllChildrenIncludingDeleted($context);
	}

	/**
	 * @see AllChildrenIncludingDeleted
	 *
	 * @param mixed $context
	 * @return ArrayList
	 */
	public function doAllChildrenIncludingDeleted($context = null) {
		if(!$this->owner) user_error('Hierarchy::doAllChildrenIncludingDeleted() called without $this->owner');

		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		if($baseClass) {
			$stageChildren = $this->owner->stageChildren(true);

			// Add live site content that doesn't exist on the stage site, if required.
			if($this->owner->hasExtension('Versioned')) {
				// Next, go through the live children.  Only some of these will be listed
				$liveChildren = $this->owner->liveChildren(true, true);
				if($liveChildren) {
					$merged = new ArrayList();
					$merged->merge($stageChildren);
					$merged->merge($liveChildren);
					$stageChildren = $merged;
				}
			}

			$this->owner->extend("augmentAllChildrenIncludingDeleted", $stageChildren, $context);

		} else {
			user_error("Hierarchy::AllChildren() Couldn't determine base class for '{$this->owner->class}'",
				E_USER_ERROR);
		}

		return $stageChildren;
	}

	/**
	 * Return all the children that this page had, including pages that were deleted from both stage & live.
	 *
	 * @return DataList
	 * @throws Exception
	 */
	public function AllHistoricalChildren() {
		if(!$this->owner->hasExtension('Versioned')) {
			throw new Exception('Hierarchy->AllHistoricalChildren() only works with Versioned extension applied');
		}

		$baseClass=ClassInfo::baseDataClass($this->owner->class);
		return Versioned::get_including_deleted($baseClass,
			"\"ParentID\" = " . (int)$this->owner->ID, "\"$baseClass\".\"ID\" ASC");
	}

	/**
	 * Return the number of children that this page ever had, including pages that were deleted.
	 *
	 * @return int
	 * @throws Exception
	 */
	public function numHistoricalChildren() {
		if(!$this->owner->hasExtension('Versioned')) {
			throw new Exception('Hierarchy->AllHistoricalChildren() only works with Versioned extension applied');
		}

		return Versioned::get_including_deleted(ClassInfo::baseDataClass($this->owner->class),
			"\"ParentID\" = " . (int)$this->owner->ID)->count();
	}

	/**
	 * Return the number of direct children. By default, values are cached after the first invocation. Can be
	 * augumented by {@link augmentNumChildrenCountQuery()}.
	 *
	 * @param bool $cache Whether to retrieve values from cache
	 * @return int
	 */
	public function numChildren($cache = true) {
		// Build the cache for this class if it doesn't exist.
		if(!$cache || !is_numeric($this->_cache_numChildren)) {
			// Hey, this is efficient now!
			// We call stageChildren(), because Children() has canView() filtering
			$this->_cache_numChildren = (int)$this->owner->stageChildren(true)->Count();
		}

		// If theres no value in the cache, it just means that it doesn't have any children.
		return $this->_cache_numChildren;
	}

	/**
	 * Checks if we're on a controller where we should filter. ie. Are we loading the SiteTree?
	 *
	 * @return bool
	 */
	public function showingCMSTree() {
		if (!Controller::has_curr()) return false;
		$controller = Controller::curr();
		return $controller instanceof LeftAndMain
			&& in_array($controller->getAction(), array("treeview", "listview", "getsubtree"));
	}

	/**
	 * Return children in the stage site.
	 *
	 * @param bool $showAll Include all of the elements, even those not shown in the menus. Only applicable when
	 *                      extension is applied to {@link SiteTree}.
	 * @return DataList
	 */
	public function stageChildren($showAll = false) {
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		$hide_from_hierarchy = $this->owner->config()->hide_from_hierarchy;
		$hide_from_cms_tree = $this->owner->config()->hide_from_cms_tree;
		$staged = $baseClass::get()
				->filter('ParentID', (int)$this->owner->ID)
				->exclude('ID', (int)$this->owner->ID);
		if ($hide_from_hierarchy) {
			$staged = $staged->exclude('ClassName', $hide_from_hierarchy);
		}
		if ($hide_from_cms_tree && $this->showingCMSTree()) {
			$staged = $staged->exclude('ClassName', $hide_from_cms_tree);
		}
		if (!$showAll && $this->owner->db('ShowInMenus')) {
			$staged = $staged->filter('ShowInMenus', 1);
		}
		$this->owner->extend("augmentStageChildren", $staged, $showAll);
		return $staged;
	}

	/**
	 * Return children in the live site, if it exists.
	 *
	 * @param bool $showAll              Include all of the elements, even those not shown in the menus. Only
	 *                                   applicable when extension is applied to {@link SiteTree}.
	 * @param bool $onlyDeletedFromStage Only return items that have been deleted from stage
	 * @return DataList
	 * @throws Exception
	 */
	public function liveChildren($showAll = false, $onlyDeletedFromStage = false) {
		if(!$this->owner->hasExtension('Versioned')) {
			throw new Exception('Hierarchy->liveChildren() only works with Versioned extension applied');
		}

		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		$hide_from_hierarchy = $this->owner->config()->hide_from_hierarchy;
		$hide_from_cms_tree = $this->owner->config()->hide_from_cms_tree;
		$children = $baseClass::get()
			->filter('ParentID', (int)$this->owner->ID)
			->exclude('ID', (int)$this->owner->ID)
			->setDataQueryParam(array(
				'Versioned.mode' => $onlyDeletedFromStage ? 'stage_unique' : 'stage',
				'Versioned.stage' => 'Live'
			));
		if ($hide_from_hierarchy) {
			$children = $children->exclude('ClassName', $hide_from_hierarchy);
		}
		if ($hide_from_cms_tree && $this->showingCMSTree()) {
			$children = $children->exclude('ClassName', $hide_from_cms_tree);
		}
		if(!$showAll && $this->owner->db('ShowInMenus')) $children = $children->filter('ShowInMenus', 1);

		return $children;
	}

	/**
	 * Get this object's parent, optionally filtered by an SQL clause. If the clause doesn't match the parent, nothing
	 * is returned.
	 *
	 * @param string $filter
	 * @return DataObject
	 */
	public function getParent($filter = null) {
		if($p = $this->owner->__get("ParentID")) {
			$tableClasses = ClassInfo::dataClassesFor($this->owner->class);
			$baseClass = array_shift($tableClasses);
			return DataObject::get_one($this->owner->class, array(
				array("\"$baseClass\".\"ID\"" => $p),
				$filter
			));
		}
	}

	/**
	 * Return all the parents of this class in a set ordered from the lowest to highest parent.
	 *
	 * @return ArrayList
	 */
	public function getAncestors() {
		$ancestors = new ArrayList();
		$object    = $this->owner;

		while($object = $object->getParent()) {
			$ancestors->push($object);
		}

		return $ancestors;
	}

	/**
	 * Returns a human-readable, flattened representation of the path to the object, using its {@link Title} attribute.
	 *
	 * @param string $separator
	 * @return string
	 */
	public function getBreadcrumbs($separator = ' &raquo; ') {
		$crumbs = array();
		$ancestors = array_reverse($this->owner->getAncestors()->toArray());
		foreach($ancestors as $ancestor) $crumbs[] = $ancestor->Title;
		$crumbs[] = $this->owner->Title;
		return implode($separator, $crumbs);
	}

	/**
	 * Get the next node in the tree of the type. If there is no instance of the className descended from this node,
	 * then search the parents.
	 *
	 * @todo Write!
	 *
	 * @param string     $className Class name of the node to find
	 * @param DataObject $afterNode Used for recursive calls to this function
	 * @return DataObject
	 */
	public function naturalPrev($className, $afterNode = null ) {
		return null;
	}

	/**
	 * Get the next node in the tree of the type. If there is no instance of the className descended from this node,
	 * then search the parents.
	 * @param string     $className Class name of the node to find.
	 * @param string|int $root      ID/ClassName of the node to limit the search to
	 * @param DataObject $afterNode Used for recursive calls to this function
	 * @return DataObject
	 */
	public function naturalNext($className = null, $root = 0, $afterNode = null ) {
		// If this node is not the node we are searching from, then we can possibly return this node as a solution
		if($afterNode && $afterNode->ID != $this->owner->ID) {
			if(!$className || ($className && $this->owner->class == $className)) {
				return $this->owner;
			}
		}

		$nextNode = null;
		$baseClass = ClassInfo::baseDataClass($this->owner->class);

		$children = $baseClass::get()
			->filter('ParentID', (int)$this->owner->ID)
			->sort('"Sort"', 'ASC');
		if ($afterNode) {
			$children = $children->filter('Sort:GreaterThan', $afterNode->Sort);
		}

		// Try all the siblings of this node after the given node
		/*if( $siblings = DataObject::get( ClassInfo::baseDataClass($this->owner->class),
		"\"ParentID\"={$this->owner->ParentID}" . ( $afterNode ) ? "\"Sort\"
		> {$afterNode->Sort}" : "" , '\"Sort\" ASC' ) ) $searchNodes->merge( $siblings );*/

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
		if(!(is_numeric($root) && $root == $this->owner->ID || $root == $this->owner->class)
				&& ($parent = $this->owner->Parent())) {

			return $parent->naturalNext( $className, $root, $this->owner );
		}

		return null;
	}

	/**
	 * Flush all Hierarchy caches:
	 * - Children (instance)
	 * - NumChildren (instance)
	 * - Marked (global)
	 * - Expanded (global)
	 * - TreeOpened (global)
	 */
	public function flushCache() {
		$this->_cache_children = null;
		$this->_cache_numChildren = null;
		self::$marked = array();
		self::$expanded = array();
		self::$treeOpened = array();
	}

	/**
	 * Reset global Hierarchy caches:
	 * - Marked
	 * - Expanded
	 * - TreeOpened
	 */
	public static function reset() {
		self::$marked = array();
		self::$expanded = array();
		self::$treeOpened = array();
	}

}
