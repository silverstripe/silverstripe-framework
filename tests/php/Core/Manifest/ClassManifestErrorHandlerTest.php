<?php
namespace SilverStripe\Core\Tests\Manifest;

use SilverStripe\Dev\SapphireTest;
use SilverStripe\Core\Manifest\ClassManifestErrorHandler;
use PhpParser\Error;

class ClassManifestErrorHandlerTest extends SapphireTest
{
    public function testIncludesPathname()
    {
        $this->expectException(Error::class);
        $this->expectExceptionMessage('my error in /my/path');
        $h = new ClassManifestErrorHandler('/my/path');
        $e = new Error('my error');
        $h->handleError($e);
    }
}
