<?php

class i18nTest extends SapphireTest {
	function testGetExistingTranslations() {
		$this->assertTrue(false);
		$translations = i18n::get_existing_translations();
		$this->assertTrue(isset($translations['en_US']), 'Checking for en_US translation');
		$this->assertTrue(isset($translations['de_DE']), 'Checking for de_DE translation');
	}
	
}
