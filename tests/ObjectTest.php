<?php

class ObjectTest extends SapphireTest {
	function testHasMethod() {
		$st = new SiteTree();
		$cc = new ContentController($st);

		// Check that Versiond methods exist on SiteTree		
		$this->assertTrue($st->hasMethod('publish'), "Test SiteTree has publish");
		$this->assertTrue($st->hasMethod('migrateVersion'), "Test SiteTree has migrateVersion");
		
		// Check for different casing
		$this->assertTrue($st->hasMethod('PuBliSh'), "Test SiteTree has PuBliSh");
		$this->assertTrue($st->hasMethod('MiGratEVersIOn'), "Test SiteTree has MiGratEVersIOn");
		
		// Check that SiteTree methods exist on ContentController (test failover)
		$this->assertTrue($cc->hasMethod);
		$this->assertTrue($cc->hasMethod('canView'), "Test ContentController has canView");
		$this->assertTrue($cc->hasMethod('linkorcurrent'), "Test ContentController has linkorcurrent");
		$this->assertTrue($cc->hasMethod('MiGratEVersIOn'), "Test ContentController has MiGratEVersIOn");
	}
	
}