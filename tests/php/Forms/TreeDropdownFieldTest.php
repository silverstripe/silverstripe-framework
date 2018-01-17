<?php

namespace SilverStripe\Forms\Tests;

use SilverStripe\Assets\File;
use SilverStripe\Assets\Folder;
use SilverStripe\Control\Session;
use SilverStripe\Dev\CSSContentParser;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Forms\TreeDropdownField;

class TreeDropdownFieldTest extends SapphireTest
{

    protected static $fixture_file = 'TreeDropdownFieldTest.yml';

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

        // case insensitive search against keyword 'sub' for folders
        $request = new HTTPRequest('GET', 'url', array('search'=>'sub', 'format' => 'json'));
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = json_decode($response->getBody(), true);

        $folder1 = $this->objFromFixture(Folder::class, 'folder1');
        $folder1Subfolder1 = $this->objFromFixture(Folder::class, 'folder1-subfolder1');

        $this->assertContains(
            $folder1->Name,
            array_column($tree['children'], 'title'),
            $folder1->Name . ' is found in the json'
        );

        $filtered = array_filter($tree['children'], function ($entry) use ($folder1) {
            return $folder1->Name === $entry['title'];
        });
        $folder1Tree = array_pop($filtered);

        $this->assertContains(
            $folder1Subfolder1->Name,
            array_column($folder1Tree['children'], 'title'),
            $folder1Subfolder1->Name . ' is found in the folder1 entry in the json'
        );
    }

    public function testTreeSearchJsonFlatlist()
    {
        $field = new TreeDropdownField('TestTree', 'Test tree', Folder::class);

        // case insensitive search against keyword 'sub' for folders
        $request = new HTTPRequest('GET', 'url', array('search'=>'sub', 'format' => 'json', 'flatList' => '1'));
        $request->setSession(new Session([]));
        $response = $field->tree($request);
        $tree = json_decode($response->getBody(), true);

        $folder1 = $this->objFromFixture(Folder::class, 'folder1');
        $folder1Subfolder1 = $this->objFromFixture(Folder::class, 'folder1-subfolder1');

        $this->assertNotContains(
            $folder1->Name,
            array_column($tree['children'], 'title'),
            $folder1->Name . ' is not found in the json'
        );

        $this->assertContains(
            $folder1Subfolder1->Name,
            array_column($tree['children'], 'title'),
            $folder1Subfolder1->Name . ' is found in the json'
        );
    }

    public function testTreeSearch()
    {
        $field = new TreeDropdownField('TestTree', 'Test tree', Folder::class);

        // case insensitive search against keyword 'sub' for folders
        $request = new HTTPRequest('GET', 'url', array('search'=>'sub'));
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

        // case insensitive search against keyword 'sub' for files
        $request = new HTTPRequest('GET', 'url', array('search'=>'sub'));
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

    public function testReadonly()
    {
        $field = new TreeDropdownField('TestTree', 'Test tree', File::class);
        $asdf = $this->objFromFixture(File::class, 'asdf');
        $field->setValue($asdf->ID);
        $readonlyField = $field->performReadonlyTransformation();
        $this->assertEquals(
            <<<"HTML"
<span class="readonly" id="TestTree">&lt;Special &amp; characters&gt;</span><input type="hidden" name="TestTree" value="{$asdf->ID}" />
HTML
            ,
            (string)$readonlyField->Field()
        );
    }
}
