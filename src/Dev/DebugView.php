<?php

namespace SilverStripe\Dev;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\Core\Manifest\ModuleResourceLoader;

/**
 * A basic HTML wrapper for stylish rendering of a developement info view.
 * Used to output error messages, and test results.
 */
class DebugView
{
    use Configurable;
    use Injectable;

    /**
     * Column size to wrap long strings to
     *
     * @var int
     * @config
     */
    private static $columns = 100;

    protected static $error_types = array(
        0 => array(
            'title' => 'Emergency',
            'class' => 'error'
        ),
        1 => array(
            'title' => 'Alert',
            'class' => 'error'
        ),
        2 => array(
            'title' => 'Critical',
            'class' => 'error'
        ),
        3 => array(
            'title' => 'Error',
            'class' => 'error'
        ),
        4 =>  array(
            'title' => 'Warning',
            'class' => 'warning'
        ),
        5 => array(
            'title' => 'Notice',
            'class' => 'notice'
        ),
        6 => array(
            'title' => 'Information',
            'class' => 'info'
        ),
        7=> array(
            'title' => 'SilverStripe\\Dev\\Debug',
            'class' => 'debug'
        ),
        E_USER_ERROR => array(
            'title' => 'User Error',
            'class' => 'error'
        ),
        E_CORE_ERROR => array(
            'title' => 'Core Error',
            'class' => 'error'
        ),
        E_NOTICE => array(
            'title' => 'Notice',
            'class' => 'notice'
        ),
        E_USER_NOTICE => array(
            'title' => 'User Notice',
            'class' => 'notice'
        ),
        E_DEPRECATED => array(
            'title' => 'Deprecated',
            'class' => 'notice'
        ),
        E_USER_DEPRECATED => array(
            'title' => 'User Deprecated',
            'class' => 'notice'
        ),
        E_CORE_ERROR => array(
            'title' => 'Core Error',
            'class' => 'error'
        ),
        E_WARNING => array(
            'title' => 'Warning',
            'class' => 'warning'
        ),
        E_CORE_WARNING => array(
            'title' => 'Core Warning',
            'class' => 'warning'
        ),
        E_USER_WARNING => array(
            'title' => 'User Warning',
            'class' => 'warning'
        ),
        E_STRICT => array(
            'title' => 'Strict Notice',
            'class' => 'notice'
        ),
        E_RECOVERABLE_ERROR => array(
            'title' => 'Recoverable Error',
            'class' => 'warning'
        )
    );

    protected static $unknown_error = array(
        'title' => 'Unknown Error',
        'class' => 'error'
    );

    /**
     * Generate breadcrumb links to the URL path being displayed
     *
     * @return string
     */
    public function Breadcrumbs()
    {
        $basePath = str_replace(Director::protocolAndHost(), '', Director::absoluteBaseURL());
        $relPath = parse_url(
            substr($_SERVER['REQUEST_URI'], strlen($basePath), strlen($_SERVER['REQUEST_URI'])),
            PHP_URL_PATH
        );
        $parts = explode('/', $relPath);
        $base = Director::absoluteBaseURL();
        $pathPart = "";
        $pathLinks = array();
        foreach ($parts as $part) {
            if ($part != '') {
                $pathPart .= "$part/";
                $pathLinks[] = "<a href=\"$base$pathPart\">$part</a>";
            }
        }
        return implode('&nbsp;&rarr;&nbsp;', $pathLinks);
    }

    /**
     * @deprecated 4.0.0:5.0.0 Use renderHeader() instead
     */
    public function writeHeader()
    {
        Deprecation::notice('4.0', 'Use renderHeader() instead');
        echo $this->renderHeader();
    }

    /**
     * @deprecated 4.0.0:5.0.0 Use renderInfo() instead
     */
    public function writeInfo($title, $subtitle, $description = false)
    {
        Deprecation::notice('4.0', 'Use renderInfo() instead');
        echo $this->renderInfo($title, $subtitle, $description);
    }

    /**
     * @deprecated 4.0.0:5.0.0 Use renderFooter() instead
     */
    public function writeFooter()
    {
        Deprecation::notice('4.0', 'Use renderFooter() instead');
        echo $this->renderFooter();
    }

    /**
     * @deprecated 4.0.0:5.0.0 Use renderError() instead
     */
    public function writeError($httpRequest, $errno, $errstr, $errfile, $errline)
    {
        Deprecation::notice('4.0', 'Use renderError() instead');
        echo $this->renderError($httpRequest, $errno, $errstr, $errfile, $errline);
    }

    /**
     * @deprecated 4.0.0:5.0.0 Use renderSourceFragment() instead
     */
    public function writeSourceFragment($lines, $errline)
    {
        Deprecation::notice('4.0', 'Use renderSourceFragment() instead');
        echo $this->renderSourceFragment($lines, $errline);
    }

    /**
     * @deprecated 4.0.0:5.0.0 Use renderTrace() instead
     */
    public function writeTrace($trace)
    {
        Deprecation::notice('4.0', 'Use renderTrace() instead');
        echo $this->renderTrace($trace);
    }

    /**
     * @deprecated 4.0.0:5.0.0 Use renderVariable() instead
     */
    public function writeVariable($val, $caller)
    {
        Deprecation::notice('4.0', 'Use renderVariable() instead');
        echo $this->renderVariable($val, $caller);
    }

    /**
     * Render HTML header for development views
     *
     * @param HTTPRequest $httpRequest
     * @return string
     */
    public function renderHeader($httpRequest = null)
    {
        $url = htmlentities(
            $_SERVER['REQUEST_METHOD'] . ' ' . $_SERVER['REQUEST_URI'],
            ENT_COMPAT,
            'UTF-8'
        );

        $debugCSS = ModuleResourceLoader::singleton()
            ->resolveURL('silverstripe/framework:client/styles/debug.css');
        $output = '<!DOCTYPE html><html><head><title>' . $url . '</title>';
        $output .= '<link rel="stylesheet" type="text/css" href="' . $debugCSS . '" />';
        $output .= '</head>';
        $output .= '<body>';

        return $output;
    }

    /**
     * Render the information header for the view
     *
     * @param string $title The main title
     * @param string $subtitle The subtitle
     * @param string|bool $description The description to show
     * @return string
     */
    public function renderInfo($title, $subtitle, $description = false)
    {
        $output = '<div class="info header">';
        $output .= "<h1>" . Convert::raw2xml($title) . "</h1>";
        if ($subtitle) {
            $output .= "<h3>" . Convert::raw2xml($subtitle) . "</h3>";
        }
        if ($description) {
            $output .= "<p>$description</p>";
        } else {
            $output .= $this->Breadcrumbs();
        }
        $output .= '</div>';

        return $output;
    }

    /**
     * Render HTML footer for development views
     * @return string
     */
    public function renderFooter()
    {
        return "</body></html>";
    }

    /**
     * Render an error.
     *
     * @param string $httpRequest the kind of request
     * @param int $errno Codenumber of the error
     * @param string $errstr The error message
     * @param string $errfile The name of the soruce code file where the error occurred
     * @param int $errline The line number on which the error occured
     * @return string
     */
    public function renderError($httpRequest, $errno, $errstr, $errfile, $errline)
    {
        $errorType = isset(self::$error_types[$errno]) ? self::$error_types[$errno] : self::$unknown_error;
        $httpRequestEnt = htmlentities($httpRequest, ENT_COMPAT, 'UTF-8');
        if (ini_get('html_errors')) {
            $errstr = strip_tags($errstr);
        } else {
            $errstr = Convert::raw2xml($errstr);
        }
        $output = '<div class="header info ' . $errorType['class'] . '">';
        $output .= "<h1>[" . $errorType['title'] . '] ' . $errstr . "</h1>";
        $output .= "<h3>$httpRequestEnt</h3>";
        $output .= "<p>Line <strong>$errline</strong> in <strong>$errfile</strong></p>";
        $output .= '</div>';

        return $output;
    }

    /**
     * Render a fragment of the a source file
     *
     * @param array $lines An array of file lines; the keys should be the original line numbers
     * @param int $errline The line of the error
     * @return string
     */
    public function renderSourceFragment($lines, $errline)
    {
        $output = '<div class="info"><h3>Source</h3>';
        $output .= '<pre>';
        foreach ($lines as $offset => $line) {
            $line = htmlentities($line, ENT_COMPAT, 'UTF-8');
            if ($offset == $errline) {
                $output .= "<span>$offset</span> <span class=\"error\">$line</span>";
            } else {
                $output .= "<span>$offset</span> $line";
            }
        }
        $output .= '</pre></div>';

        return $output;
    }

    /**
     * Render a call track
     *
     * @param  array $trace The debug_backtrace() array
     * @return string
     */
    public function renderTrace($trace)
    {
        $output = '<div class="info">';
        $output .= '<h3>Trace</h3>';
        $output .= Backtrace::get_rendered_backtrace($trace);
        $output .= '</div>';

        return $output;
    }

    /**
     * Render an arbitrary paragraph.
     *
     * @param  string $text The HTML-escaped text to render
     * @return string
     */
    public function renderParagraph($text)
    {
        return '<div class="info"><p>' . $text . '</p></div>';
    }

    /**
     * Formats the caller of a method
     *
     * @param  array $caller
     * @return string
     */
    protected function formatCaller($caller)
    {
        $return = basename($caller['file']) . ":" . $caller['line'];
        if (!empty($caller['class']) && !empty($caller['function'])) {
            $return .= " - {$caller['class']}::{$caller['function']}()";
        }
        return $return;
    }

    /**
     * Outputs a variable in a user presentable way
     *
     * @param  object $val
     * @param  array $caller Caller information
     * @return string
     */
    public function renderVariable($val, $caller)
    {
        $output = '<pre style="background-color:#ccc;padding:5px;font-size:14px;line-height:18px;">';
        $output .= "<span style=\"font-size: 12px;color:#666;\">" . $this->formatCaller($caller) . " - </span>\n";
        if (is_string($val)) {
            $output .= wordwrap($val, self::config()->columns);
        } else {
            $output .= var_export($val, true);
        }
        $output .= '</pre>';

        return $output;
    }

    public function renderMessage($message, $caller, $showHeader = true)
    {
        $header = '';
        if ($showHeader) {
            $file = basename($caller['file']);
            $line = $caller['line'];
            $header .= "<b>Debug (line {$line} of {$file}):</b>\n";
        }
        return "<p class=\"message warning\">\n" . $header . Convert::raw2xml($message) . "</p>\n";
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
            return "<div style=\"background-color: white; text-align: left;\">\n<hr>\n"
                . "<h3>Debug <span style=\"font-size: 65%\">($callerFormatted)</span>\n</h3>\n"
                . $text
                . "</div>";
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
            foreach ($val as $key => $valueItem) {
                $keyText = Convert::raw2xml($key);
                $valueText = $this->debugVariableText($valueItem);
                $result .= "<li>{$keyText} = {$valueText}</li>\n";
            }
            return "<ul>\n{$result}</ul>\n";
        }

        // Format object
        if (is_object($val)) {
            return var_export($val, true);
        }

        // Format bool
        if (is_bool($val)) {
            return '(bool) ' . ($val ? 'true' : 'false');
        }

        // Format text
        $html = Convert::raw2xml($val);
        return "<pre style=\"font-family: Courier new, serif\">{$html}</pre>\n";
    }
}
