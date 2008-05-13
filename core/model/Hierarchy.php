<?php

/**
 * @package sapphire
 * @subpackage model
 */

/**
 * DataObjects that use the Hierachy decorator can be be organised as a hierachy, with children and parents.
 * The most obvious example of this is SiteTree.
 * @package sapphire
 * @subpackage model
 */
class Hierarchy extends DataObjectDecorator {
	protected $markedNodes;
	protected $markingFilter;
	
	function augmentSQL(SQLQuery &$query) {
	}

	function augmentDatabase() {
	}
	
	function augmentWrite(&$manipulation) {
	}

	/**
	 * Returns the children of this DataObject as an XHTML UL. This will be called recursively on each child,
	 * so if they have children they will be displayed as a UL inside a LI.
	 * @param string $attributes Attributes to add to the UL.
	 * @param string $titleEval PHP code to evaluate to start each child - this should include '<li>'
	 * @param string $extraArg Extra arguments that will be passed on to children, for if they overload this function.
	 * @param boolean $limitToMarked Display only marked children.
	 * @param boolean $rootCall Set to true for this first call, and then to false for calls inside the recursion. You should not change this.
	 * @return string
	 */
	public function getChildrenAsUL($attributes = "", $titleEval = '"<li>" . $child->Title', $extraArg = null, $limitToMarked = false, $rootCall = true) {
		if($limitToMarked && $rootCall) {
			$this->markingFinished();
		}
		
		$children = $this->owner->AllChildrenIncludingDeleted();

		if($children) {
			if($attributes) {
				$attributes = " $attributes";
			}
			
			$output = "<ul$attributes>\n";
		
			foreach($children as $child) {
				if(!$limitToMarked || $child->isMarked()) {
					$foundAChild = true;
					$output .= eval("return $titleEval;") . "\n" . 
					$child->getChildrenAsUL("", $titleEval, $extraArg, $limitToMarked, false) . "</li>\n";
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
	 * @param int $minCount The minimum amount of nodes to mark.
	 * @return int The actual number of nodes marked.
	 */
	public function markPartialTree($minCount = 30) {
		$this->markedNodes = array($this->owner->ID => $this->owner);
		$this->owner->markUnexpanded();

		// foreach can't handle an ever-growing $nodes list
		while(list($id, $node) = each($this->markedNodes)) {
			$this->markChildren($node);
			
			if($minCount && sizeof($this->markedNodes) >= $minCount) {
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
		} else if ($funcName = $this->markingFilter['func']) {
			return $funcName($node);
		}
	}
	
	/**
	 * Mark all children of the given node that match the marking filter.
	 * @param DataObject $node Parent node.
	 */
	public function markChildren($node) {
		$children = $node->AllChildrenIncludingDeleted();
		$node->markExpanded();
		if($children) {
			foreach($children as $child) {
				if(!$this->markingFilter || $this->markingFilterMatches($child)) {
					$child->markUnexpanded();
					$this->markedNodes[$child->ID] = $child;
				}
			}
		}
	}
	
	/**
	 * Ensure marked nodes that have children are also marked expanded.
	 * Call this after marking but before iterating over the tree.
	 */
	protected function markingFinished() {
		// Mark childless nodes as expanded.
		foreach($this->markedNodes as $id => $node) {
			if(!$node->numChildren()) {
				$node->markExpanded();
			}
		}
	}
	
	/**
	 * Return CSS classes of 'unexpanded', 'closed', both, or neither, depending on
	 * the marking of this DataObject.
	 */
	public function markingClasses() {
		$classes = '';
		if(!$this->expanded) {
			$classes .= " unexpanded";
		}
		if(!$this->treeOpened) {
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
	protected $marked = false;
	
	/**
	 * True if this DataObject is expanded.
	 * @var boolean
	 */
	protected $expanded = false;
	
	/**
	 * True if this DataObject is opened.
	 * @var boolean
	 */
	protected $treeOpened = false;
	
	/**
	 * Mark this DataObject as expanded.
	 */
	public function markExpanded() {
		$this->marked = true;		
		$this->expanded = true;
	}
	
	/**
	 * Mark this DataObject as unexpanded.
	 */
	public function markUnexpanded() {
		$this->marked = true;		
		$this->expanded = false;
	}
	
	/**
	 * Mark this DataObject's tree as opened.
	 */
	public function markOpened() {
		$this->marked = true;
		$this->treeOpened = true;
	}
	
	/**
	 * Check if this DataObject is marked.
	 * @return boolean
	 */
	public function isMarked() {
		return $this->marked;
	}
	
	/**
	 * Check if this DataObject is expanded.
	 * @return boolean
	 */
	public function isExpanded() {
		return $this->expanded;
	}
	
	/**
	 * Check if this DataObject's tree is opened.
	 */
	public function isTreeOpened() {
		return $this->treeOpened;
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
				$child->loadDescendantIDListInto($idList);
			}
		}
	}
	
	/**
	 * Cached result for AllChildren().
	 * @var DataObjectSet
	 */
	protected $allChildren;
	
	/**
	 * Cached result for AllChildrenIncludingDeleted().
	 * @var DataObjectSet
	 */
	protected $allChildrenIncludingDeleted;
	
	/**
	 * Cached result for Children().
	 */
	protected $children;
	
	/**
	 * Get the children for this DataObject.
	 * @return DataObjectSet
	 */
	public function Children() {
		return $this->owner->stageChildren(false);
	}

	/**
	 * Return all children, including those 'not in menus'.
	 * @return DataObjectSet
	 */
	public function AllChildren() {
		// Cache the allChildren data, so that future requests will return the references to the same
		// object.  This allows the mark..() system to work appropriately.
		if(!$this->allChildren) {
			$this->allChildren = $this->owner->stageChildren(true);
		}
		
		return $this->allChildren;
	}

	/**
	 * Return all children, including those that have been deleted but are still in live.
	 * Deleted children will be marked as "DeletedFromStage"
	 * Added children will be marked as "AddedToStage"
	 * Modified children will be marked as "ModifiedOnStage"
	 * Everything else has "SameOnStage" set, as an indicator that this information has been looked up.
	 * @return DataObjectSet
	 */
	public function AllChildrenIncludingDeleted() {
		// Cache the allChildren data, so that future requests will return the references to the same
		// object.  This allows the mark..() system to work appropriately.

		if(!$this->allChildrenIncludingDeleted) {
			$baseClass = ClassInfo::baseDataClass($this->owner->class);
			if($baseClass) {
				$stageChildren = $this->owner->stageChildren(true);
				$this->allChildrenIncludingDeleted = $stageChildren;
				
				// Add live site content, if required.
				if($this->owner->hasExtension('Versioned')) {
					// Get all the requisite data, and index it
					$liveChildren = $this->owner->liveChildren(true);
					
					if(isset($stageChildren)) {
						foreach($stageChildren as $child) {
							$idxStageChildren[$child->ID] = $child;
						}
					}
					
					if(isset($liveChildren)) {
						foreach($liveChildren as $child) {
							$idxLiveChildren[$child->ID] = $child;
						}
					}
					
					if(isset($idxStageChildren)) {
						$foundInLive = Versioned::get_by_stage( $baseClass, 'Live', "`{$baseClass}`.`ID` IN (" . implode(",", array_keys($idxStageChildren)) . ")", "" );
					}
					
					if(isset($idxLiveChildren)) {
						$foundInStage = Versioned::get_by_stage( $baseClass, 'Stage', "`{$baseClass}`.`ID` IN (" . implode(",", array_keys($idxLiveChildren)) . ")", "" );
					}
					
					if(isset($foundInLive)) {
						foreach($foundInLive as $child) {
							$idxFoundInLive[$child->ID] = $child;
						}
					}
					
					if(isset($foundInStage)) {
						foreach($foundInStage as $child) {
							$idxFoundInStage[$child->ID] = $child;
						}
					}
					
					$this->allChildrenIncludingDeleted = new DataObjectSet();
					
					// First, go through the stage children.  They will all be listed but may be different colours
					if($stageChildren) {
						foreach($stageChildren as $child) {
							// Not found on live = new page
							if(!isset($idxFoundInLive[$child->ID]))  {
								$child->AddedToStage = true;
							
							// Version different on live = edited page
							} else if($idxFoundInLive[$child->ID]->Version != $child->Version) {
								$child->ModifiedOnStage = true;
							}
	
							$child->CheckedPublicationDifferences = true;
							$this->allChildrenIncludingDeleted->push($child);
						}
					}
					
					// Next, go through the live children.  Only some of these will be listed					
					if($liveChildren) {
						foreach($liveChildren as $child) {
							// Not found on stage = deleted page.  Anything else is ignored
							if(!isset($idxFoundInStage[$child->ID])) {
								$child->DeletedFromStage = true;
								$child->CheckedPublicationDifferences = true;
								$this->allChildrenIncludingDeleted->push($child);
							}
						}
					}
				}
				
			} else {
				user_error("Hierarchy::AllChildren() Couldn't determine base class for '{$this->owner->class}'", E_USER_ERROR);
			}
		}
		
		return $this->allChildrenIncludingDeleted;
	}

	/**
	 * Return the number of children
	 * @return int
	 */
	public function numChildren() {
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		// We build the query in an extension-friendly way.
		$query = new SQLQuery("COUNT(*)","`$baseClass`","ParentID = " . (int)$this->owner->ID);
		$this->owner->extend('augmentSQL', $query);
		return $query->execute()->value();
	}

	/**
	 * Return children from the stage site
	 * @param showAll Inlcude all of the elements, even those not shown in the menus.
	 * @return DataObjectSet
	 */
	public function stageChildren($showAll = false) {
		$extraFilter = $showAll ? '' : " AND ShowInMenus = 1";
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		return DataObject::get($baseClass, "`{$baseClass}`.`ParentID` = " . (int)$this->owner->ID . " AND `{$baseClass}`.ID != " . (int)$this->owner->ID . $extraFilter, "");
	}

	/**
	 * Return children from the live site, if it exists.
	 * @param boolean $showAll Include all of the elements, even those not shown in the menus.
	 * @return DataObjectSet
	 */
	public function liveChildren($showAll = false) {
		$extraFilter = $showAll ? '' : " AND ShowInMenus = 1";
		$baseClass = ClassInfo::baseDataClass($this->owner->class);
		return Versioned::get_by_stage($baseClass, "Live", "`{$baseClass}`.`ParentID` = " . (int)$this->owner->ID . " AND `{$baseClass}`.ID != " . (int)$this->owner->ID. $extraFilter, "");
	}
	
	/**
	 * Get the parent of this class.
	 * @return DataObject
	 */
	public function getParent($filter = '') {
		if($p = $this->owner->__get("ParentID")) {
			$className = $this->owner->class;
			$filter .= $filter?" AND ":""."`$className`.ID = $p";
			return DataObject::get_one($className, $filter);
		}
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
		
		// Try searching for the next node of the given class in each of the children, but treat each
		// child as the root of the search. This will stop the recursive call from searching backwards.
		// If afterNode is given, then only search for the nodes after 
		if(!$afterNode || $afterNode->ParentID != $this->owner->ID) {
			$children = $this->AllChildren();
		} else {
			$children = DataObject::get(ClassInfo::baseDataClass($this->owner->class), "`$baseClass`.`ParentID`={$this->owner->ID}" . ( ( $afterNode ) ? " AND `Sort` > " . sprintf( '%d', $afterNode->Sort ) : "" ), '`Sort` ASC');
		}
		
		// Try all the siblings of this node after the given node
		/*if( $siblings = DataObject::get( ClassInfo::baseDataClass($this->owner->class), "`ParentID`={$this->owner->ParentID}" . ( $afterNode ) ? "`Sort` > {$afterNode->Sort}" : "" , '`Sort` ASC' ) )
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
}

?>
