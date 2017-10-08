<?php

namespace SilverStripe\Core\Tests\Startup\ErrorControlChainTest;

use ReflectionFunction;
use SilverStripe\Core\Manifest\ClassLoader;
use SilverStripe\Core\Startup\ErrorControlChain;

/**
 * An extension of ErrorControlChain that runs the chain in a subprocess.
 *
 * We need this because ErrorControlChain only suppresses uncaught fatal errors, and
 * that would kill PHPUnit execution
 */
class ErrorControlChainTest_Chain extends ErrorControlChain
{

    protected $displayErrors = 'STDERR';

    /**
     * Modify method visibility to public for testing
     *
     * @return string
     */
    public function getDisplayErrors()
    {
        // Protect manipulation of underlying php_ini values
        return $this->displayErrors;
    }

    /**
     * Modify method visibility to public for testing
     *
     * @param mixed $errors
     */
    public function setDisplayErrors($errors)
    {
        // Protect manipulation of underlying php_ini values
        $this->displayErrors = $errors;
    }

    // Change function visibility to be testable directly
    public function translateMemstring($memstring)
    {
        return parent::translateMemstring($memstring);
    }

    function executeInSubprocess($includeStderr = false)
    {
        // Get the path to the ErrorControlChain class
        $erroControlClass = 'SilverStripe\\Core\\Startup\\ErrorControlChain';
        $classpath = ClassLoader::inst()->getItemPath($erroControlClass);
        $suppression = $this->suppression ? 'true' : 'false';

        // Start building a PHP file that will execute the chain
        $src = '<' . "?php
require_once '$classpath';

\$chain = new $erroControlClass();

\$chain->setSuppression($suppression);

\$chain
";

        // For each step, use reflection to pull out the call, stick in the the PHP source we're building
        foreach ($this->steps as $step) {
            $func = new ReflectionFunction($step['callback']);
            $source = file($func->getFileName());

            $start_line = $func->getStartLine() - 1;
            $end_line = $func->getEndLine();
            $length = $end_line - $start_line;

            $src .= implode("", array_slice($source, $start_line, $length)) . "\n";
        }

        // Finally add a line to execute the chain
        $src .= "->execute();";

        // Now stick it in a temporary file & run it
        $codepath = TEMP_PATH . DIRECTORY_SEPARATOR . 'ErrorControlChainTest_' . sha1($src) . '.php';

        if ($includeStderr) {
            $null = '&1';
        } else {
            $null = is_writeable('/dev/null') ? '/dev/null' : 'NUL';
        }

        file_put_contents($codepath, $src);
        exec("php $codepath 2>$null", $stdout, $errcode);
        unlink($codepath);

        return array(implode("\n", $stdout), $errcode);
    }
}
