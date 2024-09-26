<?php

namespace SilverStripe\PolyExecution;

use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\LogRecord;
use SilverStripe\Core\Injector\Injectable;

/**
 * Log handler that uses a PolyOutput to output log entries to the browser or CLI.
 */
class PolyOutputLogHandler extends AbstractProcessingHandler
{
    use Injectable;

    private PolyOutput $output;

    public function __construct(PolyOutput $output, int|string|Level $level = Level::Debug, bool $bubble = true)
    {
        $this->output = $output;
        parent::__construct($level, $bubble);
    }

    protected function write(LogRecord $record): void
    {
        $message = rtrim($record->formatted, PHP_EOL);
        $this->output->write($message, true, PolyOutput::OUTPUT_RAW);
    }
}
