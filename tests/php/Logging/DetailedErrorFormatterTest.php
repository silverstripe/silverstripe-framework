<?php declare(strict_types = 1);

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
        $this->assertContains('ERROR [Emergency]: Uncaught Exception: Error', $output);
        $this->assertContains("Line 32 in $base/DetailedErrorFormatterTest/ErrorGenerator.php", $output);
        $this->assertContains('* 32:                  throw new Exception(\'Error\');', $output);
        $this->assertContains(
            'SilverStripe\\Logging\\Tests\\DetailedErrorFormatterTest\\ErrorGenerator->mockException(4)',
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

        $this->assertContains('ERRNO 401', $result, 'Status code was not found in trace');
        $this->assertContains('Denied', $result, 'Message was not found in trace');
        $this->assertContains('Line 4 in index.php', $result, 'Line or filename were not found in trace');
        $this->assertContains(self::class, $result, 'Backtrace doesn\'t show current test class');
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

        $this->assertContains('ERRNO 401', $result, 'First status code was not found in trace');
        $this->assertContains('ERRNO 404', $result, 'Second status code was not found in trace');
        $this->assertContains('Denied', $result, 'First message was not found in trace');
        $this->assertContains('Not found', $result, 'Second message was not found in trace');
    }
}
