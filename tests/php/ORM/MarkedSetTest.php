<?php

namespace SilverStripe\ORM\Tests;

use DOMDocument;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Hierarchy\MarkedSet;
use SilverStripe\Versioned\Versioned;

/**
 * Test set of marked Hierarchy-extended DataObjects
 */
class MarkedSetTest extends SapphireTest
{
    protected static $fixture_file = 'HierarchyTest.yml';

    protected static $extra_dataobjects = array(
        HierarchyTest\TestObject::class,
        HierarchyTest\HideTestObject::class,
        HierarchyTest\HideTestSubObject::class,
    );

    public static function getExtraDataObjects()
    {
        // Prevent setup breaking if versioned module absent
        if (class_exists(Versioned::class)) {
            return parent::getExtraDataObjects();
        }
        return [];
    }

    public function setUp()
    {
        parent::setUp();

        // Note: Soft support for versioned module optionality
        if (!class_exists(Versioned::class)) {
            $this->markTestSkipped('MarkedSetTest requires the Versioned extension');
        }
    }


    /**
     * Test that you can call MarkedSet::markExpanded/Unexpanded/Open() on a obj, and that
     * calling MarkedSet::isMarked() on a different instance of that object will return true.
     */
    public function testItemMarkingIsntRestrictedToSpecificInstance()
    {
        // Build new object
        $set = new MarkedSet(HierarchyTest\TestObject::singleton());

        // Mark a few objs
        $set->markExpanded($this->objFromFixture(HierarchyTest\TestObject::class, 'obj2'));
        $set->markExpanded($this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a'));
        $set->markExpanded($this->objFromFixture(HierarchyTest\TestObject::class, 'obj2b'));
        $set->markUnexpanded($this->objFromFixture(HierarchyTest\TestObject::class, 'obj3'));

        // Query some objs in a different context and check their m
        $objs = DataObject::get(HierarchyTest\TestObject::class, '', '"ID" ASC');
        $marked = $expanded = array();
        foreach ($objs as $obj) {
            if ($set->isMarked($obj)) {
                $marked[] = $obj->Title;
            }
            if ($set->isExpanded($obj)) {
                $expanded[] = $obj->Title;
            }
        }
        $this->assertEquals(array('Obj 2', 'Obj 3', 'Obj 2a', 'Obj 2b'), $marked);
        $this->assertEquals(array('Obj 2', 'Obj 2a', 'Obj 2b'), $expanded);
    }

    /**
     * @covers \SilverStripe\ORM\Hierarchy\MarkedSet::markChildren()
     */
    public function testMarkChildrenDoesntUnmarkPreviouslyMarked()
    {
        $obj3 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj3');
        $obj3aa = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj3aa');
        $obj3ba = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj3ba');
        $obj3ca = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj3ca');

        $set = new MarkedSet($obj3);
        $set->markPartialTree();
        $set->markToExpose($obj3aa);
        $set->markToExpose($obj3ba);
        $set->markToExpose($obj3ca);

        $expected = <<<EOT
<ul>
    <li>Obj 3a
        <ul>
            <li>Obj 3aa
            </li>
            <li>Obj 3ab
            </li>
        </ul>
    </li>
    <li>Obj 3b
        <ul>
            <li>Obj 3ba
            </li>
            <li>Obj 3bb
            </li>
        </ul>
    </li>
    <li>Obj 3c
        <ul>
            <li>Obj 3c
            </li>
        </ul>
    </li>
    <li>Obj 3d
    </li>
</ul>

EOT;

        $this->assertHTMLSame($expected, $set->renderChildren());
    }

    public function testGetChildrenCustomTemplate()
    {
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');
        $obj2aa = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2aa');

        // Render marked tree
        $set = new MarkedSet(HierarchyTest\TestObject::singleton(), 'AllChildrenIncludingDeleted', 'numChildren', 30);
        $set->markPartialTree();
        $template = __DIR__ . '/HierarchyTest/MarkedSetTest_HTML.ss';
        $html = $set->renderChildren($template);

        $this->assertTreeContains(
            $html,
            array($obj2),
            'Contains root elements'
        );
        $this->assertTreeContains(
            $html,
            array($obj2, $obj2a),
            'Contains child elements (in correct nesting)'
        );
        $this->assertTreeContains(
            $html,
            array($obj2, $obj2a, $obj2aa),
            'Contains grandchild elements (in correct nesting)'
        );
    }

    public function testGetChildrenAsULMinNodeCount()
    {
        $obj1 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj1');
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');

        // Render marked tree
        $set = new MarkedSet(HierarchyTest\TestObject::singleton(), 'AllChildrenIncludingDeleted', 'numChildren');
        $set->setNodeCountThreshold(3); // Set low enough that it should be fulfilled by root only elements
        $set->markPartialTree();
        $template = __DIR__ . '/HierarchyTest/MarkedSetTest_HTML.ss';
        $html = $set->renderChildren($template);

        $this->assertTreeContains(
            $html,
            array($obj1),
            'Contains root elements'
        );
        $this->assertTreeContains(
            $html,
            array($obj2),
            'Contains root elements'
        );
        $this->assertTreeNotContains(
            $html,
            array($obj2, $obj2a),
            'Does not contains child elements because they exceed minNodeCount'
        );
    }

    public function testGetChildrenAsULMinNodeCountWithMarkToExpose()
    {
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');
        $obj2aa = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2aa');

        // Render marked tree
        $set = new MarkedSet(HierarchyTest\TestObject::singleton(), 'AllChildrenIncludingDeleted', 'numChildren');
        $set->setNodeCountThreshold(3); // Set low enough that it should be fulfilled by root only elements
        $set->markPartialTree();
        // Mark certain node which should be included regardless of minNodeCount restrictions
        $set->markToExpose($obj2aa);
        $template = __DIR__ . '/HierarchyTest/MarkedSetTest_HTML.ss';
        $html = $set->renderChildren($template);

        $this->assertTreeContains(
            $html,
            array($obj2),
            'Contains root elements'
        );
        $this->assertTreeContains(
            $html,
            array($obj2, $obj2a, $obj2aa),
            'Does contain marked children nodes regardless of configured threshold'
        );
    }

    public function testGetChildrenAsULMinNodeCountWithFilters()
    {
        $obj1 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj1');
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');
        $obj2aa = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2aa');

        // Render marked tree
        $set = new MarkedSet(HierarchyTest\TestObject::singleton(), 'AllChildrenIncludingDeleted', 'numChildren');
        $set->setNodeCountThreshold(3); // Set low enough that it should be fulfilled by root only elements
        // Includes nodes by filter regardless of minNodeCount restrictions
        $set->setMarkingFilterFunction(
            function ($record) use ($obj2, $obj2a, $obj2aa) {
                // Results need to include parent hierarchy, even if we just want to
                // match the innermost node.
                return in_array($record->ID, array($obj2->ID, $obj2a->ID, $obj2aa->ID));
            }
        );
        $set->markPartialTree();
        // Mark certain node which should be included regardless of minNodeCount restrictions
        $template = __DIR__ . '/HierarchyTest/MarkedSetTest_HTML.ss';
        $html = $set->renderChildren($template);

        $this->assertTreeNotContains(
            $html,
            array($obj1),
            'Does not contain root elements which dont match the filter'
        );
        $this->assertTreeContains(
            $html,
            array($obj2, $obj2a, $obj2aa),
            'Contains non-root elements which match the filter'
        );
    }

    public function testGetChildrenAsULHardLimitsNodes()
    {
        $obj1 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj1');
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');
        $obj2aa = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2aa');

        // Render marked tree
        $set = new MarkedSet(HierarchyTest\TestObject::singleton(), 'AllChildrenIncludingDeleted', 'numChildren');
        $set->setNodeCountThreshold(3); // Set low enough that it should miss out one node

        // Includes nodes by filter regardless of minNodeCount restrictions
        $set->setMarkingFilterFunction(
            function ($record) use ($obj2, $obj2a, $obj2aa) {
                // Results need to include parent hierarchy, even if we just want to
                // match the innermost node.
                return in_array($record->ID, array($obj2->ID, $obj2a->ID, $obj2aa->ID));
            }
        );
        $set->markPartialTree();
        $template = __DIR__ . '/HierarchyTest/MarkedSetTest_HTML.ss';
        $html = $set->renderChildren($template);

        $this->assertTreeNotContains(
            $html,
            array($obj1, $obj2aa),
            'Does not contain root elements which dont match the filter or are limited'
        );
        $this->assertTreeContains(
            $html,
            array($obj2, $obj2a),
            'Contains non-root elements which match the filter'
        );
    }

    public function testGetChildrenAsULNodeThresholdLeaf()
    {
        $obj1 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj1');
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');
        $obj3 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj3');
        $obj3a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj3a');

        // Render marked tree
        $set = new MarkedSet(HierarchyTest\TestObject::singleton(), 'AllChildrenIncludingDeleted', 'numChildren');
        $set->setNodeCountThreshold(99);
        $set->setMaxChildNodes(2); // Force certain children to exceed limits
        $set->markPartialTree();
        $template = __DIR__ . '/HierarchyTest/MarkedSetTest_HTML.ss';
        $html = $set->renderChildren($template);

        $this->assertTreeContains(
            $html,
            array($obj1),
            'Does contain root elements regardless of count'
        );
        $this->assertTreeContains(
            $html,
            array($obj3),
            'Does contain root elements regardless of count'
        );
        $this->assertTreeContains(
            $html,
            array($obj2, $obj2a),
            'Contains children which do not exceed threshold'
        );
        $this->assertTreeNotContains(
            $html,
            array($obj3, $obj3a),
            'Does not contain children which exceed threshold'
        );
    }

    /**
     * This test checks that deleted ('archived') child pages don't set a css class on the parent
     * node that makes it look like it has children
     */
    public function testGetChildrenAsULNodeDeletedOnLive()
    {
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');
        $obj2aa = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2aa');
        $obj2ab = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2b');

        // delete all children under obj2
        $obj2a->delete();
        $obj2aa->delete();
        $obj2ab->delete();

        $set = new MarkedSet(
            HierarchyTest\TestObject::singleton(),
            'AllChildren',
            'numChildren'
        );
        // Don't pre-load all children
        $set->setNodeCountThreshold(1);
        $set->markPartialTree();
        $template = __DIR__ . '/HierarchyTest/MarkedSetTest_HTML.ss';
        $html = $set->renderChildren($template);

        // Get the class attribute from the $obj2 node in the sitetree, class 'jstree-leaf' means it's a leaf node
        $nodeClass = $this->getNodeClassFromTree($html, $obj2);
        $this->assertEquals('jstree-leaf closed', $nodeClass, 'object2 should not have children in the sitetree');
    }

    /**
     * This test checks that deleted ('archived') child pages _do_ set a css class on the parent
     * node that makes it look like it has children when getting all children including deleted
     */
    public function testGetChildrenAsULNodeDeletedOnStage()
    {
        $obj2 = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2');
        $obj2a = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2a');
        $obj2aa = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2aa');
        $obj2ab = $this->objFromFixture(HierarchyTest\TestObject::class, 'obj2b');

        // delete all children under obj2
        $obj2a->delete();
        $obj2aa->delete();
        $obj2ab->delete();

        $set = new MarkedSet(
            HierarchyTest\TestObject::singleton(),
            'AllChildrenIncludingDeleted',
            'numHistoricalChildren'
        );
        // Don't pre-load all children
        $set->setNodeCountThreshold(1);
        $set->markPartialTree();
        $template = __DIR__ . '/HierarchyTest/MarkedSetTest_HTML.ss';
        $html = $set->renderChildren($template);

        // Get the class attribute from the $obj2 node in the sitetree
        $nodeClass = $this->getNodeClassFromTree($html, $obj2);
        // Object2 can now be expanded
        $this->assertEquals('unexpanded jstree-closed closed', $nodeClass, 'obj2 should have children in the sitetree');
    }


    /**
     * @param String $html    [description]
     * @param array  $nodes   Breadcrumb path as array
     * @param String $message
     */
    protected function assertTreeContains($html, $nodes, $message = null)
    {
        $parser = new CSSContentParser($html);
        $xpath = '/';
        foreach ($nodes as $node) {
            $xpath .= '/ul/li[@data-id="' . $node->ID . '"]';
        }
        $match = $parser->getByXpath($xpath);
        self::assertThat((bool)$match, self::isTrue(), $message);
    }

    /**
     * @param String $html    [description]
     * @param array  $nodes   Breadcrumb path as array
     * @param String $message
     */
    protected function assertTreeNotContains($html, $nodes, $message = null)
    {
        $parser = new CSSContentParser($html);
        $xpath = '/';
        foreach ($nodes as $node) {
            $xpath .= '/ul/li[@data-id="' . $node->ID . '"]';
        }
        $match = $parser->getByXpath($xpath);
        self::assertThat((bool)$match, self::isFalse(), $message);
    }

    /**
     * Get the HTML class attribute from a node in the sitetree
     *
     * @param string$html
     * @param DataObject $node
     * @return string
     */
    protected function getNodeClassFromTree($html, $node)
    {
        $parser = new CSSContentParser($html);
        $xpath = '//ul/li[@data-id="' . $node->ID . '"]';
        $object = $parser->getByXpath($xpath);

        foreach ($object[0]->attributes() as $key => $attr) {
            if ($key == 'class') {
                return (string)$attr;
            }
        }
        return '';
    }

    protected function assertHTMLSame($expected, $actual, $message = '')
    {
        // Trim each line, strip empty lines
        $expected = implode("\n", array_filter(array_map('trim', explode("\n", $expected))));
        $actual = implode("\n", array_filter(array_map('trim', explode("\n", $actual))));
        $this->assertXmlStringEqualsXmlString($expected, $actual, $message);
    }
}
