<?php

/**
 * Tests link tracking to files and images.
 */
class FileLinkTrackingTest extends SapphireTest {
	static $fixture_file = "sapphire/tests/FileLinkTrackingTest.yml";
	
	function setUp() {
		parent::setUp();
		$this->logInWithPermssion('ADMIN');
		touch(Director::baseFolder() . '/assets/testscript-test-file.pdf');
	}
	function tearDown() {
		parent::tearDown();
		$testFiles = array(
			'/assets/testscript-test-file.pdf',
			'/assets/renamed-test-file.pdf',
		);
		foreach($testFiles as $file) {
			if(file_exists(Director::baseFolder().$file)) unlink(Director::baseFolder().$file);
		}
	}
	
	function testFileRenameUpdatesDraftAndPublishedPages() {
		$page = $this->objFromFixture('Page', 'page1');
		$this->assertTrue($page->doPublish());
		$this->assertContains('<img src="assets/testscript-test-file.pdf" />',
			DB::query("SELECT \"Content\" FROM \"SiteTree_Live\" WHERE \"ID\" = $page->ID")->value());
		
		$file = $this->objFromFixture('File', 'file1');
		$file->Name = 'renamed-test-file.pdf';
		
		$this->assertContains('<img src="assets/renamed-test-file.pdf" />',
			DB::query("SELECT \"Content\" FROM \"SiteTree\" WHERE \"ID\" = $page->ID")->value());
	}
	
}

?>
