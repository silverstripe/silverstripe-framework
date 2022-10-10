<?php

namespace SilverStripe\Logging;

use SilverStripe\Dev\Debug;
use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;
use Exception;

/**
 * Monolog-compatible error handler that will output a detailed error message to the screen.
 */
class DetailedErrorFormatter implements FormatterInterface
{
    public function format(array|LogRecord $record)
    {
        if (isset($record['context']['exception'])) {
            /** @var Exception $exception */
            $exception = $record['context']['exception'];
            $context = [
                'code' => $exception->getCode(),
                'message' => 'Uncaught ' . get_class($exception) . ': ' . $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace(),
            ];
        } else {
            $context = isset($record['context']) ? $record['context'] : $record;
            foreach (['code', 'message', 'file', 'line'] as $key) {
                if (!isset($context[$key])) {
                    $context[$key] = isset($record[$key]) ? $record[$key] : null;
                }
            }

            if (!isset($context['trace'])) {
                $trace = debug_backtrace();

                // Filter out monolog plumbing from the trace
                // If the context file & line isn't found in the trace, then the trace is most likely
                // call to the fatal error handler and is not useful, so exclude it entirely
                $i = $this->findInTrace($trace, $context['file'], $context['line']);
                if ($i !== null) {
                    $context['trace'] = array_slice($trace ?? [], $i ?? 0);
                } else {
                    $context['trace'] = null;
                }
            }
        }

        return $this->output(
            $context['code'],
            $context['message'],
            $context['file'],
            $context['line'],
            $context['trace']
        );
    }

    public function formatBatch(array $records)
    {
        return implode("\n", array_map([$this, 'format'], $records ?? []));
    }

    /**
     * Find a call on the given file & line in the trace
     * @param array $trace The result of debug_backtrace()
     * @param string $file The filename to look for
     * @param string $line The line number to look for
     * @return int|null The matching row number, if found, or null if not found
     */
    protected function findInTrace(array $trace, $file, $line)
    {
        foreach ($trace as $i => $call) {
            if (isset($call['file']) && isset($call['line']) && $call['file'] == $file && $call['line'] == $line) {
                return $i;
            }
        }
        return null;
    }

    /**
     * Render a developer facing error page, showing the stack trace and details
     * of the code where the error occurred.
     *
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @param array $errcontext
     * @return string
     */
    protected function output($errno, $errstr, $errfile, $errline, $errcontext)
    {
        $reporter = Debug::create_debug_view();

        // Coupling alert: This relies on knowledge of how the director gets its URL, it could be improved.
        $httpRequest = null;
        if (isset($_SERVER['REQUEST_URI'])) {
            $httpRequest = $_SERVER['REQUEST_URI'];
        }

        if (isset($_SERVER['REQUEST_METHOD'])) {
            $httpRequest = $_SERVER['REQUEST_METHOD'] . ' ' . $httpRequest;
        }

        $output = $reporter->renderHeader();
        $output .= $reporter->renderError($httpRequest, $errno, $errstr, $errfile, $errline);

        if (file_exists($errfile ?? '')) {
            $lines = file($errfile ?? '');

            // Make the array 1-based
            array_unshift($lines, "");
            unset($lines[0]);

            $offset = $errline-10;
            $lines = array_slice($lines ?? [], $offset ?? 0, 16, true);
            $output .= $reporter->renderSourceFragment($lines, $errline);
        }
        $output .= $reporter->renderTrace($errcontext);
        $output .= $reporter->renderFooter();

        return $output;
    }
}
