<?php

namespace SilverStripe\Dev\Tests;


use SilverStripe\Dev\SapphireTest;



class SapphireTestTest extends SapphireTest
{
	public function testResolveFixturePath() {
		// Same directory
		$this->assertEquals(
			__DIR__ . '/CsvBulkLoaderTest.yml',
			$this->resolveFixturePath('./CsvBulkLoaderTest.yml')
		);
		// Filename only
		$this->assertEquals(
			__DIR__ . '/CsvBulkLoaderTest.yml',
			$this->resolveFixturePath('CsvBulkLoaderTest.yml')
		);
		// Parent path
		$this->assertEquals(
			dirname(__DIR__) . '/ORM/DataObjectTest.yml',
			$this->resolveFixturePath('../ORM/DataObjectTest.yml')
		);
		// Absolute path
		$this->assertEquals(
			dirname(__DIR__) . '/ORM/DataObjectTest.yml',
			$this->resolveFixturePath(dirname(__DIR__) .'/ORM/DataObjectTest.yml')
		);
	}
}
