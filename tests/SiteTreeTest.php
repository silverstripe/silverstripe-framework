<?php

/**
 * @package tests
 */

/**
 * Tests for SiteTree
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
	
	
	/**
	 * Test that saving changes creates a new version with the correct data in it.
	 */
	
	/**
	 * Test that publishing an unsaved page will save the changes before publishing
	 */
}