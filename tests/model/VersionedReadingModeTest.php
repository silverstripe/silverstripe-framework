<?php

class VersionedReadingModeTest extends SapphireTest
{
    /**
     * @dataProvider provideReadingModes()
     *
     * @param string $readingMode
     * @param array $dataQuery
     * @param array $queryStringArray
     * @param string $queryString
     */
    public function testToDataQueryParams($readingMode, $dataQuery, $queryStringArray, $queryString)
    {
        $this->assertEquals(
            $dataQuery,
            VersionedReadingMode::toDataQueryParams($readingMode),
            "Convert {$readingMode} to dataquery parameters"
        );
    }
    /**
     * @dataProvider provideReadingModes()
     *
     * @param string $readingMode
     * @param array $dataQuery
     * @param array $queryStringArray
     * @param string $queryString
     */
    public function testFromDataQueryParameters($readingMode, $dataQuery, $queryStringArray, $queryString)
    {
        $this->assertEquals(
            $readingMode,
            VersionedReadingMode::fromDataQueryParams($dataQuery),
            "Convert {$readingMode} from dataquery parameters"
        );
    }

    /**
     * @dataProvider provideReadingModes()
     *
     * @param string $readingMode
     * @param array $dataQuery
     * @param array $queryStringArray
     * @param string $queryString
     */
    public function testToQueryString($readingMode, $dataQuery, $queryStringArray, $queryString)
    {
        $this->assertEquals(
            $queryStringArray,
            VersionedReadingMode::toQueryString($readingMode),
            "Convert {$readingMode} to querystring array"
        );
    }

    /**
     * @dataProvider provideReadingModes()
     *
     * @param string $readingMode
     * @param array $dataQuery
     * @param array $queryStringArray
     * @param string $queryString
     */
    public function testFromQueryString($readingMode, $dataQuery, $queryStringArray, $queryString)
    {
        $this->assertEquals(
            $readingMode,
            VersionedReadingMode::fromQueryString($queryStringArray),
            "Convert {$readingMode} from querystring array"
        );
        $this->assertEquals(
            $readingMode,
            VersionedReadingMode::fromQueryString($queryString),
            "Convert {$readingMode} from querystring encoded string"
        );
    }

    /**
     * Return list of reading modes in order:
     *  - reading mode string
     *  - dataquery params array
     *  - query string array
     *  - query string (string)
     * @return array
     */
    public function provideReadingModes()
    {
        return array(
            // Draft
            array(
                'Stage.Stage',
                array(
                    'Versioned.mode' => 'stage',
                    'Versioned.stage' => 'Stage',
				),
                array(
                    'stage' => 'Stage',
				),
                'stage=Stage'
			),
            // Live
            array(
                'Stage.Live',
                array(
                    'Versioned.mode' => 'stage',
                    'Versioned.stage' => 'Live',
				),
                array(
                    'stage' => 'Live',
				),
                'stage=Live'
			),
            // Draft archive
            array(
                'Archive.2017-11-15 11:31:42',
                array(
                    'Versioned.mode' => 'archive',
                    'Versioned.date' => '2017-11-15 11:31:42',
				),
                array(
                    'archiveDate' => '2017-11-15 11:31:42',
				),
                'archiveDate=2017-11-15+11%3A31%3A42',
			),
            // Live archive
            array(
                'Archive.2017-11-15 11:31:42',
                array(
                    'Versioned.mode' => 'archive',
                    'Versioned.date' => '2017-11-15 11:31:42',
				),
                array(
                    'archiveDate' => '2017-11-15 11:31:42',
				),
                'archiveDate=2017-11-15+11%3A31%3A42',
			),
		);
    }

    /**
     * @dataProvider provideTestInvalidStage
     * @param string $stage
     */
    public function testInvalidStage($stage)
    {
    	$this->setExpectedException('InvalidArgumentException');
        VersionedReadingMode::validateStage($stage);
    }

    public function provideTestInvalidStage()
    {
        return array(
            array(''),
            array('stage'),
            array('bob'),
		);
    }
}
