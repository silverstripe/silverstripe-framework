<?php

namespace SilverStripe\View\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\ORM\DataObject;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;
use SilverStripe\View\Tests\SSViewerTest\SSViewerTestModel;
use SilverStripe\View\Tests\SSViewerTest\SSViewerTestModelController;
use SilverStripe\View\Tests\SSViewerTest\DummyTemplateEngine;

class SSViewerTest extends SapphireTest
{
    protected $usesDatabase = false;

    /**
     * Tests for themes helper functions, ensuring they behave as defined in the RFC at
     * https://github.com/silverstripe/silverstripe-framework/issues/5604
     */
    public function testThemesHelpers()
    {
        // Test set_themes()
        SSViewer::set_themes(['mytheme', '$default']);
        $this->assertEquals(['mytheme', '$default'], SSViewer::get_themes());

        // Ensure add_themes() prepends
        SSViewer::add_themes(['my_more_important_theme']);
        $this->assertEquals(['my_more_important_theme', 'mytheme', '$default'], SSViewer::get_themes());

        // Ensure add_themes() on theme already in cascade promotes it to the top
        SSViewer::add_themes(['mytheme']);
        $this->assertEquals(['mytheme', 'my_more_important_theme', '$default'], SSViewer::get_themes());
    }

    public function testRequirementsInjected()
    {
        Requirements::clear();

        try {
            Requirements::customCSS('pretend this is real css');
            $viewer = new SSViewer([], new DummyTemplateEngine());
            $result1 = $viewer->process('pretend this is a model')->getValue();
            // if we disable the requirements then we should get nothing
            $viewer->includeRequirements(false);
            $result2 = $viewer->process('pretend this is a model')->getValue();
        } finally {
            Requirements::restore();
        }

        $this->assertEqualIgnoringWhitespace(
            '<html><head><style type="text/css">pretend this is real css</style></head><body></body></html>',
            $result1
        );
        $this->assertEqualIgnoringWhitespace(
            '<html><head></head><body></body></html>',
            $result2
        );
    }

    public function testGetTemplatesByClass()
    {
        $this->useTestTheme(
            __DIR__ . '/SSViewerTest',
            'layouttest',
            function () {
                // Test passing a string
                $templates = SSViewer::get_templates_by_class(
                    SSViewerTestModelController::class,
                    '',
                    Controller::class
                );
                $this->assertEquals(
                    [
                    SSViewerTestModelController::class,
                    [
                        'type' => 'Includes',
                        SSViewerTestModelController::class,
                    ],
                    SSViewerTestModel::class,
                    Controller::class,
                    [
                        'type' => 'Includes',
                        Controller::class,
                    ],
                    ],
                    $templates
                );

                // Test to ensure we're stopping at the base class.
                $templates = SSViewer::get_templates_by_class(
                    SSViewerTestModelController::class,
                    '',
                    SSViewerTestModelController::class
                );
                $this->assertEquals(
                    [
                    SSViewerTestModelController::class,
                    [
                        'type' => 'Includes',
                        SSViewerTestModelController::class,
                    ],
                    SSViewerTestModel::class,
                    ],
                    $templates
                );

                // Make sure we can search templates by suffix.
                $templates = SSViewer::get_templates_by_class(
                    SSViewerTestModel::class,
                    'Controller',
                    DataObject::class
                );
                $this->assertEquals(
                    [
                    SSViewerTestModelController::class,
                    [
                        'type' => 'Includes',
                        SSViewerTestModelController::class,
                    ],
                    DataObject::class . 'Controller',
                    [
                        'type' => 'Includes',
                        DataObject::class . 'Controller',
                    ],
                    ],
                    $templates
                );

                // Let's throw something random in there.
                $this->expectException(\InvalidArgumentException::class);
                SSViewer::get_templates_by_class('no-class');
            }
        );
    }

    public function testRewriteHashlinks()
    {
        SSViewer::setRewriteHashLinksDefault(true);
        $oldServerVars = $_SERVER;

        try {
            $origRequest = Injector::inst()->has(HTTPRequest::class)
                ? Injector::inst()->get(HTTPRequest::class)
                : null;
            Injector::inst()->registerService(
                new HTTPRequest('GET', '//file.com?foo"onclick="alert(\'xss\')""'),
                HTTPRequest::class
            );

            // Note that leading double slashes have been rewritten to prevent these being mis-interepreted
            // as protocol-less absolute urls
            $base = Convert::raw2att('/file.com?foo"onclick="alert(\'xss\')""');

            $engine = new DummyTemplateEngine();
            $engine->setOutput(
                '<!DOCTYPE html>
                <html>
                    <head><base href="http://www.example.com/"></head>
                    <body>
                    <a class="external-inline" href="http://google.com#anchor">ExternalInlineLink</a>
                    <a class="external-inserted" href="http://google.com#anchor">ExternalInsertedLink</a>
                    <a class="inline" href="#anchor">InlineLink</a>
                    <a class="inserted" href="#anchor">InsertedLink</a>
                    <svg><use xlink:href="#sprite"></use></svg>
                    <body>
                </html>'
            );
            $tmpl = new SSViewer([], $engine);
            $result = $tmpl->process('pretend this is a model');
            $this->assertStringContainsString(
                '<a class="inserted" href="' . $base . '#anchor">InsertedLink</a>',
                $result
            );
            $this->assertStringContainsString(
                '<a class="external-inserted" href="http://google.com#anchor">ExternalInsertedLink</a>',
                $result
            );
            $this->assertStringContainsString(
                '<a class="inline" href="' . $base . '#anchor">InlineLink</a>',
                $result
            );
            $this->assertStringContainsString(
                '<a class="external-inline" href="http://google.com#anchor">ExternalInlineLink</a>',
                $result
            );
            $this->assertStringContainsString(
                '<svg><use xlink:href="#sprite"></use></svg>',
                $result,
                'SSTemplateParser should only rewrite anchor hrefs'
            );
        } finally {
            $_SERVER = $oldServerVars;
            if ($origRequest) {
                Injector::inst()->registerService($origRequest, HTTPRequest::class);
            } else {
                Injector::inst()->unregisterNamedObject(HTTPRequest::class);
            }
        }
    }

    public function testRewriteHashlinksInPhpMode()
    {
        SSViewer::setRewriteHashLinksDefault('php');
        $engine = new DummyTemplateEngine();
        $engine->setOutput(
            '<!DOCTYPE html>
            <html>
                <head><base href="http://www.example.com/"></head>
                <body>
                <a class="inline" href="#anchor">InlineLink</a>
                <a class="inserted" href="#anchor">InsertedLink</a>
                <svg><use xlink:href="#sprite"></use></svg>
                <body>
            </html>'
        );
        $tmpl = new SSViewer([], $engine);

        try {
            $origRequest = Injector::inst()->has(HTTPRequest::class)
                ? Injector::inst()->get(HTTPRequest::class)
                : null;
            Injector::inst()->registerService(
                new HTTPRequest('GET', 'about-us'),
                HTTPRequest::class
            );
            $result = $tmpl->process('pretend this is a model');
        } finally {
            if ($origRequest) {
                Injector::inst()->registerService($origRequest, HTTPRequest::class);
            } else {
                Injector::inst()->unregisterNamedObject(HTTPRequest::class);
            }
        }

        $code = <<<'EOC'
        <a class="inserted" href="<?php echo \SilverStripe\Core\Convert::raw2att(\SilverStripe\Control\Controller::normaliseTrailingSlash(preg_replace(
            "/^(\/)+/",
            "/",
            $_SERVER['REQUEST_URI'] ?? SilverStripe\Control\Controller::curr()?->getRequest()?->getURL(true) ?? '/about-us'
        ))); ?>#anchor">InsertedLink</a>
        EOC;
        $this->assertStringContainsString($code, $result);
        $this->assertStringContainsString(
            '<svg><use xlink:href="#sprite"></use></svg>',
            $result,
            'SSTemplateParser should only rewrite anchor hrefs'
        );
    }

    public static function provideRewriteHashlinksTrailingSlash(): array
    {
        return [
            'no trailing slash' => [
                'addTrailingSlash' => false,
                'url' => 'about-us',
                'expected' => '/about-us',
            ],
            'trailing slash' => [
                'addTrailingSlash' => true,
                'url' => 'about-us',
                'expected' => '/about-us/',
            ],
            'no trailing slash with query string' => [
                'addTrailingSlash' => false,
                'url' => 'about-us?foo=bar',
                'expected' => '/about-us?foo=bar',
            ],
            'trailing slash with query string' => [
                'addTrailingSlash' => true,
                'url' => 'about-us?foo=bar',
                'expected' => '/about-us/?foo=bar',
            ],
            'no trailing slash with a dot in the query string' => [
                'addTrailingSlash' => false,
                'url' => 'about-us?foo=bar.baz',
                'expected' => '/about-us?foo=bar.baz',
            ],
            'trailing slash with a dot in the query string' => [
                'addTrailingSlash' => true,
                'url' => 'about-us?foo=bar.baz',
                'expected' => '/about-us/?foo=bar.baz',
            ],
            'no trailing slash on home page' => [
                'addTrailingSlash' => false,
                'url' => '',
                'expected' => '/',
            ],
            'trailing slash on home page' => [
                'addTrailingSlash' => true,
                'url' => '',
                'expected' => '/',
            ],
        ];
    }

    #[DataProvider('provideRewriteHashlinksTrailingSlash')]
    public function testRewriteHashlinksTrailingSlash(bool $addTrailingSlash, string $url, string $expected): void
    {
        Controller::config()->set('add_trailing_slash', $addTrailingSlash);
        SSViewer::setRewriteHashLinksDefault(true);

        $origRequest = Injector::inst()->has(HTTPRequest::class)
            ? Injector::inst()->get(HTTPRequest::class)
            : null;
        Injector::inst()->registerService(new HTTPRequest('GET', $url), HTTPRequest::class);

        try {
            $engine = new DummyTemplateEngine();
            $engine->setOutput(
                '<html><head><base href="http://www.example.com/"></head>'
                . '<body><a href="#anchor">Link</a></body></html>'
            );
            $tmpl = new SSViewer([], $engine);
            $result = $tmpl->process('pretend this is a model');
        } finally {
            if ($origRequest) {
                Injector::inst()->registerService($origRequest, HTTPRequest::class);
            } else {
                Injector::inst()->unregisterNamedObject(HTTPRequest::class);
            }
        }

        $this->assertStringContainsString('<a href="' . Convert::raw2att($expected) . '#anchor">Link</a>', $result);
    }

    public function testGetBaseTag()
    {
        Director::config()->set('alternate_base_url', 'https://example.com/');

        // Enabled should return a base tag
        SSViewer::config()->set('enable_base_tag', true);
        $this->assertSame('<base href="https://example.com/">', SSViewer::getBaseTag());

        // XHTML mode should return a self-closing base tag
        $this->assertSame('<base href="https://example.com/" />', SSViewer::getBaseTag(true));

        // Disabled should return an empty string
        SSViewer::config()->set('enable_base_tag', false);
        $this->assertSame('', SSViewer::getBaseTag());
        $this->assertSame('', SSViewer::getBaseTag(true));
    }

    private function assertEqualIgnoringWhitespace(string $a, string $b, string $message = ''): void
    {
        $this->assertEquals(preg_replace('/\s+/', '', $a), preg_replace('/\s+/', '', $b), $message);
    }
}
