<?php

/**
 * @package framework
 * @package tests
 */
class CSVParserTest extends SapphireTest {


	public function testParsingWithHeaders() {
		/* By default, a CSV file will be interpreted as having headers */
		$csv = new CSVParser($this->getCurrentRelativePath() . '/CsvBulkLoaderTest_PlayersWithHeader.csv');

		$firstNames = $birthdays = $biographies = $registered = [];
		foreach($csv as $record) {
			/* Each row in the CSV file will be keyed with the header row */
			$this->assertEquals(['FirstName','Biography','Birthday','IsRegistered'], array_keys($record));
			$firstNames[] = $record['FirstName'];
			$biographies[] = $record['Biography'];
			$birthdays[] = $record['Birthday'];
			$registered[] = $record['IsRegistered'];
		}

		$this->assertEquals(['John','Jane','Jamie','Järg'], $firstNames);

		$this->assertEquals([
			"He's a good guy",
			"She is awesome." . PHP_EOL
				. "So awesome that she gets multiple rows and \"escaped\" strings in her biography",
			"Pretty old, with an escaped comma",
			"Unicode FTW"], $biographies);
		$this->assertEquals(["31/01/1988","31/01/1982","31/01/1882","31/06/1982"], $birthdays);
		$this->assertEquals(['1', '0', '1', '1'], $registered);
	}

	public function testParsingWithHeadersAndColumnMap() {
		/* By default, a CSV file will be interpreted as having headers */
		$csv = new CSVParser($this->getCurrentRelativePath() . '/CsvBulkLoaderTest_PlayersWithHeader.csv');

		/* We can set up column remapping.  The keys are case-insensitive. */
		$csv->mapColumns([
			'FirstName' => '__fn',
			'bIoGrApHy' => '__BG',
		]);

		$firstNames = $birthdays = $biographies = $registered = [];
		foreach($csv as $record) {
			/* Each row in the CSV file will be keyed with the renamed columns.  Any unmapped column names will be
			 * left as-is. */
			$this->assertEquals(['__fn','__BG','Birthday','IsRegistered'], array_keys($record));
			$firstNames[] = $record['__fn'];
			$biographies[] = $record['__BG'];
			$birthdays[] = $record['Birthday'];
			$registered[] = $record['IsRegistered'];
		}

		$this->assertEquals(['John','Jane','Jamie','Järg'], $firstNames);
		$this->assertEquals([
			"He's a good guy",
			"She is awesome."
				. PHP_EOL . "So awesome that she gets multiple rows and \"escaped\" strings in her biography",
			"Pretty old, with an escaped comma",
			"Unicode FTW"], $biographies);
		$this->assertEquals(["31/01/1988","31/01/1982","31/01/1882","31/06/1982"], $birthdays);
		$this->assertEquals(['1', '0', '1', '1'], $registered);
	}

	public function testParsingWithExplicitHeaderRow() {
		/* If your CSV file doesn't have a header row */
		$csv = new CSVParser($this->getCurrentRelativePath() .'/CsvBulkLoaderTest_PlayersWithHeader.csv');

		$csv->provideHeaderRow(['__fn','__bio','__bd','__reg']);

		$firstNames = $birthdays = $biographies = $registered = [];
		foreach($csv as $record) {
			/* Each row in the CSV file will be keyed with the header row that you gave */
			$this->assertEquals(['__fn','__bio','__bd','__reg'], array_keys($record));
			$firstNames[] = $record['__fn'];
			$biographies[] = $record['__bio'];
			$birthdays[] = $record['__bd'];
			$registered[] = $record['__reg'];
		}

		/* And the first row will be returned in the data */
		$this->assertEquals(['FirstName','John','Jane','Jamie','Järg'], $firstNames);
		$this->assertEquals([
			'Biography',
			"He's a good guy",
			"She is awesome." . PHP_EOL
				. "So awesome that she gets multiple rows and \"escaped\" strings in her biography",
			"Pretty old, with an escaped comma",
			"Unicode FTW"], $biographies);
		$this->assertEquals(["Birthday","31/01/1988","31/01/1982","31/01/1882","31/06/1982"], $birthdays);
		$this->assertEquals(['IsRegistered', '1', '0', '1', '1'], $registered);
	}
}
