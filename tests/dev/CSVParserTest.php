<?php

/**
 * @package framework
 * @package tests
 */
class CSVParserTest extends SapphireTest {


	public function testParsingWithHeaders() {
		/* By default, a CSV file will be interpreted as having headers */
		$csv = new CSVParser($this->getCurrentRelativePath() . '/CsvBulkLoaderTest_PlayersWithHeader.csv');

		$firstNames = $birthdays = $biographies = $registered = array();
		foreach($csv as $record) {
			/* Each row in the CSV file will be keyed with the header row */
			$this->assertEquals(array('FirstName','Biography','Birthday','IsRegistered'), array_keys($record));
			$firstNames[] = $record['FirstName'];
			$biographies[] = $record['Biography'];
			$birthdays[] = $record['Birthday'];
			$registered[] = $record['IsRegistered'];
		}

		$this->assertEquals(array('John','Jane','Jamie','Järg','Jacob'), $firstNames);

		$this->assertEquals(array(
			"He's a good guy",
			"She is awesome." . PHP_EOL
				. "So awesome that she gets multiple rows and \"escaped\" strings in her biography",
			"Pretty old, with an escaped comma",
			"Unicode FTW",
			"Likes leading tabs in his biography",
		), $biographies);
		$this->assertEquals(array("31/01/1988","31/01/1982","31/01/1882","31/06/1982","31/4/2000"), $birthdays);
		$this->assertEquals(array('1', '0', '1', '1', '0'), $registered);
	}

	public function testParsingWithHeadersAndColumnMap() {
		/* By default, a CSV file will be interpreted as having headers */
		$csv = new CSVParser($this->getCurrentRelativePath() . '/CsvBulkLoaderTest_PlayersWithHeader.csv');

		/* We can set up column remapping.  The keys are case-insensitive. */
		$csv->mapColumns(array(
			'FirstName' => '__fn',
			'bIoGrApHy' => '__BG',
		));

		$firstNames = $birthdays = $biographies = $registered = array();
		foreach($csv as $record) {
			/* Each row in the CSV file will be keyed with the renamed columns.  Any unmapped column names will be
			 * left as-is. */
			$this->assertEquals(array('__fn','__BG','Birthday','IsRegistered'), array_keys($record));
			$firstNames[] = $record['__fn'];
			$biographies[] = $record['__BG'];
			$birthdays[] = $record['Birthday'];
			$registered[] = $record['IsRegistered'];
		}

		$this->assertEquals(array('John','Jane','Jamie','Järg','Jacob'), $firstNames);
		$this->assertEquals(array(
			"He's a good guy",
			"She is awesome."
				. PHP_EOL . "So awesome that she gets multiple rows and \"escaped\" strings in her biography",
			"Pretty old, with an escaped comma",
			"Unicode FTW",
			"Likes leading tabs in his biography"), $biographies);
		$this->assertEquals(array("31/01/1988","31/01/1982","31/01/1882","31/06/1982","31/4/2000"), $birthdays);
		$this->assertEquals(array('1', '0', '1', '1', '0'), $registered);
	}

	public function testParsingWithExplicitHeaderRow() {
		/* If your CSV file doesn't have a header row */
		$csv = new CSVParser($this->getCurrentRelativePath() .'/CsvBulkLoaderTest_PlayersWithHeader.csv');

		$csv->provideHeaderRow(array('__fn','__bio','__bd','__reg'));

		$firstNames = $birthdays = $biographies = $registered = array();
		foreach($csv as $record) {
			/* Each row in the CSV file will be keyed with the header row that you gave */
			$this->assertEquals(array('__fn','__bio','__bd','__reg'), array_keys($record));
			$firstNames[] = $record['__fn'];
			$biographies[] = $record['__bio'];
			$birthdays[] = $record['__bd'];
			$registered[] = $record['__reg'];
		}

		/* And the first row will be returned in the data */
		$this->assertEquals(array('FirstName','John','Jane','Jamie','Järg','Jacob'), $firstNames);
		$this->assertEquals(array(
			'Biography',
			"He's a good guy",
			"She is awesome." . PHP_EOL
				. "So awesome that she gets multiple rows and \"escaped\" strings in her biography",
			"Pretty old, with an escaped comma",
			"Unicode FTW",
			"Likes leading tabs in his biography"), $biographies);
		$this->assertEquals(array("Birthday","31/01/1988","31/01/1982","31/01/1882","31/06/1982","31/4/2000"), $birthdays);
		$this->assertEquals(array('IsRegistered', '1', '0', '1', '1', '0'), $registered);
	}
}
