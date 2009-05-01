<?php

class VirtualPageTest extends SapphireTest {
	static $fixture_file = 'sapphire/tests/model/VirtualPageTest.yml';
	
	/**
	 * Test that, after you update the source page of a virtual page, all the virtual pages
	 * are updated
	 */
	function testEditingSourcePageUpdatesVirtualPages() {
		$master = $this->objFromFixture('Page', 'master');
		$master->Title = "New title";
		$master->Content = "<p>New content</p>";
		$master->write();
		
		$vp1 = $this->objFromFixture('VirtualPage', 'vp1');
		$vp2 = $this->objFromFixture('VirtualPage', 'vp2');
		
		$this->assertEquals("New title", $vp1->Title);
		$this->assertEquals("New title", $vp2->Title);
		$this->assertEquals("<p>New content</p>", $vp1->Content);
		$this->assertEquals("<p>New content</p>", $vp2->Content);
	}

	/**
	 * Test that, after you publish the source page of a virtual page, all the virtual pages
	 * are published
	 */
	function testPublishingSourcePagePublishesVirtualPages() {
		$master = $this->objFromFixture('Page', 'master');
		$master->Title = "New title";
		$master->Content = "<p>New content</p>";
		$master->write();
		$master->doPublish();

		Versioned::reading_stage("Live");
		$vp1 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp1'));
		$vp2 = DataObject::get_by_id("VirtualPage", $this->idFromFixture('VirtualPage', 'vp2'));
		
		$this->assertNotNull($vp1);
		$this->assertNotNull($vp2);
		
		$this->assertEquals("New title", $vp1->Title);
		$this->assertEquals("New title", $vp2->Title);
		$this->assertEquals("<p>New content</p>", $vp1->Content);
		$this->assertEquals("<p>New content</p>", $vp2->Content);
		Versioned::reading_stage("Stage");
	}
	
}