<?php
namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Manifest\ClassManifestErrorHandler;
use PhpParser\Error;

class ClassManifestErrorHandlerTest extends SapphireTest
{
    /**
     * @expectedException \PhpParser\Error
     * @expectedExceptionMessage my error in /my/path
     */
    public function testIncludesPathname()
    {
        $h = new ClassManifestErrorHandler('/my/path');
        $e = new Error('my error');
        $h->handleError($e);
    }
}
