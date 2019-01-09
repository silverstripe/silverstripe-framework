<?php

namespace SilverStripe\Control\Tests;

use SilverStripe\Control\Director;
use SilverStripe\Control\SimpleResourceURLGenerator;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Core\Manifest\Module;
use SilverStripe\Core\Manifest\ResourceURLGenerator;
use SilverStripe\Dev\SapphireTest;

class SimpleResourceURLGeneratorTest extends SapphireTest
{
    protected function setUp()
    {
        parent::setUp();
        Director::config()->set(
            'alternate_base_folder',
            __DIR__ . '/SimpleResourceURLGeneratorTest/_fakewebroot'
        );
        Director::config()->set(
            'alternate_base_url',
            'http://www.mysite.com/'
        );
        Director::config()->set(
            'alternate_public_dir',
            'public'
        );
    }

    public function testAddMTime()
    {
        /** @var SimpleResourceURLGenerator $generator */
        $generator = Injector::inst()->get(ResourceURLGenerator::class);
        $mtime = filemtime(
            __DIR__ . '/SimpleResourceURLGeneratorTest/_fakewebroot/basemodule/client/file.js'
        );
        $this->assertEquals(
            '/'. RESOURCES_DIR . '/basemodule/client/file.js?m=' . $mtime,
            $generator->urlForResource('basemodule/client/file.js')
        );
    }

    public function testVendorResource()
    {
        /** @var SimpleResourceURLGenerator $generator */
        $generator = Injector::inst()->get(ResourceURLGenerator::class);
        $mtime = filemtime(
            __DIR__ . '/SimpleResourceURLGeneratorTest/_fakewebroot/vendor/silverstripe/mymodule/client/style.css'
        );
        $this->assertEquals(
            '/'. RESOURCES_DIR . '/vendor/silverstripe/mymodule/client/style.css?m=' . $mtime,
            $generator->urlForResource('vendor/silverstripe/mymodule/client/style.css')
        );
    }

    public function testPublicDirResource()
    {
        /** @var SimpleResourceURLGenerator $generator */
        $generator = Injector::inst()->get(ResourceURLGenerator::class);
        $mtime = filemtime(
            __DIR__ . '/SimpleResourceURLGeneratorTest/_fakewebroot/public/basemodule/css/style.css'
        );

        $this->assertEquals(
            '/basemodule/css/style.css?m=' . $mtime,
            $generator->urlForResource('public/basemodule/css/style.css')
        );

        $mtime = filemtime(
            __DIR__ . '/SimpleResourceURLGeneratorTest/_fakewebroot/basemodule/client/file.js'
        );

        $this->assertEquals(
            '/'. RESOURCES_DIR . '/basemodule/client/file.js?m=' . $mtime,
            $generator->urlForResource('basemodule/client/file.js')
        );
    }

    public function testModuleResource()
    {
        /** @var SimpleResourceURLGenerator $generator */
        $generator = Injector::inst()->get(ResourceURLGenerator::class);
        $module = new Module(
            __DIR__ . '/SimpleResourceURLGeneratorTest/_fakewebroot/vendor/silverstripe/mymodule/',
            __DIR__ . '/SimpleResourceURLGeneratorTest/_fakewebroot/'
        );
        $mtime = filemtime(
            __DIR__ . '/SimpleResourceURLGeneratorTest/_fakewebroot/vendor/silverstripe/mymodule/client/style.css'
        );
        $this->assertEquals(
            '/'. RESOURCES_DIR . '/vendor/silverstripe/mymodule/client/style.css?m=' . $mtime,
            $generator->urlForResource($module->getResource('client/style.css'))
        );
    }

    public function testURLForResourceFailsGracefully()
    {
        /** @var SimpleResourceURLGenerator $generator */
        $generator = Injector::inst()->get(ResourceURLGenerator::class);
        $url = @$generator->urlForResource('doesnotexist.jpg');
        $this->assertEquals('/doesnotexist.jpg', $url);
    }

    public function testAbsoluteResource()
    {
        /** @var SimpleResourceURLGenerator $generator */
        $generator = Injector::inst()->get(ResourceURLGenerator::class);
        $fakeExternalAsset = 'https://cdn.example.com/some_library.css';
        $this->assertEquals($fakeExternalAsset, $generator->urlForResource($fakeExternalAsset));
    }
}
