<?php
namespace SilverStripe\Core\Manifest;

use PhpParser\Error;
use PhpParser\ErrorHandler;

/**

 * Error handler which throws, but retains the original path context.
 * For parsing errors, this is essential information to identify the issue.
 */
class ClassManifestErrorHandler implements ErrorHandler
{
    /**
     * @var String
     */
    protected $pathname;

    /**
     * @param string $pathname
     */
    public function __construct($pathname)
    {
        $this->pathname = $pathname;
    }

    public function handleError(Error $error)
    {
        $newMessage = sprintf('%s in %s', $error->getRawMessage(), $this->pathname);
        $error->setRawMessage($newMessage);
        throw $error;
    }
}
