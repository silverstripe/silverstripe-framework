<?php

class HierarchyTest extends SapphireTest {

	protected static $fixture_file = 'HierarchyTest.yml';

	protected $requiredExtensions = array(
		'HierarchyTest_Object' => array('Hierarchy', 'Versioned')
	);

	protected $extraDataObjects = array(
		'HierarchyTest_Object'
	);

	/**
	 * Test the Hierarchy prevents infinite loops.
	 */
	public function testPreventLoop() {
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2aa = $this->objFromFixture('HierarchyTest_Object', 'obj2aa');

		$obj2->ParentID = $obj2aa->ID;
		try {
			$obj2->write();
		}
		catch (ValidationException $e) {
			$this->assertContains('Infinite loop found within the "HierarchyTest_Object" hierarchy', $e->getMessage());
			return;
		}

		$this->fail('Failed to prevent infinite loop in hierarchy.');
	}

	/**
	 * Test Hierarchy::AllHistoricalChildren().
	 */
	public function testAllHistoricalChildren() {
		// Delete some objs
		$this->objFromFixture('HierarchyTest_Object', 'obj2b')->delete();
		$this->objFromFixture('HierarchyTest_Object', 'obj3a')->delete();
		$this->objFromFixture('HierarchyTest_Object', 'obj3')->delete();

		// Check that obj1-3 appear at the top level of the AllHistoricalChildren tree
		$this->assertEquals(array("Obj 1", "Obj 2", "Obj 3"),
			singleton('HierarchyTest_Object')->AllHistoricalChildren()->column('Title'));

		// Check numHistoricalChildren
		$this->assertEquals(3, singleton('HierarchyTest_Object')->numHistoricalChildren());

		// Check that both obj 2 children are returned
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$this->assertEquals(array("Obj 2a", "Obj 2b"),
			$obj2->AllHistoricalChildren()->column('Title'));

		// Check numHistoricalChildren
		$this->assertEquals(2, $obj2->numHistoricalChildren());


		// Obj 3 has been deleted; let's bring it back from the grave
		$obj3 = Versioned::get_including_deleted("HierarchyTest_Object", "\"Title\" = 'Obj 3'")->First();

		// Check that both obj 3 children are returned
		$this->assertEquals(array("Obj 3a", "Obj 3b", "Obj 3c"),
			$obj3->AllHistoricalChildren()->column('Title'));

		// Check numHistoricalChildren
		$this->assertEquals(3, $obj3->numHistoricalChildren());

	}

	/**
	 * Test that you can call Hierarchy::markExpanded/Unexpanded/Open() on a obj, and that
	 * calling Hierarchy::isMarked() on a different instance of that object will return true.
	 */
	public function testItemMarkingIsntRestrictedToSpecificInstance() {
		// Mark a few objs
		$this->objFromFixture('HierarchyTest_Object', 'obj2')->markExpanded();
		$this->objFromFixture('HierarchyTest_Object', 'obj2a')->markExpanded();
		$this->objFromFixture('HierarchyTest_Object', 'obj2b')->markExpanded();
		$this->objFromFixture('HierarchyTest_Object', 'obj3')->markUnexpanded();

		// Query some objs in a different context and check their m
		$objs = DataObject::get("HierarchyTest_Object", '', '"ID" ASC');
		$marked = $expanded = array();
		foreach($objs as $obj) {
			if($obj->isMarked()) $marked[] = $obj->Title;
			if($obj->isExpanded()) $expanded[] = $obj->Title;
		}

		$this->assertEquals(array('Obj 2', 'Obj 3', 'Obj 2a', 'Obj 2b'), $marked);
		$this->assertEquals(array('Obj 2', 'Obj 2a', 'Obj 2b'), $expanded);
	}

	public function testNumChildren() {
		$this->assertEquals($this->objFromFixture('HierarchyTest_Object', 'obj1')->numChildren(), 0);
		$this->assertEquals($this->objFromFixture('HierarchyTest_Object', 'obj2')->numChildren(), 2);
		$this->assertEquals($this->objFromFixture('HierarchyTest_Object', 'obj3')->numChildren(), 3);
		$this->assertEquals($this->objFromFixture('HierarchyTest_Object', 'obj2a')->numChildren(), 2);
		$this->assertEquals($this->objFromFixture('HierarchyTest_Object', 'obj2b')->numChildren(), 0);
		$this->assertEquals($this->objFromFixture('HierarchyTest_Object', 'obj3a')->numChildren(), 2);
		$this->assertEquals($this->objFromFixture('HierarchyTest_Object', 'obj3b')->numChildren(), 0);

		$obj1 = $this->objFromFixture('HierarchyTest_Object', 'obj1');
		$this->assertEquals($obj1->numChildren(), 0);
		$obj1Child1 = new HierarchyTest_Object();
		$obj1Child1->ParentID = $obj1->ID;
		$obj1Child1->write();
		$this->assertEquals($obj1->numChildren(false), 1,
			'numChildren() caching can be disabled through method parameter'
		);
		$obj1Child2 = new HierarchyTest_Object();
		$obj1Child2->ParentID = $obj1->ID;
		$obj1Child2->write();
		$obj1->flushCache();
		$this->assertEquals($obj1->numChildren(), 2,
			'numChildren() caching can be disabled by flushCache()'
		);
	}

	public function testLoadDescendantIDListIntoArray() {
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2a = $this->objFromFixture('HierarchyTest_Object', 'obj2a');
		$obj2b = $this->objFromFixture('HierarchyTest_Object', 'obj2b');
		$obj2aa = $this->objFromFixture('HierarchyTest_Object', 'obj2aa');
		$obj2ab = $this->objFromFixture('HierarchyTest_Object', 'obj2ab');

		$obj2IdList = $obj2->getDescendantIDList();
		$obj2aIdList = $obj2a->getDescendantIDList();

		$this->assertContains($obj2a->ID, $obj2IdList);
		$this->assertContains($obj2b->ID, $obj2IdList);
		$this->assertContains($obj2aa->ID, $obj2IdList);
		$this->assertContains($obj2ab->ID, $obj2IdList);
		$this->assertEquals(4, count($obj2IdList));

		$this->assertContains($obj2aa->ID, $obj2aIdList);
		$this->assertContains($obj2ab->ID, $obj2aIdList);
		$this->assertEquals(2, count($obj2aIdList));
	}

	/**
	 * The "only deleted from stage" argument to liveChildren() should exclude
	 * any page that has been moved to another location on the stage site
	 */
	public function testLiveChildrenOnlyDeletedFromStage() {
		$obj1 = $this->objFromFixture('HierarchyTest_Object', 'obj1');
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2a = $this->objFromFixture('HierarchyTest_Object', 'obj2a');
		$obj2b = $this->objFromFixture('HierarchyTest_Object', 'obj2b');

		// Get a published set of objects for our fixture
		$obj1->publish("Stage", "Live");
		$obj2->publish("Stage", "Live");
		$obj2a->publish("Stage", "Live");
		$obj2b->publish("Stage", "Live");

		// Then delete 2a from stage and move 2b to a sub-node of 1.
		$obj2a->delete();
		$obj2b->ParentID = $obj1->ID;
		$obj2b->write();

		// Get live children, excluding pages that have been moved on the stage site
		$children = $obj2->liveChildren(true, true)->column("Title");

		// 2a has been deleted from stage and should be shown
		$this->assertContains("Obj 2a", $children);

		// 2b has merely been moved to a different parent and so shouldn't be shown
		$this->assertNotContains("Obj 2b", $children);
	}

	public function testBreadcrumbs() {
		$obj1 = $this->objFromFixture('HierarchyTest_Object', 'obj1');
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2a = $this->objFromFixture('HierarchyTest_Object', 'obj2a');
		$obj2aa = $this->objFromFixture('HierarchyTest_Object', 'obj2aa');

		$this->assertEquals('Obj 1', $obj1->getBreadcrumbs());
		$this->assertEquals('Obj 2 &raquo; Obj 2a', $obj2a->getBreadcrumbs());
		$this->assertEquals('Obj 2 &raquo; Obj 2a &raquo; Obj 2aa', $obj2aa->getBreadcrumbs());
	}

	public function testGetChildrenAsUL() {
		$obj1 = $this->objFromFixture('HierarchyTest_Object', 'obj1');
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2a = $this->objFromFixture('HierarchyTest_Object', 'obj2a');
		$obj2aa = $this->objFromFixture('HierarchyTest_Object', 'obj2aa');

		$nodeCountThreshold = 30;

		$root = new HierarchyTest_Object();
		$root->markPartialTree($nodeCountThreshold);
		$html = $root->getChildrenAsUL(
			"",
			'"<li id=\"" . $child->ID . "\">" . $child->Title',
			null,
			false,
			"AllChildrenIncludingDeleted",
			"numChildren",
			true,  // rootCall
			$nodeCountThreshold
		);
		$this->assertTreeContains($html, array($obj2),
			'Contains root elements'
		);
		$this->assertTreeContains($html, array($obj2, $obj2a),
			'Contains child elements (in correct nesting)'
		);
		$this->assertTreeContains($html, array($obj2, $obj2a, $obj2aa),
			'Contains grandchild elements (in correct nesting)'
		);
	}

	public function testGetChildrenAsULMinNodeCount() {
		$obj1 = $this->objFromFixture('HierarchyTest_Object', 'obj1');
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2a = $this->objFromFixture('HierarchyTest_Object', 'obj2a');

		// Set low enough that it should be fulfilled by root only elements
		$nodeCountThreshold = 3;

		$root = new HierarchyTest_Object();
		$root->markPartialTree($nodeCountThreshold);
		$html = $root->getChildrenAsUL(
			"",
			'"<li id=\"" . $child->ID . "\">" . $child->Title',
			null,
			false,
			"AllChildrenIncludingDeleted",
			"numChildren",
			true,
			$nodeCountThreshold
		);
		$this->assertTreeContains($html, array($obj1),
			'Contains root elements'
		);
		$this->assertTreeContains($html, array($obj2),
			'Contains root elements'
		);
		$this->assertTreeNotContains($html, array($obj2, $obj2a),
			'Does not contains child elements because they exceed minNodeCount'
		);
	}

	public function testGetChildrenAsULMinNodeCountWithMarkToExpose() {
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2a = $this->objFromFixture('HierarchyTest_Object', 'obj2a');
		$obj2aa = $this->objFromFixture('HierarchyTest_Object', 'obj2aa');

		// Set low enough that it should be fulfilled by root only elements
		$nodeCountThreshold = 3;

		$root = new HierarchyTest_Object();
		$root->markPartialTree($nodeCountThreshold);

		// Mark certain node which should be included regardless of minNodeCount restrictions
		$root->markToExpose($obj2aa);

		$html = $root->getChildrenAsUL(
			"",
			'"<li id=\"" . $child->ID . "\">" . $child->Title',
			null,
			false,
			"AllChildrenIncludingDeleted",
			"numChildren",
			true,
			$nodeCountThreshold
		);
		$this->assertTreeContains($html, array($obj2),
			'Contains root elements'
		);
		$this->assertTreeContains($html, array($obj2, $obj2a, $obj2aa),
			'Does contain marked children nodes regardless of configured threshold'
		);
	}

	public function testGetChildrenAsULMinNodeCountWithFilters() {
		$obj1 = $this->objFromFixture('HierarchyTest_Object', 'obj1');
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2a = $this->objFromFixture('HierarchyTest_Object', 'obj2a');
		$obj2aa = $this->objFromFixture('HierarchyTest_Object', 'obj2aa');

		// Set low enough that it should fit all search matches without lazy loading
		$nodeCountThreshold = 3;

		$root = new HierarchyTest_Object();

		// Includes nodes by filter regardless of minNodeCount restrictions
		$root->setMarkingFilterFunction(function($record) use($obj2, $obj2a, $obj2aa) {
			// Results need to include parent hierarchy, even if we just want to
			// match the innermost node.
			return in_array($record->ID, array($obj2->ID, $obj2a->ID, $obj2aa->ID));
		});
		$root->markPartialTree($nodeCountThreshold);

		$html = $root->getChildrenAsUL(
			"",
			'"<li id=\"" . $child->ID . "\">" . $child->Title',
			null,
			true, // limit to marked
			"AllChildrenIncludingDeleted",
			"numChildren",
			true,
			$nodeCountThreshold
		);
		$this->assertTreeNotContains($html, array($obj1),
			'Does not contain root elements which dont match the filter'
		);
		$this->assertTreeContains($html, array($obj2, $obj2a, $obj2aa),
			'Contains non-root elements which match the filter'
		);
	}

	public function testGetChildrenAsULHardLimitsNodes() {
		$obj1 = $this->objFromFixture('HierarchyTest_Object', 'obj1');
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2a = $this->objFromFixture('HierarchyTest_Object', 'obj2a');
		$obj2aa = $this->objFromFixture('HierarchyTest_Object', 'obj2aa');

		// Set low enough that it should fit all search matches without lazy loading
		$nodeCountThreshold = 3;

		$root = new HierarchyTest_Object();

		// Includes nodes by filter regardless of minNodeCount restrictions
		$root->setMarkingFilterFunction(function($record) use($obj2, $obj2a, $obj2aa) {
			// Results need to include parent hierarchy, even if we just want to
			// match the innermost node.
			return in_array($record->ID, array($obj2->ID, $obj2a->ID, $obj2aa->ID));
		});
		$root->markPartialTree($nodeCountThreshold);

		$html = $root->getChildrenAsUL(
			"",
			'"<li id=\"" . $child->ID . "\">" . $child->Title',
			null,
			true, // limit to marked
			"AllChildrenIncludingDeleted",
			"numChildren",
			true,
			$nodeCountThreshold
		);
		$this->assertTreeNotContains($html, array($obj1),
			'Does not contain root elements which dont match the filter'
		);
		$this->assertTreeContains($html, array($obj2, $obj2a, $obj2aa),
			'Contains non-root elements which match the filter'
		);
	}

	public function testGetChildrenAsULNodeThresholdLeaf() {
		$obj1 = $this->objFromFixture('HierarchyTest_Object', 'obj1');
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2a = $this->objFromFixture('HierarchyTest_Object', 'obj2a');
		$obj3 = $this->objFromFixture('HierarchyTest_Object', 'obj3');
		$obj3a = $this->objFromFixture('HierarchyTest_Object', 'obj3a');

		$nodeCountThreshold = 99;

		$root = new HierarchyTest_Object();
		$root->markPartialTree($nodeCountThreshold);
		$nodeCountCallback = function($parent, $numChildren) {
			// Set low enough that it the fixture structure should exceed it
			if($parent->ID && $numChildren > 2) {
				return '<span class="exceeded">Exceeded!</span>';
			}
		};

		$html = $root->getChildrenAsUL(
			"",
			'"<li id=\"" . $child->ID . "\">" . $child->Title',
			null,
			true, // limit to marked
			"AllChildrenIncludingDeleted",
			"numChildren",
			true,
			$nodeCountThreshold,
			$nodeCountCallback
		);
		$this->assertTreeContains($html, array($obj1),
			'Does contain root elements regardless of count'
		);
		$this->assertTreeContains($html, array($obj3),
			'Does contain root elements regardless of count'
		);
		$this->assertTreeContains($html, array($obj2, $obj2a),
			'Contains children which do not exceed threshold'
		);
		$this->assertTreeNotContains($html, array($obj3, $obj3a),
			'Does not contain children which exceed threshold'
		);
	}

	/**
	 * This test checks that deleted ('archived') child pages don't set a css class on the parent
	 * node that makes it look like it has children
	 */
	public function testGetChildrenAsULNodeDeletedOnLive() {
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2a = $this->objFromFixture('HierarchyTest_Object', 'obj2a');
		$obj2aa = $this->objFromFixture('HierarchyTest_Object', 'obj2aa');
		$obj2ab = $this->objFromFixture('HierarchyTest_Object', 'obj2b');

		// delete all children under obj2
		$obj2a->delete();
		$obj2aa->delete();
		$obj2ab->delete();
		// Don't pre-load all children
		$nodeCountThreshold = 1;

		$childrenMethod = 'AllChildren';
		$numChildrenMethod = 'numChildren';

		$root = new HierarchyTest_Object();
		$root->markPartialTree($nodeCountThreshold, null, $childrenMethod, $numChildrenMethod);

		// As in LeftAndMain::getSiteTreeFor() but simpler and more to the point for testing purposes
		$titleFn = function(&$child, $numChildrenMethod="") {
			return '<li class="' . $child->markingClasses($numChildrenMethod).
				'" id="' . $child->ID . '">"' . $child->Title;
		};

		$html = $root->getChildrenAsUL(
			"",
			$titleFn,
			null,
			true, // limit to marked
			$childrenMethod,
			$numChildrenMethod,
			true,
			$nodeCountThreshold
		);

		// Get the class attribute from the $obj2 node in the sitetree, class 'jstree-leaf' means it's a leaf node
		$nodeClass = $this->getNodeClassFromTree($html, $obj2);
		$this->assertEquals('jstree-leaf closed', $nodeClass, 'object2 should not have children in the sitetree');
	}

	/**
	 * This test checks that deleted ('archived') child pages _do_ set a css class on the parent
	 * node that makes it look like it has children when getting all children including deleted
	 */
	public function testGetChildrenAsULNodeDeletedOnStage() {
		$obj2 = $this->objFromFixture('HierarchyTest_Object', 'obj2');
		$obj2a = $this->objFromFixture('HierarchyTest_Object', 'obj2a');
		$obj2aa = $this->objFromFixture('HierarchyTest_Object', 'obj2aa');
		$obj2ab = $this->objFromFixture('HierarchyTest_Object', 'obj2b');

		// delete all children under obj2
		$obj2a->delete();
		$obj2aa->delete();
		$obj2ab->delete();
		// Don't pre-load all children
		$nodeCountThreshold = 1;

		$childrenMethod = 'AllChildrenIncludingDeleted';
		$numChildrenMethod = 'numHistoricalChildren';

		$root = new HierarchyTest_Object();
		$root->markPartialTree($nodeCountThreshold, null, $childrenMethod, $numChildrenMethod);

		// As in LeftAndMain::getSiteTreeFor() but simpler and more to the point for testing purposes
		$titleFn = function(&$child, $numChildrenMethod="") {
			return '<li class="' . $child->markingClasses($numChildrenMethod).
				'" id="' . $child->ID . '">"' . $child->Title;
		};

		$html = $root->getChildrenAsUL(
			"",
			$titleFn,
			null,
			true, // limit to marked
			$childrenMethod,
			$numChildrenMethod,
			true,
			$nodeCountThreshold
		);

		// Get the class attribute from the $obj2 node in the sitetree
		$nodeClass = $this->getNodeClassFromTree($html, $obj2);
		// Object2 can now be expanded
		$this->assertEquals('unexpanded jstree-closed closed', $nodeClass, 'obj2 should have children in the sitetree');
	}

	/**
	 * @param String $html  [description]
	 * @param array $nodes Breadcrumb path as array
	 * @param String $message
	 */
	protected function assertTreeContains($html, $nodes, $message = null) {
		$parser = new CSSContentParser($html);
		$xpath = '/';
		foreach($nodes as $node) $xpath .= '/ul/li[@id="' . $node->ID . '"]';
		$match = $parser->getByXpath($xpath);
		self::assertThat((bool)$match, self::isTrue(), $message);
	}

	/**
	 * @param String $html  [description]
	 * @param array $nodes Breadcrumb path as array
	 * @param String $message
	 */
	protected function assertTreeNotContains($html, $nodes, $message = null) {
		$parser = new CSSContentParser($html);
		$xpath = '/';
		foreach($nodes as $node) $xpath .= '/ul/li[@id="' . $node->ID . '"]';
		$match = $parser->getByXpath($xpath);
		self::assertThat((bool)$match, self::isFalse(), $message);
	}

	/**
	 * Get the HTML class attribute from a node in the sitetree
	 *
	 * @param $html
	 * @param $node
	 * @return string
	 */
	protected function getNodeClassFromTree($html, $node) {
		$parser = new CSSContentParser($html);
		$xpath = '//ul/li[@id="' . $node->ID . '"]';
		$object = $parser->getByXpath($xpath);

		foreach($object[0]->attributes() as $key => $attr) {
			if($key == 'class') {
				return (string)$attr;
			}
		}
		return '';
	}
}

class HierarchyTest_Object extends DataObject implements TestOnly {
	private static $db = array(
		'Title' => 'Varchar'
	);

	private static $extensions = array(
		'Hierarchy',
		"Versioned('Stage', 'Live')",
	);

	public function cmstreeclasses() {
		return $this->markingClasses();
	}
}
