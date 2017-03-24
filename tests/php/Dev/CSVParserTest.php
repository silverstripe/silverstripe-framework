<?php

namespace SilverStripe\Dev\Tests;

use SilverStripe\Dev\CSVParser;
use SilverStripe\Dev\SapphireTest;

class CSVParserTest extends SapphireTest
{

    /**
     * Name of csv test dir
     *
     * @var string
     */
    protected $csvPath = null;

    protected function setUp()
    {
        parent::setUp();
        $this->csvPath = __DIR__ . '/CsvBulkLoaderTest/csv/';
    }

    public function testParsingWithHeaders()
    {
        /* By default, a CSV file will be interpreted as having headers */
        $csv = new CSVParser($this->csvPath . 'PlayersWithHeader.csv');

        $firstNames = $birthdays = $biographies = $registered = array();
        foreach ($csv as $record) {
            /* Each row in the CSV file will be keyed with the header row */
            $this->assertEquals(array('FirstName','Biography','Birthday','IsRegistered'), array_keys($record));
            $firstNames[] = $record['FirstName'];
            $biographies[] = $record['Biography'];
            $birthdays[] = $record['Birthday'];
            $registered[] = $record['IsRegistered'];
        }

        $this->assertEquals(array('John','Jane','Jamie','Järg'), $firstNames);

        $this->assertEquals(
            array(
            "He's a good guy",
            "She is awesome." . PHP_EOL
                . "So awesome that she gets multiple rows and \"escaped\" strings in her biography",
            "Pretty old, with an escaped comma",
            "Unicode FTW"),
            $biographies
        );
        $this->assertEquals([
            "1988-01-31",
            "1982-01-31",
            "1882-01-31",
            "1982-06-30"
        ], $birthdays);
        $this->assertEquals(array('1', '0', '1', '1'), $registered);
    }

    public function testParsingWithHeadersAndColumnMap()
    {
        /* By default, a CSV file will be interpreted as having headers */
        $csv = new CSVParser($this->csvPath . 'PlayersWithHeader.csv');

        /* We can set up column remapping.  The keys are case-insensitive. */
        $csv->mapColumns(
            array(
            'FirstName' => '__fn',
            'bIoGrApHy' => '__BG',
            )
        );

        $firstNames = $birthdays = $biographies = $registered = array();
        foreach ($csv as $record) {
            /* Each row in the CSV file will be keyed with the renamed columns.  Any unmapped column names will be
            * left as-is. */
            $this->assertEquals(array('__fn','__BG','Birthday','IsRegistered'), array_keys($record));
            $firstNames[] = $record['__fn'];
            $biographies[] = $record['__BG'];
            $birthdays[] = $record['Birthday'];
            $registered[] = $record['IsRegistered'];
        }

        $this->assertEquals(array('John','Jane','Jamie','Järg'), $firstNames);
        $this->assertEquals(
            array(
            "He's a good guy",
            "She is awesome."
                . PHP_EOL . "So awesome that she gets multiple rows and \"escaped\" strings in her biography",
            "Pretty old, with an escaped comma",
            "Unicode FTW"),
            $biographies
        );
        $this->assertEquals([
            "1988-01-31",
            "1982-01-31",
            "1882-01-31",
            "1982-06-30"
        ], $birthdays);
        $this->assertEquals(array('1', '0', '1', '1'), $registered);
    }

    public function testParsingWithExplicitHeaderRow()
    {
        /* If your CSV file doesn't have a header row */
        $csv = new CSVParser($this->csvPath .'PlayersWithHeader.csv');

        $csv->provideHeaderRow(array('__fn','__bio','__bd','__reg'));

        $firstNames = $birthdays = $biographies = $registered = array();
        foreach ($csv as $record) {
            /* Each row in the CSV file will be keyed with the header row that you gave */
            $this->assertEquals(array('__fn','__bio','__bd','__reg'), array_keys($record));
            $firstNames[] = $record['__fn'];
            $biographies[] = $record['__bio'];
            $birthdays[] = $record['__bd'];
            $registered[] = $record['__reg'];
        }

        /* And the first row will be returned in the data */
        $this->assertEquals(array('FirstName','John','Jane','Jamie','Järg'), $firstNames);
        $this->assertEquals(
            array(
            'Biography',
            "He's a good guy",
            "She is awesome." . PHP_EOL
                . "So awesome that she gets multiple rows and \"escaped\" strings in her biography",
            "Pretty old, with an escaped comma",
            "Unicode FTW"),
            $biographies
        );
        $this->assertEquals([
            "Birthday",
            "1988-01-31",
            "1982-01-31",
            "1882-01-31",
            "1982-06-30"
        ], $birthdays);
        $this->assertEquals(array('IsRegistered', '1', '0', '1', '1'), $registered);
    }
}
