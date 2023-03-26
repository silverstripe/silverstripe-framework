<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\Session;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\TreeDropdownField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\Tests\HierarchyTest\HierarchyOnSubclassTestObject;
use SilverStripe\ORM\Tests\HierarchyTest\HierarchyOnSubclassTestSubObject;
use SilverStripe\ORM\Tests\HierarchyTest\TestObject;

class TreeDropdownFieldTest extends SapphireTest
{

    protected static $fixture_file = 'TreeDropdownFieldTest.yml';

    protected static $extra_dataobjects = [
        TestObject::class,
        HierarchyOnSubclassTestObject::class,
        HierarchyOnSubclassTestSubObject::class,
    ];

    public function testSchemaStateDefaults()
    {
        $field = new TreeDropdownField('TestTree', 'Test tree', Folder::class);
        $folder = $this->objFromFixture(Folder::class, 'folder1-subfolder1');

        $schema = $field->getSchemaStateDefaults();
        $this->assertFalse(isset($schema['data']['valueObject']));

        $field->setValue($folder->ID);

        $schema = $field->getSchemaStateDefaults();
        $this->assertEquals($folder->ID, $schema['data']['valueObject']['id']);
        $this->assertTrue(isset($schema['data']['valueObject']));
        $this->assertFalse($schema['data']['showSelectedPath']);
        $this->assertEquals('', $schema['data']['valueObject']['titlePath']);

        $field->setShowSelectedPath(true);
        $schema = $field->getSchemaStateDefaults();
        $this->assertTrue($schema['data']['showSelectedPath']);
        $this->assertEquals(
            'FileTest-folder1/FileTest-folder1-subfolder1/',
            $schema['data']['valueObject']['titlePath']
        );
    }

    public function testTreeSearchJson()
    {
        $field = new TreeDropdownField('TestTree', 'Test tree', Folder::class);

        // case-insensitive search against keyword 'sub' for folders
        $request = new HTTPRequest('GET', 'url', ['search'=>'sub', 'format' => 'json']);
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = json_decode($response->getBody() ?? '', true);

        $folder1 = $this->objFromFixture(Folder::class, 'folder1');
        $folder1Subfolder1 = $this->objFromFixture(Folder::class, 'folder1-subfolder1');

        $this->assertContains(
            $folder1->Name,
            array_column($tree['children'] ?? [], 'title'),
            $folder1->Name . ' is found in the json'
        );

        $filtered = array_filter($tree['children'] ?? [], function ($entry) use ($folder1) {
            return $folder1->Name === $entry['title'];
        });
        $folder1Tree = array_pop($filtered);

        $this->assertContains(
            $folder1Subfolder1->Name,
            array_column($folder1Tree['children'] ?? [], 'title'),
            $folder1Subfolder1->Name . ' is found in the folder1 entry in the json'
        );
    }

    public function testTreeSearchJsonFlatlist()
    {
        $field = new TreeDropdownField('TestTree', 'Test tree', Folder::class);

        // case-insensitive search against keyword 'sub' for folders
        $request = new HTTPRequest('GET', 'url', ['search'=>'sub', 'format' => 'json', 'flatList' => '1']);
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = json_decode($response->getBody() ?? '', true);

        $folder1 = $this->objFromFixture(Folder::class, 'folder1');
        $folder1Subfolder1 = $this->objFromFixture(Folder::class, 'folder1-subfolder1');

        $this->assertNotContains(
            $folder1->Name,
            array_column($tree['children'] ?? [], 'title'),
            $folder1->Name . ' is not found in the json'
        );

        $this->assertContains(
            $folder1Subfolder1->Name,
            array_column($tree['children'] ?? [], 'title'),
            $folder1Subfolder1->Name . ' is found in the json'
        );
    }

    public function testTreeSearchJsonFlatlistWithLowNodeThreshold()
    {
        // Initialise our TreeDropDownField
        $field = new TreeDropdownField('TestTree', 'Test tree', TestObject::class);
        $field->config()->set('node_threshold_total', 2);

        // Search for all Test object matching our criteria
        $request = new HTTPRequest(
            'GET',
            'url',
            ['search' => 'MatchSearchCriteria', 'format' => 'json', 'flatList' => '1']
        );
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = json_decode($response->getBody() ?? '', true);
        $actualNodeIDs = array_column($tree['children'] ?? [], 'id');


        // Get the list of expected node IDs from the YML Fixture
        $expectedNodeIDs = array_map(
            function ($key) {
                return $this->objFromFixture(TestObject::class, $key)->ID;
            },
            ['zero', 'oneA', 'twoAi', 'three'] // Those are the identifiers of the object we expect our search to find
        );

        sort($actualNodeIDs);
        sort($expectedNodeIDs);

        $this->assertEquals($expectedNodeIDs, $actualNodeIDs);
    }

    public function testTreeSearchJsonFlatlistWithLowNodeThresholdUsingSubObject()
    {
        // Initialise our TreeDropDownField
        $field = new TreeDropdownField('TestTree', 'Test tree - Hierarchy on subclass', HierarchyOnSubclassTestSubObject::class);
        $field->config()->set('node_threshold_total', 2);

        // Search for all Test object matching our criteria
        $request = new HTTPRequest(
            'GET',
            'url',
            ['search' => 'SubObject', 'format' => 'json', 'flatList' => '1']
        );
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = json_decode($response->getBody() ?? '', true);
        $actualNodeIDs = array_column($tree['children'] ?? [], 'id');

        // Get the list of expected node IDs from the YML Fixture
        $expectedNodeIDs = array_map(
            function ($key) {
                return $this->objFromFixture(HierarchyOnSubclassTestSubObject::class, $key)->ID;
            },
            ['four', 'fourB', 'fourA2'] // Those are the identifiers of the object we expect our search to find
        );

        sort($actualNodeIDs);
        sort($expectedNodeIDs);

        $this->assertEquals($expectedNodeIDs, $actualNodeIDs);
    }

    public function testTreeSearch()
    {
        $field = new TreeDropdownField('TestTree', 'Test tree', Folder::class);

        // case-insensitive search against keyword 'sub' for folders
        $request = new HTTPRequest('GET', 'url', ['search'=>'sub']);
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = $response->getBody();

        $folder1 = $this->objFromFixture(Folder::class, 'folder1');
        $folder1Subfolder1 = $this->objFromFixture(Folder::class, 'folder1-subfolder1');

        $parser = new CSSContentParser($tree);
        $cssPath = 'ul.tree li#selector-TestTree-' . $folder1->ID . ' li#selector-TestTree-' . $folder1Subfolder1->ID . ' a span.item';
        $firstResult = $parser->getBySelector($cssPath);
        $this->assertEquals(
            $folder1Subfolder1->Name,
            (string)$firstResult[0],
            $folder1Subfolder1->Name . ' is found, nested under ' . $folder1->Name
        );

        $subfolder = $this->objFromFixture(Folder::class, 'subfolder');
        $cssPath = 'ul.tree li#selector-TestTree-' . $subfolder->ID . ' a span.item';
        $secondResult = $parser->getBySelector($cssPath);
        $this->assertEquals(
            $subfolder->Name,
            (string)$secondResult[0],
            $subfolder->Name . ' is found at root level'
        );

        // other folders which don't contain the keyword 'sub' are not returned in search results
        $folder2 = $this->objFromFixture(Folder::class, 'folder2');
        $cssPath = 'ul.tree li#selector-TestTree-' . $folder2->ID . ' a span.item';
        $noResult = $parser->getBySelector($cssPath);
        $this->assertEmpty(
            $noResult,
            $folder2 . ' is not found'
        );

        $field = new TreeDropdownField('TestTree', 'Test tree', File::class);

        // case-insensitive search against keyword 'sub' for files
        $request = new HTTPRequest('GET', 'url', ['search'=>'sub']);
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = $response->getBody();

        $parser = new CSSContentParser($tree);

        // Even if we used File as the source object, folders are still returned because Folder is a File
        $cssPath = 'ul.tree li#selector-TestTree-' . $folder1->ID . ' li#selector-TestTree-' . $folder1Subfolder1->ID . ' a span.item';
        $firstResult = $parser->getBySelector($cssPath);
        $this->assertEquals(
            $folder1Subfolder1->Name,
            (string)$firstResult[0],
            $folder1Subfolder1->Name . ' is found, nested under ' . $folder1->Name
        );

        // Looking for two files with 'sub' in their name, both under the same folder
        $file1 = $this->objFromFixture(File::class, 'subfolderfile1');
        $file2 = $this->objFromFixture(File::class, 'subfolderfile2');
        $cssPath = 'ul.tree li#selector-TestTree-' . $subfolder->ID . ' li#selector-TestTree-' . $file1->ID . ' a';
        $firstResult = $parser->getBySelector($cssPath);
        $this->assertNotEmpty(
            $firstResult,
            $file1->Name . ' with ID ' . $file1->ID . ' is in search results'
        );
        $this->assertEquals(
            $file1->Name,
            (string)$firstResult[0],
            $file1->Name . ' is found nested under ' . $subfolder->Name
        );

        $cssPath = 'ul.tree li#selector-TestTree-' . $subfolder->ID . ' li#selector-TestTree-' . $file2->ID . ' a';
        $secondResult = $parser->getBySelector($cssPath);
        $this->assertNotEmpty(
            $secondResult,
            $file2->Name . ' with ID ' . $file2->ID . ' is in search results'
        );
        $this->assertEquals(
            $file2->Name,
            (string)$secondResult[0],
            $file2->Name . ' is found nested under ' . $subfolder->Name
        );

        // other files which don't include 'sub' are not returned in search results
        $file3 = $this->objFromFixture(File::class, 'asdf');
        $cssPath = 'ul.tree li#selector-TestTree-' . $file3->ID;
        $noResult = $parser->getBySelector($cssPath);
        $this->assertEmpty(
            $noResult,
            $file3->Name . ' is not found'
        );
    }

    public function testTreeSearchUsingSubObject()
    {
        $field = new TreeDropdownField('TestTree', 'Test tree', HierarchyOnSubclassTestSubObject::class);

        // case-insensitive search against keyword 'SubObject' for objects that have Hierarchy extension
        // applied to a class that doesn't directly inherit from DataObject
        $request = new HTTPRequest('GET', 'url', ['search' => 'SubObject']);
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = $response->getBody();

        $subObject1 = $this->objFromFixture(HierarchyOnSubclassTestSubObject::class, 'four');
        $subObject1ChildB = $this->objFromFixture(HierarchyOnSubclassTestSubObject::class, 'fourB');

        $parser = new CSSContentParser($tree);
        $cssPath = 'ul.tree li#selector-TestTree-' . $subObject1->ID . ' li#selector-TestTree-' . $subObject1ChildB->ID . ' a';
        $firstResult = $parser->getBySelector($cssPath);
        $this->assertEquals(
            $subObject1ChildB->Name,
            (string)$firstResult[0],
            $subObject1ChildB->Name . ' is found, nested under ' . $subObject1->Name
        );

        // other objects which don't contain the keyword 'SubObject' are not returned in search results
        $subObject2 = $this->objFromFixture(HierarchyOnSubclassTestSubObject::class, 'five');
        $cssPath = 'ul.tree li#selector-TestTree-' . $subObject2->ID . ' a';
        $noResult = $parser->getBySelector($cssPath);
        $this->assertEmpty(
            $noResult,
            $subObject2 . ' is not found'
        );
    }

    public function testReadonly()
    {
        $field = new TreeDropdownField('TestTree', 'Test tree', File::class);
        $fileMock = $this->objFromFixture(File::class, 'asdf');
        $field->setValue($fileMock->ID);
        $readonlyField = $field->performReadonlyTransformation();
        $result = (string) $readonlyField->Field();
        $this->assertStringContainsString(
            '<span class="readonly" id="TestTree">&lt;Special &amp; characters&gt;</span>',
            $result
        );
        $this->assertStringContainsString(
            '<input type="hidden" name="TestTree" value="' . $fileMock->ID . '" />',
            $result
        );
    }

    /**
     * This is to test setting $key to an Object in the protected function objectForKey()
     * This is to fix an issue where postgres will not fail gracefully when you do this
     */
    public function testObjectForKeyObjectValue()
    {
        $form = Form::create();
        $fieldList = FieldList::create();
        $field = TreeDropdownField::create('TestTree', 'Test tree', File::class);
        $fieldList->add($field);
        $form->setFields($fieldList);
        $field->setValue(DataObject::create());
        // The following previously errored in postgres
        $field->getSchemaStateDefaults();
        $this->assertTrue(true);
    }

    public function testTreeBaseID()
    {
        $treeBaseID = $this->idFromFixture(Folder::class, 'folder1');
        $field = new TreeDropdownField('TestTree', 'Test tree', Folder::class);

        // getSchemaDataDefaults needs the field to be attached to a form
        new Form(
            null,
            'mock',
            new FieldList($field)
        );

        $this->assertEmpty($field->getTreeBaseID(), 'TreeBaseId does not have an initial value');

        $field->setTreeBaseID($treeBaseID);
        $this->assertEquals(
            $treeBaseID,
            $field->getTreeBaseID(),
            'Value passed to setTreeBaseID is returned by getTreeBaseID'
        );

        $schema = $field->getSchemaDataDefaults();
        $this->assertEquals(
            $treeBaseID,
            $schema['data']['treeBaseId'],
            'TreeBaseId is included in the default schema data'
        );
    }

    public function testSearchFilter()
    {
        $field = new TreeDropdownField('TestTree', 'Test tree', TestObject::class);

        // case-insensitive search against keyword 'zero' for pages
        $request = new HTTPRequest('GET', 'url', ['search' => 'zero', 'format' => 'json', 'flatList' => '1' ]);
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = $response->getBody();
        $json = json_decode($tree);
        $children1Before = count($json->children);
        $this->assertEquals($children1Before, 4, 'PartialMatch search for zero has 4 results in fixture');

        TreeDropdownField::config()->set('search_filter', 'StartsWith');
        $request = new HTTPRequest('GET', 'url', ['search' => 'zero', 'format' => 'json', 'flatList' => '1' ]);
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = $response->getBody();
        $json = json_decode($tree);
        $children1After = count($json->children);
        $this->assertEquals($children1After, 1, 'StartsWith search for zero has 1 result in fixture');

        //change search_filter back and repeat the test
        TreeDropdownField::config()->set('search_filter', 'PartialMatch');
        // case-insensitive search against keyword 'child' for pages
        $request = new HTTPRequest('GET', 'url', ['search' => 'child', 'format' => 'json', 'flatList' => '1' ]);
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = $response->getBody();
        $json = json_decode($tree);
        $children2Before = count($json->children);
        $this->assertEquals($children2Before, 9, 'PartialMatch search for child has 9 results in fixture');

        TreeDropdownField::config()->set('search_filter', 'StartsWith');
        $request = new HTTPRequest('GET', 'url', ['search' => 'child', 'format' => 'json', 'flatList' => '1' ]);
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = $response->getBody();
        $json = json_decode($tree);
        $children2After = count($json->children);
        $this->assertEquals($children2After, 8, 'PartialMatch search for child has 8 results in fixture (excludes grandchild)');
    }
}
