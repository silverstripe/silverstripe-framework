<?php
/**
 * @package sapphire
 * @subpackage tests
 */
class SiteTreeTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/SiteTreeTest.yml';
	
	
	/**
	 * Test generation of the URLSegment values.
	 *  - Turns things into lowercase-hyphen-format
	 *  - Generates from Title by default, unless URLSegment is explicitly set
	 *  - Resolves duplicates by appending a number
	 */
	function testURLGeneration() {
		$expectedURLs = array(
			'home' => 'home',
			'staff' => 'my-staff',
			'about' => 'about-us',
			'staffduplicate' => 'my-staff-2',
			'product1' => '1-1-test-product',
			'product2' => 'another-product',
			'product3' => 'another-product-2',
			'product4' => 'another-product-3',
		);
		
		foreach($expectedURLs as $fixture => $urlSegment) {
			$obj = $this->objFromFixture('Page', $fixture);
			$this->assertEquals($urlSegment, $obj->URLSegment);
		}
	}
	
	/**
	 * Test that publication copies data to SiteTree_Live
	 */
	function testPublishCopiesToLiveTable() {
		$obj = $this->objFromFixture('Page','about');
		$obj->publish('Stage', 'Live');
		
		$createdID = DB::query("SELECT ID FROM SiteTree_Live WHERE URLSegment = '$obj->URLSegment'")->value();
		$this->assertEquals($obj->ID, $createdID);
	}
	
	function testParentNodeCachedInMemory() {
		$parent = new SiteTree();
     	$parent->Title = 'Section Title';
     	$child = new SiteTree();
     	$child->Title = 'Page Title';
		$child->setParent($parent);
		
		$this->assertType("SiteTree", $child->Parent);
		$this->assertEquals("Section Title", $child->Parent->Title);
	}
	
	function testParentModelReturnType() {
		$parent = new PageNode();
		$child = new PageNode();

		$child->setParent($parent);
		$this->assertType('PageNode', $child->Parent);
	}
	
}

class PageNode extends SiteTree { }

?>