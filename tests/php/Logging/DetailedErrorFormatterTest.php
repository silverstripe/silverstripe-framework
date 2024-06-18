<?php

namespace SilverStripe\Logging\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Logging\DetailedErrorFormatter;
use SilverStripe\Logging\Tests\DetailedErrorFormatterTest\ErrorGenerator;

class DetailedErrorFormatterTest extends SapphireTest
{
    public function testFormatWithException()
    {
        $generator = new ErrorGenerator();
        $formatter = new DetailedErrorFormatter();
        $exception = $generator->mockException();

        $output = '' . $formatter->format(['context' => [
            'exception' => $exception,
        ]]);

        $base = __DIR__;
        $this->assertStringContainsString('ERROR [Emergency]: Uncaught Exception: Error', $output);
        $this->assertStringContainsString("Line 32 in $base/DetailedErrorFormatterTest/ErrorGenerator.php", $output);
        $this->assertStringContainsString('* 32:                  throw new Exception(\'Error\');', $output);
        $this->assertStringContainsString(
            'SilverStripe\\Logging\\Tests\\DetailedErrorFormatterTest\\ErrorGenerator->mockException',
            $output
        );
    }

    public function testFormatWithoutException()
    {
        $record = [
            'code' => 401,
            'message' => 'Denied',
            'file' => 'index.php',
            'line' => 4,
        ];

        $formatter = new DetailedErrorFormatter();
        $result = $formatter->format($record);

        $this->assertStringContainsString('ERRNO 401', $result, 'Status code was not found in trace');
        $this->assertStringContainsString('Denied', $result, 'Message was not found in trace');
        $this->assertStringContainsString('Line 4 in index.php', $result, 'Line or filename were not found in trace');
        $this->assertStringContainsString(DetailedErrorFormatterTest::class, $result, 'Backtrace doesn\'t show current test class');
    }

    public function testFormatBatch()
    {
        $records = [
            [
                'code' => 401,
                'message' => 'Denied',
                'file' => 'index.php',
                'line' => 4,
            ],
            [
                'code' => 404,
                'message' => 'Not found',
                'file' => 'admin.php',
                'line' => 7,
            ],
        ];

        $formatter = new DetailedErrorFormatter();
        $result = $formatter->formatBatch($records);

        $this->assertStringContainsString('ERRNO 401', $result, 'First status code was not found in trace');
        $this->assertStringContainsString('ERRNO 404', $result, 'Second status code was not found in trace');
        $this->assertStringContainsString('Denied', $result, 'First message was not found in trace');
        $this->assertStringContainsString('Not found', $result, 'Second message was not found in trace');
    }
}
