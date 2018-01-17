<?php

namespace SilverStripe\Logging\Tests;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Logging\DetailedErrorFormatter;
use SilverStripe\Logging\Tests\DetailedErrorFormatterTest\ErrorGenerator;

class DetailedErrorFormatterTest extends SapphireTest
{
    public function testFormat()
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
}
