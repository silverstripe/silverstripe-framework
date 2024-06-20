<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Convert;

/**
 * A basic HTML wrapper for stylish rendering of a development info view.
 * Used to output error messages, and test results.
 */
class CliDebugView extends DebugView
{

    /**
     * Render HTML header for development views
     *
     * @param HTTPRequest $httpRequest
     * @return string
     */
    public function renderHeader($httpRequest = null)
    {
        return null;
    }

    /**
     * Render HTML footer for development views
     */
    public function renderFooter()
    {
    }

    /**
     * Write information about the error to the screen
     *
     * @param string $httpRequest
     * @param int $errno
     * @param string $errstr
     * @param string $errfile
     * @param int $errline
     * @return string
     */
    public function renderError($httpRequest, $errno, $errstr, $errfile, $errline)
    {
        if (!isset(CliDebugView::$error_types[$errno])) {
            $errorTypeTitle = "UNKNOWN TYPE, ERRNO $errno";
        } else {
            $errorTypeTitle = CliDebugView::$error_types[$errno]['title'];
        }
        $output = CLI::text("ERROR [" . $errorTypeTitle . "]: $errstr\nIN $httpRequest\n", "red", null, true);
        $output .= CLI::text("Line $errline in $errfile\n\n", "red");

        return $output;
    }

    /**
     * Write a fragment of the a source file
     *
     * @param array $lines An array of file lines; the keys should be the original line numbers
     * @param int $errline Index of the line in $lines which has the error
     * @return string
     */
    public function renderSourceFragment($lines, $errline)
    {
        $output = "Source\n======\n";
        foreach ($lines as $offset => $line) {
            $output .= ($offset == $errline) ? "* " : "  ";
            $output .= str_pad("$offset:", 5);
            $output .= wordwrap($line ?? '', static::config()->columns ?? 0, "\n       ");
        }
        $output .= "\n";

        return $output;
    }

    /**
     * Write a backtrace
     *
     * @param array $trace
     * @return string
     */
    public function renderTrace($trace = null)
    {
        $output = "Trace\n=====\n";
        $output .= Backtrace::get_rendered_backtrace($trace ? $trace : debug_backtrace(), true);

        return $output;
    }

    public function renderParagraph($text)
    {
        return wordwrap($text ?? '', static::config()->columns ?? 0) . "\n\n";
    }

    /**
     * Render the information header for the view
     *
     * @param string $title
     * @param string $subtitle
     * @param string $description
     * @return string
     */
    public function renderInfo($title, $subtitle, $description = null)
    {
        $output = wordwrap(strtoupper($title ?? ''), static::config()->columns ?? 0) . "\n";
        $output .= wordwrap($subtitle ?? '', static::config()->columns ?? 0) . "\n";
        $output .= str_repeat('-', min(static::config()->columns, max(strlen($title ?? ''), strlen($subtitle ?? ''))) ?? 0) . "\n";
        $output .= wordwrap($description ?? '', static::config()->columns ?? 0) . "\n\n";

        return $output;
    }

    public function renderVariable($val, $caller)
    {
        $output = PHP_EOL;
        $output .= CLI::text(str_repeat('=', static::config()->columns ?? 0), 'green');
        $output .= PHP_EOL;
        $output .= CLI::text($this->formatCaller($caller), 'blue', null, true);
        $output .= PHP_EOL . PHP_EOL;
        if (is_string($val)) {
            $output .= wordwrap($val ?? '', static::config()->columns ?? 0);
        } else {
            $output .= var_export($val, true);
        }
        $output .= PHP_EOL;
        $output .= CLI::text(str_repeat('=', static::config()->columns ?? 0), 'green');
        $output .= PHP_EOL;

        return $output;
    }

    /**
     * Similar to renderVariable() but respects debug() method on object if available
     *
     * @param mixed $val
     * @param array $caller
     * @param bool $showHeader
     * @return string
     */
    public function debugVariable($val, $caller, $showHeader = true)
    {
        $text = $this->debugVariableText($val);
        if ($showHeader) {
            $callerFormatted = $this->formatCaller($caller);
            return "Debug ($callerFormatted)\n{$text}\n\n";
        } else {
            return $text;
        }
    }

    /**
     * Get debug text for this object
     *
     * @param mixed $val
     * @return string
     */
    public function debugVariableText($val)
    {
        // Check debug
        if (is_object($val) && ClassInfo::hasMethod($val, 'debug')) {
            return $val->debug();
        }

        // Format as array
        if (is_array($val)) {
            $result = '';
            foreach ($val as $key => $valItem) {
                $valText = $this->debugVariableText($valItem);
                $result .= "$key = $valText\n";
            }
            return $result;
        }

        // Format object
        if (is_object($val)) {
            return print_r($val, true);
        }

        // Format bool
        if (is_bool($val)) {
            return '(bool) ' . ($val ? 'true' : 'false');
        }

        // Format text
        if (is_string($val)) {
            return wordwrap($val ?? '', static::config()->columns ?? 0);
        }

        // Other
        return var_export($val, true);
    }

    public function renderMessage($message, $caller, $showHeader = true)
    {
        $header = '';
        if ($showHeader) {
            $file = basename($caller['file'] ?? '');
            $line = $caller['line'];
            $header .= "Debug (line {$line} of {$file}):\n";
        }
        return $header . "{$message}\n\n";
    }
}
