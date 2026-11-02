<?php

namespace SilverStripe\Dev\Tests\Tasks;

use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\Dev\Tasks\i18nTextCollectorTask;
use SilverStripe\Dev\Tests\Tasks\i18nTextCollectorTaskTest\TestWriter;
use SilverStripe\PolyExecution\PolyOutput;
use stdClass;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Output\BufferedOutput;
use Throwable;

class i18nTextCollectorTaskTest extends SapphireTest
{
    public static function provideWriterOptionRejectsNonWriterClass(): array
    {
        return [
            'class spec with constructor arguments' => [TestWriter::class . "('injected')"],
            'class which does not implement the writer interface' => [stdClass::class],
            'value which is not a class name' => ['not a class name'],
            'array of class specs' => [[TestWriter::class . "('injected')"]],
            'integer' => [123],
            'float' => [1.5],
            'boolean' => [true],
        ];
    }

    #[DataProvider('provideWriterOptionRejectsNonWriterClass')]
    public function testWriterOptionRejectsNonWriterClass(mixed $writerName): void
    {
        TestWriter::reset();
        $task = new i18nTextCollectorTask();
        $input = new ArrayInput(['--writer' => $writerName], new InputDefinition($task->getOptions()));
        $input->setInteractive(false);
        $output = new PolyOutput(PolyOutput::FORMAT_ANSI, wrappedOutput: new BufferedOutput());
        $exception = null;
        try {
            $task->run($input, $output);
        } catch (Throwable $e) {
            $exception = $e;
        }
        $this->assertFalse(TestWriter::wasConstructed());
        $this->assertInstanceOf(InvalidArgumentException::class, $exception);
    }
}
