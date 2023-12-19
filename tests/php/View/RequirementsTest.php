<?php

namespace SilverStripe\View\Tests;

use InvalidArgumentException;
use SilverStripe\Control\Director;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\i18n\i18n;
use SilverStripe\View\Requirements;
use SilverStripe\View\ArrayData;
use Silverstripe\Assets\Dev\TestAssetStore;
use SilverStripe\View\Requirements_Backend;
use SilverStripe\Core\Manifest\ResourceURLGenerator;
use SilverStripe\Control\SimpleResourceURLGenerator;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\Deprecation;
use SilverStripe\View\SSViewer;
use SilverStripe\View\ThemeResourceLoader;

class RequirementsTest extends SapphireTest
{

    /**
     * @var ThemeResourceLoader
     */
    protected $oldThemeResourceLoader = null;

    static $html_template = '<html><head></head><body></body></html>';

    protected function setUp(): void
    {
        parent::setUp();
        Director::config()->set('alternate_base_folder', __DIR__ . '/SSViewerTest');
        Director::config()->set('alternate_base_url', 'http://www.mysite.com/basedir/');
        Director::config()->set('alternate_public_dir', 'public'); // Enforce public dir
        // Add public as a theme in itself
        SSViewer::set_themes([SSViewer::PUBLIC_THEME, SSViewer::DEFAULT_THEME]);
        TestAssetStore::activate('RequirementsTest'); // Set backend root to /RequirementsTest
        $this->oldThemeResourceLoader = ThemeResourceLoader::inst();
    }

    protected function tearDown(): void
    {
        ThemeResourceLoader::set_instance($this->oldThemeResourceLoader);
        TestAssetStore::reset();
        parent::tearDown();
    }

    public function testExternalUrls()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $backend->setCombinedFilesEnabled(true);

        $backend->javascript('http://www.mydomain.com/test.js');
        $backend->javascript('https://www.mysecuredomain.com/test.js');
        $backend->javascript('//scheme-relative.example.com/test.js');
        $backend->javascript('http://www.mydomain.com:3000/test.js');
        $backend->css('http://www.mydomain.com/test.css');
        $backend->css('https://www.mysecuredomain.com/test.css');
        $backend->css('//scheme-relative.example.com/test.css');
        $backend->css('http://www.mydomain.com:3000/test.css');

        $html = $backend->includeInHTML(self::$html_template);

        $this->assertStringContainsString('http://www.mydomain.com/test.js', $html, 'Load external javascript URL');
        $this->assertStringContainsString('https://www.mysecuredomain.com/test.js', $html, 'Load external secure javascript URL');
        $this->assertStringContainsString('//scheme-relative.example.com/test.js', $html, 'Load external scheme-relative JS');
        $this->assertStringContainsString('http://www.mydomain.com:3000/test.js', $html, 'Load external with port');
        $this->assertStringContainsString('http://www.mydomain.com/test.css', $html, 'Load external CSS URL');
        $this->assertStringContainsString('https://www.mysecuredomain.com/test.css', $html, 'Load external secure CSS URL');
        $this->assertStringContainsString('//scheme-relative.example.com/test.css', $html, 'Load scheme-relative CSS URL');
        $this->assertStringContainsString('http://www.mydomain.com:3000/test.css', $html, 'Load external with port');
    }

    public function testResolveCSSReferencesDisabled()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        Config::forClass(get_class($backend))->set('resolve_relative_css_refs', false);

        $backend->combineFiles(
            'RequirementsTest_pc.css',
            [
                'css/RequirementsTest_d.css',
                'css/deep/deeper/RequirementsTest_p.css'
            ]
        );

        $backend->includeInHTML(self::$html_template);

        // we get the file path here
        $allCSS = $backend->getCSS();
        $this->assertCount(
            1,
            $allCSS,
            'only one combined file'
        );

        $files = array_keys($allCSS);
        $combinedFileName = $files[0];
        $combinedFileName = str_replace('/' . ASSETS_DIR . '/', '/', $combinedFileName);

        $combinedFilePath = TestAssetStore::base_path() . $combinedFileName;

        $content = file_get_contents($combinedFilePath);

        /* DISABLED COMBINED CSS URL RESOLVER IGNORED ONE DOT */
        $this->assertStringContainsString(
            ".p0 { background: url(./zero.gif); }",
            $content,
            'disabled combined css url resolver ignored one dot'
        );

        /* DISABLED COMBINED CSS URL RESOLVER IGNORED DOUBLE-DOT */
        $this->assertStringContainsString(
            ".p1 { background: url(../one.gif); }",
            $content,
            'disabled combined css url resolver ignored double-dot'
        );
    }

    public function testResolveCSSReferences()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        Config::forClass(get_class($backend))->set('resolve_relative_css_refs', true);

        $backend->combineFiles(
            'RequirementsTest_pc.css',
            [
                'css/RequirementsTest_d.css',
                'css/deep/deeper/RequirementsTest_p.css'
            ]
        );

        $backend->includeInHTML(self::$html_template);

        // we get the file path here
        $allCSS = $backend->getCSS();
        $this->assertCount(
            1,
            $allCSS,
            'only one combined file'
        );
        $files = array_keys($allCSS);
        $combinedFileName = $files[0];
        $combinedFileName = str_replace('/' . ASSETS_DIR . '/', '/', $combinedFileName);

        $combinedFilePath = TestAssetStore::base_path() . $combinedFileName;

        /* COMBINED JAVASCRIPT FILE EXISTS */
        $this->assertTrue(
            file_exists($combinedFilePath),
            'combined css file exists'
        );

        $content = file_get_contents($combinedFilePath);

        /* COMBINED CSS URL RESOLVER IGNORE FULL URLS */
        $this->assertStringContainsString(
            ".url { background: url(http://example.com/zero.gif); }",
            $content,
            'combined css url resolver ignore full urls'
        );

        /* COMBINED CSS URL RESOLVER DECODED ONE DOT */
        $this->assertStringContainsString(
            ".p0 { background: url(/css/deep/deeper/zero.gif); }",
            $content,
            'combined css url resolver decoded one dot'
        );

        /* COMBINED CSS URL RESOLVER DECODED NO DOTS */
        $this->assertStringContainsString(
            ".p0-plain { background: url(/css/deep/deeper/zero.gif); }",
            $content,
            'combined css url resolver decoded no dots'
        );

        /* COMBINED CSS URL RESOLVER DAMAGED A QUERYSTRING */
        $this->assertStringContainsString(
            ".p0-qs { background: url(/css/deep/deeper/zero.gif?some=param); }",
            $content,
            'combined css url resolver damaged a querystring'
        );

        /* COMBINED CSS URL RESOLVER DECODED ONE DOT WITH SINGLE QUOTES */
        $this->assertStringContainsString(
            ".p0sq { background: url('/css/deep/deeper/zero-sq.gif'); }",
            $content,
            'combined css url resolver decoded one dot with single quotes'
        );

        /* COMBINED CSS URL RESOLVER DECODED ONE DOT WITH DOUBLE QUOTES */
        $this->assertStringContainsString(
            ".p0dq { background: url(\"/css/deep/deeper/zero-dq.gif\"); }",
            $content,
            'combined css url resolver decoded one dot with double quotes'
        );

        /* COMBINED CSS URL RESOLVER DECODED ONE DOT WITH DOUBLE QUOTES AND SPACES NEW LINE */
        $this->assertStringContainsString(
            "\n  \"/css/deep/deeper/zero-dq-nls.gif\"\n",
            $content,
            'combined css url resolver decoded one dot with double quotes and spaces new line'
        );

        /* COMBINED CSS URL RESOLVER DECODED ONE DOT WITH DOUBLE QUOTES NEW LINE */
        $this->assertStringContainsString(
            "\"/css/deep/deeper/zero-dq-nl.gif\"",
            $content,
            'combined css url resolver decoded one dot with double quotes new line'
        );

        /* COMBINED CSS URL RESOLVER DECODED ONE DOT WITH DOUBLE QUOTES NEW LINE WITH SPACES */
        $this->assertStringContainsString(
            "\"/css/deep/deeper/zero-dq-nls.gif\"",
            $content,
            'combined css url resolver decoded one dot with double quotes new line with spaces'
        );

        /* COMBINED CSS URL RESOLVER DECODED 1 DOUBLE-DOT */
        $this->assertStringContainsString(
            ".p1 { background: url(/css/deep/one.gif); }",
            $content,
            'combined css url resolver decoded 1 double-dot'
        );

        /* COMBINED CSS URL RESOLVER DECODED 2 DOUBLE-DOT */
        $this->assertStringContainsString(
            ".p2 { background: url(/css/two.gif); }",
            $content,
            'combined css url resolver decoded 2 double-dot'
        );

        /* COMBINED CSS URL RESOLVER DECODED 2 DOUBLE-DOT SINGLE QUOTES */
        $this->assertStringContainsString(
            ".p2sq { background: url('/css/two-sq.gif'); }",
            $content,
            'combined css url resolver decoded 2 double-dot single quotes'
        );

        /* COMBINED CSS URL RESOLVER DECODED 2 DOUBLE-DOT DOUBLE QUOTES */
        $this->assertStringContainsString(
            ".p2dq { background: url(\"/css/two-dq.gif\"); }",
            $content,
            'combined css url resolver decoded 2 double-dot double quotes'
        );

        /* COMBINED CSS URL RESOLVER SHOULD NOT TOUCH ABSOLUTE PATH */
        $this->assertStringContainsString(
            ".p2abs { background: url(/foo/bar/../../two-abs.gif); }",
            $content,
            'combined css url resolver should not touch absolute path'
        );

        /* COMBINED CSS URL RESOLVER SHOULD NOT TOUCH ABSOLUTE PATH ON NEW LINE */
        $this->assertStringContainsString(
            "\n  /foo/bar/../../two-abs-ln.gif\n",
            $content,
            'combined css url resolver should not touch absolute path on new line'
        );

        /* COMBINED CSS URL RESOLVER DECODED 3 DOUBLE-DOT */
        $this->assertStringContainsString(
            ".p3 { background: url(/three.gif); }",
            $content,
            'combined css url resolver decoded 3 double-dot'
        );

        /* COMBINED CSS URL RESOLVER DECODED 4 DOUBLE-DOT WHEN ONLY 3 LEVELS AVAILABLE*/
        $this->assertStringContainsString(
            ".p4 { background: url(/four.gif); }",
            $content,
            'combined css url resolver decoded 4 double-dot when only 3 levels available'
        );

        /* COMBINED CSS URL RESOLVER MODIFIED AN ARBITRARY VALUE */
        $this->assertStringContainsString(
            ".weird { content: \"./keepme.gif\"; }",
            $content,
            'combined css url resolver modified an arbitrary value'
        );
    }

    /**
     * Setup new backend
     *
     * @param Requirements_Backend $backend
     */
    protected function setupRequirements($backend)
    {
        // Flush requirements
        $backend->clear();
        $backend->clearCombinedFiles();
        $backend->setCombinedFilesFolder('_combinedfiles');
        $backend->setCombinedFilesEnabled(true);
        Requirements::flush();
    }

    /**
     * Setup combined and non-combined js with the backend
     *
     * @param Requirements_Backend $backend
     */
    protected function setupCombinedRequirements($backend)
    {
        $this->setupRequirements($backend);

        // require files normally (e.g. called from a FormField instance)
        $backend->javascript('javascript/RequirementsTest_a.js');
        $backend->javascript('javascript/RequirementsTest_b.js');
        $backend->javascript('javascript/RequirementsTest_c.js');

        // Public resources may or may not be specified with `public/` prefix
        $backend->javascript('javascript/RequirementsTest_d.js');
        $backend->javascript('public/javascript/RequirementsTest_e.js');

        // require two of those files as combined includes
        $backend->combineFiles(
            'RequirementsTest_bc.js',
            [
                'javascript/RequirementsTest_b.js',
                'javascript/RequirementsTest_c.js'
            ]
        );
    }

    /**
     * Setup combined files with the backend
     *
     * @param Requirements_Backend $backend
     */
    protected function setupCombinedNonrequiredRequirements($backend)
    {
        $this->setupRequirements($backend);

        // require files as combined includes
        $backend->combineFiles(
            'RequirementsTest_bc.js',
            [
                'javascript/RequirementsTest_b.js',
                'javascript/RequirementsTest_c.js'
            ]
        );
    }

    /**
     * @param Requirements_Backend $backend
     * @param bool                 $async
     * @param bool                 $defer
     */
    protected function setupCombinedRequirementsJavascriptAsyncDefer($backend, $async, $defer)
    {
        $this->setupRequirements($backend);

        // require files normally (e.g. called from a FormField instance)
        $backend->javascript('javascript/RequirementsTest_a.js');
        $backend->javascript('javascript/RequirementsTest_b.js');
        $backend->javascript('javascript/RequirementsTest_c.js');

        // require two of those files as combined includes
        $backend->combineFiles(
            'RequirementsTest_bc.js',
            [
                'javascript/RequirementsTest_b.js',
                'javascript/RequirementsTest_c.js'
            ],
            [
                'async' => $async,
                'defer' => $defer,
            ]
        );
    }

    public function testCustomType()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);

        // require files normally (e.g. called from a FormField instance)
        $backend->javascript(
            'javascript/RequirementsTest_a.js',
            [ 'type' => 'application/json' ]
        );
        $backend->javascript('javascript/RequirementsTest_b.js');
        $result = $backend->includeInHTML(self::$html_template);
        $this->assertMatchesRegularExpression(
            '#<script type="application/json" src=".*/javascript/RequirementsTest_a.js#',
            $result
        );
        $this->assertMatchesRegularExpression(
            '#<script type="application/javascript" src=".*/javascript/RequirementsTest_b.js#',
            $result
        );
    }

    public function testCombinedJavascript()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $backend->setCombinedFilesEnabled(true);
        $this->setupCombinedRequirements($backend);

        $combinedFileName = '/_combinedfiles/RequirementsTest_bc-2a55d56.js';
        $combinedFilePath = TestAssetStore::base_path() . $combinedFileName;

        $html = $backend->includeInHTML(self::$html_template);

        /* COMBINED JAVASCRIPT FILE IS INCLUDED IN HTML HEADER */
        $this->assertMatchesRegularExpression(
            '/src=".*' . preg_quote($combinedFileName ?? '', '/') . '/',
            $html,
            'combined javascript file is included in html header'
        );

        /* COMBINED JAVASCRIPT FILE EXISTS */
        $this->assertTrue(
            file_exists($combinedFilePath ?? ''),
            'combined javascript file exists'
        );

        /* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
        $this->assertStringContainsString(
            "alert('b')",
            file_get_contents($combinedFilePath ?? ''),
            'combined javascript has correct content'
        );
        $this->assertStringContainsString(
            "alert('c')",
            file_get_contents($combinedFilePath ?? ''),
            'combined javascript has correct content'
        );

        /* COMBINED FILES ARE NOT INCLUDED TWICE */
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_b\.js/',
            $html,
            'combined files are not included twice'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_c\.js/',
            $html,
            'combined files are not included twice'
        );

        /* NORMAL REQUIREMENTS ARE STILL INCLUDED */
        $this->assertMatchesRegularExpression(
            '/src=".*\/RequirementsTest_a\.js/',
            $html,
            'normal requirements are still included'
        );

        // Then do it again, this time not requiring the files beforehand
        unlink($combinedFilePath ?? '');
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupCombinedNonrequiredRequirements($backend);
        $html = $backend->includeInHTML(self::$html_template);

        /* COMBINED JAVASCRIPT FILE IS INCLUDED IN HTML HEADER */
        $this->assertMatchesRegularExpression(
            '/src=".*' . preg_quote($combinedFileName ?? '', '/') . '/',
            $html,
            'combined javascript file is included in html header'
        );

        /* COMBINED JAVASCRIPT FILE EXISTS */
        $this->assertTrue(
            file_exists($combinedFilePath ?? ''),
            'combined javascript file exists'
        );

        /* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
        $this->assertStringContainsString(
            "alert('b')",
            file_get_contents($combinedFilePath ?? ''),
            'combined javascript has correct content'
        );
        $this->assertStringContainsString(
            "alert('c')",
            file_get_contents($combinedFilePath ?? ''),
            'combined javascript has correct content'
        );

        /* COMBINED FILES ARE NOT INCLUDED TWICE */
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_b\.js/',
            $html,
            'combined files are not included twice'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_c\.js/',
            $html,
            'combined files are not included twice'
        );
    }

    public function testCombinedJavascriptAsyncDefer()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);

        $this->setupCombinedRequirementsJavascriptAsyncDefer($backend, true, false);

        $combinedFileName = '/_combinedfiles/RequirementsTest_bc-2a55d56.js';
        $combinedFilePath = TestAssetStore::base_path() . $combinedFileName;

        $html = $backend->includeInHTML(self::$html_template);

        /* ASYNC IS INCLUDED IN SCRIPT TAG */
        $this->assertMatchesRegularExpression(
            '/src=".*' . preg_quote($combinedFileName ?? '', '/') . '" async/',
            $html,
            'async is included in script tag'
        );

        /* DEFER IS NOT INCLUDED IN SCRIPT TAG */
        $this->assertStringNotContainsString('defer', $html, 'defer is not included');

        /* COMBINED JAVASCRIPT FILE EXISTS */
        clearstatcache(); // needed to get accurate file_exists() results
        $this->assertFileExists(
            $combinedFilePath,
            'combined javascript file exists'
        );

        /* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
        $this->assertStringContainsString(
            "alert('b')",
            file_get_contents($combinedFilePath ?? ''),
            'combined javascript has correct content'
        );
        $this->assertStringContainsString(
            "alert('c')",
            file_get_contents($combinedFilePath ?? ''),
            'combined javascript has correct content'
        );

        /* COMBINED FILES ARE NOT INCLUDED TWICE */
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_b\.js/',
            $html,
            'combined files are not included twice'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_c\.js/',
            $html,
            'combined files are not included twice'
        );

        /* NORMAL REQUIREMENTS ARE STILL INCLUDED */
        $this->assertMatchesRegularExpression(
            '/src=".*\/RequirementsTest_a\.js/',
            $html,
            'normal requirements are still included'
        );

        /* NORMAL REQUIREMENTS DON'T HAVE ASYNC/DEFER */
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async/',
            $html,
            'normal requirements don\'t have async'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" defer/',
            $html,
            'normal requirements don\'t have defer'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async defer/',
            $html,
            'normal requirements don\'t have async/defer'
        );

        // setup again for testing defer
        unlink($combinedFilePath ?? '');
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);

        $this->setupCombinedRequirementsJavascriptAsyncDefer($backend, false, true);

        $html = $backend->includeInHTML(self::$html_template);

        /* DEFER IS INCLUDED IN SCRIPT TAG */
        $this->assertMatchesRegularExpression(
            '/src=".*' . preg_quote($combinedFileName ?? '', '/') . '" defer/',
            $html,
            'defer is included in script tag'
        );

        /* ASYNC IS NOT INCLUDED IN SCRIPT TAG */
        $this->assertStringNotContainsString('async', $html, 'async is not included');

        /* COMBINED JAVASCRIPT FILE EXISTS */
        clearstatcache(); // needed to get accurate file_exists() results
        $this->assertFileExists(
            $combinedFilePath,
            'combined javascript file exists'
        );

        /* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
        $this->assertStringContainsString(
            "alert('b')",
            file_get_contents($combinedFilePath ?? ''),
            'combined javascript has correct content'
        );
        $this->assertStringContainsString(
            "alert('c')",
            file_get_contents($combinedFilePath ?? ''),
            'combined javascript has correct content'
        );

        /* COMBINED FILES ARE NOT INCLUDED TWICE */
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_b\.js/',
            $html,
            'combined files are not included twice'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_c\.js/',
            $html,
            'combined files are not included twice'
        );

        /* NORMAL REQUIREMENTS ARE STILL INCLUDED */
        $this->assertMatchesRegularExpression(
            '/src=".*\/RequirementsTest_a\.js/',
            $html,
            'normal requirements are still included'
        );

        /* NORMAL REQUIREMENTS DON'T HAVE ASYNC/DEFER */
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async/',
            $html,
            'normal requirements don\'t have async'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" defer/',
            $html,
            'normal requirements don\'t have defer'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async defer/',
            $html,
            'normal requirements don\'t have async/defer'
        );

        // setup again for testing async and defer
        unlink($combinedFilePath ?? '');
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);

        $this->setupCombinedRequirementsJavascriptAsyncDefer($backend, true, true);

        $html = $backend->includeInHTML(self::$html_template);

        /* ASYNC/DEFER IS INCLUDED IN SCRIPT TAG */
        $this->assertMatchesRegularExpression(
            '/src=".*' . preg_quote($combinedFileName ?? '', '/') . '" async="async" defer="defer"/',
            $html,
            'async and defer are included in script tag'
        );

        /* COMBINED JAVASCRIPT FILE EXISTS */
        clearstatcache(); // needed to get accurate file_exists() results
        $this->assertFileExists(
            $combinedFilePath,
            'combined javascript file exists'
        );

        /* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
        $this->assertStringContainsString(
            "alert('b')",
            file_get_contents($combinedFilePath ?? ''),
            'combined javascript has correct content'
        );
        $this->assertStringContainsString(
            "alert('c')",
            file_get_contents($combinedFilePath ?? ''),
            'combined javascript has correct content'
        );

        /* COMBINED FILES ARE NOT INCLUDED TWICE */
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_b\.js/',
            $html,
            'combined files are not included twice'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_c\.js/',
            $html,
            'combined files are not included twice'
        );

        /* NORMAL REQUIREMENTS ARE STILL INCLUDED */
        $this->assertMatchesRegularExpression(
            '/src=".*\/RequirementsTest_a\.js/',
            $html,
            'normal requirements are still included'
        );

        /* NORMAL REQUIREMENTS DON'T HAVE ASYNC/DEFER */
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async/',
            $html,
            'normal requirements don\'t have async'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" defer/',
            $html,
            'normal requirements don\'t have defer'
        );
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async defer/',
            $html,
            'normal requirements don\'t have async/defer'
        );

        unlink($combinedFilePath ?? '');
    }

    public function testCombinedCss()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);

        $backend->combineFiles(
            'print.css',
            [
                'css/RequirementsTest_print_a.css',
                'css/RequirementsTest_print_b.css',
                'css/RequirementsTest_print_d.css',
                'public/css/RequirementsTest_print_e.css',
            ],
            [
                'media' => 'print'
            ]
        );

        $html = $backend->includeInHTML(self::$html_template);

        $this->assertMatchesRegularExpression(
            '/href=".*\/print\-69ce614\.css/',
            $html,
            'Print stylesheets have been combined.'
        );
        $this->assertMatchesRegularExpression(
            '/media="print/',
            $html,
            'Combined print stylesheet retains the media parameter'
        );

        // Test that combining a file multiple times doesn't trigger an error
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->combineFiles(
            'style.css',
            [
                'css/RequirementsTest_b.css',
                'css/RequirementsTest_c.css',
                'css/RequirementsTest_d.css',
                'public/css/RequirementsTest_e.css',
            ]
        );
        $backend->combineFiles(
            'style.css',
            [
                'css/RequirementsTest_b.css',
                'css/RequirementsTest_c.css',
                'css/RequirementsTest_d.css',
                'public/css/RequirementsTest_e.css',
            ]
        );

        $html = $backend->includeInHTML(self::$html_template);
        $this->assertMatchesRegularExpression(
            '/href=".*\/style\-8011538\.css/',
            $html,
            'Stylesheets have been combined.'
        );
    }

    public function testBlockedCombinedJavascript()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupCombinedRequirements($backend);
        $combinedFileName = '/_combinedfiles/RequirementsTest_bc-2a55d56.js';
        $combinedFilePath = TestAssetStore::base_path() . $combinedFileName;

        /* BLOCKED COMBINED FILES ARE NOT INCLUDED */
        $backend->block('RequirementsTest_bc.js');

        clearstatcache(); // needed to get accurate file_exists() results
        $html = $backend->includeInHTML(self::$html_template);
        $this->assertFileDoesNotExist($combinedFilePath);
        $this->assertDoesNotMatchRegularExpression(
            '/src=".*\/RequirementsTest_bc\.js/',
            $html,
            'blocked combined files are not included'
        );
        $backend->unblock('RequirementsTest_bc.js');

        /* BLOCKED UNCOMBINED FILES ARE NOT INCLUDED */
        $this->setupCombinedRequirements($backend);
        $backend->block('javascript/RequirementsTest_b.js');
        $combinedFileName2 = '/_combinedfiles/RequirementsTest_bc-3748f67.js'; // SHA1 without file b included
        $combinedFilePath2 = TestAssetStore::base_path() . $combinedFileName2;
        clearstatcache(); // needed to get accurate file_exists() results
        $backend->includeInHTML(self::$html_template);
        $this->assertFileExists($combinedFilePath2);
        $this->assertStringNotContainsString(
            "alert('b')",
            file_get_contents($combinedFilePath2 ?? ''),
            'blocked uncombined files are not included'
        );
        $backend->unblock('javascript/RequirementsTest_b.js');

        /* A SINGLE FILE CAN'T BE INCLUDED IN TWO COMBINED FILES */
        $this->setupCombinedRequirements($backend);
        clearstatcache(); // needed to get accurate file_exists() results

        // Exception generated from including invalid file
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            "Requirements_Backend::combine_files(): Already included file(s) %s in combined file '%s'",
            'javascript/RequirementsTest_c.js',
            'RequirementsTest_bc.js'
        ));
        $backend->combineFiles(
            'RequirementsTest_ac.js',
            [
                'javascript/RequirementsTest_a.js',
                'javascript/RequirementsTest_c.js'
            ]
        );
    }

    public function testArgsInUrls()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);

        $generator = Injector::inst()->get(ResourceURLGenerator::class);
        $generator->setNonceStyle('mtime');

        $backend->javascript('javascript/RequirementsTest_a.js?test=1&test=2&test=3');
        $backend->css('css/RequirementsTest_a.css?test=1&test=2&test=3');
        $html = $backend->includeInHTML(self::$html_template);

        /* Javascript has correct path */
        $this->assertMatchesRegularExpression(
            '/src=".*\/RequirementsTest_a\.js\?test=1&amp;test=2&amp;test=3&amp;m=\d\d+/',
            $html,
            'javascript has correct path'
        );

        /* CSS has correct path */
        $this->assertMatchesRegularExpression(
            '/href=".*\/RequirementsTest_a\.css\?test=1&amp;test=2&amp;test=3&amp;m=\d\d+/',
            $html,
            'css has correct path'
        );
    }

    public function testRequirementsBackend()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->javascript('a.js');

        $this->assertCount(
            1,
            $backend->getJavascript(),
            "There should be only 1 file included in required javascript."
        );
        $this->assertArrayHasKey(
            'a.js',
            $backend->getJavascript(),
            "a.js should be included in required javascript."
        );

        $backend->javascript('b.js');
        $this->assertCount(
            2,
            $backend->getJavascript(),
            "There should be 2 files included in required javascript."
        );

        $backend->block('a.js');
        $this->assertCount(
            1,
            $backend->getJavascript(),
            "There should be only 1 file included in required javascript."
        );
        $this->assertArrayNotHasKey(
            'a.js',
            $backend->getJavascript(),
            "a.js should not be included in required javascript after it has been blocked."
        );
        $this->assertArrayHasKey(
            'b.js',
            $backend->getJavascript(),
            "b.js should be included in required javascript."
        );

        $backend->css('a.css');
        $this->assertCount(
            1,
            $backend->getCSS(),
            "There should be only 1 file included in required css."
        );
        $this->assertArrayHasKey(
            'a.css',
            $backend->getCSS(),
            "a.css should be in required css."
        );

        $backend->block('a.css');
        $this->assertCount(
            0,
            $backend->getCSS(),
            "There should be nothing in required css after file has been blocked."
        );
    }

    public function testAppendAndBlockWithModuleResourceLoader()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);

        // Note: assumes that client/styles/debug.css is "exposed"
        $backend->css('silverstripe/framework:client/styles/debug.css');
        $this->assertCount(
            1,
            $backend->getCSS(),
            'Module resource can be loaded via resources reference'
        );

        $backend->block('silverstripe/framework:client/styles/debug.css');
        $this->assertCount(
            0,
            $backend->getCSS(),
            'Module resource can be blocked via resources reference'
        );
    }

    public function testConditionalTemplateRequire()
    {
        // Set /SSViewerTest and /SSViewerTest/public as themes
        SSViewer::set_themes([
            '/',
            SSViewer::PUBLIC_THEME
        ]);
        ThemeResourceLoader::set_instance(new ThemeResourceLoader(__DIR__ . '/SSViewerTest'));

        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $holder = Requirements::backend();
        Requirements::set_backend($backend);
        $data = new ArrayData([
            'FailTest' => true,
        ]);

        $data->renderWith('RequirementsTest_Conditionals');
        $this->assertFileIncluded($backend, 'css', 'css/RequirementsTest_a.css');
        $this->assertFileIncluded(
            $backend,
            'js',
            [
                'javascript/RequirementsTest_b.js',
                'javascript/RequirementsTest_c.js'
            ]
        );
        $this->assertFileNotIncluded($backend, 'js', 'javascript/RequirementsTest_a.js');
        $this->assertFileNotIncluded(
            $backend,
            'css',
            [
                'css/RequirementsTest_b.css',
                'css/RequirementsTest_c.css'
            ]
        );
        $backend->clear();
        $data = new ArrayData(
            [
            'FailTest' => false,
            ]
        );
        $data->renderWith('RequirementsTest_Conditionals');
        $this->assertFileNotIncluded($backend, 'css', 'css/RequirementsTest_a.css');
        $this->assertFileNotIncluded(
            $backend,
            'js',
            [
                'javascript/RequirementsTest_b.js',
                'javascript/RequirementsTest_c.js',
            ]
        );
        $this->assertFileIncluded($backend, 'js', 'javascript/RequirementsTest_a.js');
        $this->assertFileIncluded(
            $backend,
            'css',
            [
                'css/RequirementsTest_b.css',
                'css/RequirementsTest_c.css',
            ]
        );
        Requirements::set_backend($holder);
    }

    public function testJsWriteToBody()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->javascript('http://www.mydomain.com/test.js');

        // Test matching with HTML5 <header> tags as well
        $template = '<html><head></head><body><header>My header</header><p>Body</p></body></html>';

        $backend->setWriteJavascriptToBody(false);
        $html = $backend->includeInHTML($template);
        $this->assertStringContainsString('<head><script', $html);

        $backend->setWriteJavascriptToBody(true);
        $html = $backend->includeInHTML($template);
        $this->assertStringNotContainsString('<head><script', $html);
        $this->assertStringContainsString("</script>\n</body>", $html);
    }

    public function testIncludedJsIsNotCommentedOut()
    {
        $template = '<html><head></head><body><!--<script>alert("commented out");</script>--></body></html>';
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->javascript('javascript/RequirementsTest_a.js');
        $html = $backend->includeInHTML($template);
        //wiping out commented-out html
        $html = preg_replace('/<!--(.*)-->/Uis', '', $html ?? '');
        $this->assertStringContainsString("RequirementsTest_a.js", $html);
    }

    public function testCommentedOutScriptTagIsIgnored()
    {
        /// Disable nonce
        $urlGenerator = new SimpleResourceURLGenerator();
        Injector::inst()->registerService($urlGenerator, ResourceURLGenerator::class);

        $template = '<html><head></head><body><!--<script>alert("commented out");</script>-->'
            . '<h1>more content</h1></body></html>';
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);

        $src = 'javascript/RequirementsTest_a.js';
        $backend->javascript($src);
        $html = $backend->includeInHTML($template);
        $urlSrc = $urlGenerator->urlForResource($src);
        $this->assertEquals(
            '<html><head></head><body><!--<script>alert("commented out");</script>-->'
            . '<h1>more content</h1><script type="application/javascript" src="' . $urlSrc
            . "\"></script>\n</body></html>",
            $html
        );
    }

    public function testForceJsToBottom()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->javascript('http://www.mydomain.com/test.js');
        $backend->customScript(
            <<<'EOS'
var globalvar = {
	pattern: '\\$custom\\1'
};
EOS
        );

        // Test matching with HTML5 <header> tags as well
        $template = '<html><head></head><body><header>My header</header><p>Body<script></script></p></body></html>';

        // The expected outputs
        $expectedScripts = "<script type=\"application/javascript\" src=\"http://www.mydomain.com/test.js\"></script>\n"
            . "<script type=\"application/javascript\">//<![CDATA[\n"
            . "var globalvar = {\n\tpattern: '\\\\\$custom\\\\1'\n};\n"
            . "//]]></script>\n";
        $JsInHead = "<html><head>$expectedScripts</head><body><header>My header</header><p>Body<script></script></p></body></html>";
        $JsInBody = "<html><head></head><body><header>My header</header><p>Body$expectedScripts<script></script></p></body></html>";
        $JsAtEnd  = "<html><head></head><body><header>My header</header><p>Body<script></script></p>$expectedScripts</body></html>";


        // Test if the script is before the head tag, not before the body.
        // Expected: $JsInHead
        $backend->setWriteJavascriptToBody(false);
        $backend->setForceJSToBottom(false);
        $html = $backend->includeInHTML($template);
        $this->assertNotEquals($JsInBody, $html);
        $this->assertNotEquals($JsAtEnd, $html);
        $this->assertEquals($JsInHead, $html);

        // Test if the script is before the first <script> tag, not before the body.
        // Expected: $JsInBody
        $backend->setWriteJavascriptToBody(true);
        $backend->setForceJSToBottom(false);
        $html = $backend->includeInHTML($template);
        $this->assertNotEquals($JsAtEnd, $html);
        $this->assertEquals($JsInBody, $html);

        // Test if the script is placed just before the closing bodytag, with write-to-body false.
        // Expected: $JsAtEnd
        $backend->setWriteJavascriptToBody(false);
        $backend->setForceJSToBottom(true);
        $html = $backend->includeInHTML($template);
        $this->assertNotEquals($JsInHead, $html);
        $this->assertNotEquals($JsInBody, $html);
        $this->assertEquals($JsAtEnd, $html);

        // Test if the script is placed just before the closing bodytag, with write-to-body true.
        // Expected: $JsAtEnd
        $backend->setWriteJavascriptToBody(true);
        $backend->setForceJSToBottom(true);
        $html = $backend->includeInHTML($template);
        $this->assertNotEquals($JsInHead, $html);
        $this->assertNotEquals($JsInBody, $html);
        $this->assertEquals($JsAtEnd, $html);
    }

    public function testSuffix()
    {
        /// Disable nonce
        $urlGenerator = new SimpleResourceURLGenerator();
        Injector::inst()->registerService($urlGenerator, ResourceURLGenerator::class);

        $template = '<html><head></head><body><header>My header</header><p>Body</p></body></html>';

        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);

        $backend->javascript('javascript/RequirementsTest_a.js');
        $backend->javascript('javascript/RequirementsTest_b.js?foo=bar&bla=blubb');
        $backend->css('css/RequirementsTest_a.css');
        $backend->css('css/RequirementsTest_b.css?foo=bar&bla=blubb');

        $urlGenerator->setNonceStyle('mtime');
        $html = $backend->includeInHTML($template);
        $this->assertMatchesRegularExpression('/RequirementsTest_a\.js\?m=[\d]*"/', $html);
        $this->assertMatchesRegularExpression('/RequirementsTest_b\.js\?foo=bar&amp;bla=blubb&amp;m=[\d]*"/', $html);
        $this->assertMatchesRegularExpression('/RequirementsTest_a\.css\?m=[\d]*"/', $html);
        $this->assertMatchesRegularExpression('/RequirementsTest_b\.css\?foo=bar&amp;bla=blubb&amp;m=[\d]*"/', $html);

        $urlGenerator->setNonceStyle(null);
        $html = $backend->includeInHTML($template);
        $this->assertStringNotContainsString('RequirementsTest_a.js=', $html);
        $this->assertDoesNotMatchRegularExpression('/RequirementsTest_a\.js\?m=[\d]*"/', $html);
        $this->assertDoesNotMatchRegularExpression('/RequirementsTest_b\.js\?foo=bar&amp;bla=blubb&amp;m=[\d]*"/', $html);
        $this->assertDoesNotMatchRegularExpression('/RequirementsTest_a\.css\?m=[\d]*"/', $html);
        $this->assertDoesNotMatchRegularExpression('/RequirementsTest_b\.css\?foo=bar&amp;bla=blubb&amp;m=[\d]*"/', $html);
    }

    /**
     * Tests that provided files work
     */
    public function testProvidedFiles()
    {
        /** @var Requirements_Backend $backend */
        $template = '<html><head></head><body><header>My header</header><p>Body</p></body></html>';

        // Test that provided files block subsequent files
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->javascript('javascript/RequirementsTest_a.js');
        $backend->javascript(
            'javascript/RequirementsTest_b.js',
            [
            'provides' => [
                    'javascript/RequirementsTest_a.js',
                    'javascript/RequirementsTest_c.js',
                ],
            ]
        );
        $backend->javascript('javascript/RequirementsTest_c.js');
        // Note that _a.js isn't considered provided because it was included
        // before it was marked as provided
        $this->assertEquals(
            [
                'javascript/RequirementsTest_c.js' => 'javascript/RequirementsTest_c.js'
            ],
            $backend->getProvidedScripts()
        );
        $html = $backend->includeInHTML($template);
        $this->assertMatchesRegularExpression('/src=".*\/RequirementsTest_a\.js/', $html);
        $this->assertMatchesRegularExpression('/src=".*\/RequirementsTest_b\.js/', $html);
        $this->assertDoesNotMatchRegularExpression('/src=".*\/RequirementsTest_c\.js/', $html);

        // Test that provided files block subsequent combined files
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->combineFiles('combined_a.js', ['javascript/RequirementsTest_a.js']);
        $backend->javascript(
            'javascript/RequirementsTest_b.js',
            [
            'provides' => [
                'javascript/RequirementsTest_a.js',
                'javascript/RequirementsTest_c.js'
            ]
            ]
        );
        $backend->combineFiles('combined_c.js', ['javascript/RequirementsTest_c.js']);
        $this->assertEquals(
            [
                'javascript/RequirementsTest_c.js' => 'javascript/RequirementsTest_c.js'
            ],
            $backend->getProvidedScripts()
        );
        $html = $backend->includeInHTML($template);
        $this->assertMatchesRegularExpression('/src=".*\/combined_a/', $html);
        $this->assertMatchesRegularExpression('/src=".*\/RequirementsTest_b\.js/', $html);
        $this->assertDoesNotMatchRegularExpression('/src=".*\/combined_c/', $html);
        $this->assertDoesNotMatchRegularExpression('/src=".*\/RequirementsTest_c\.js/', $html);
    }

    /**
     * Verify that the given backend includes the given files
     *
     * @param Requirements_Backend $backend
     * @param string               $type    js or css
     * @param array|string         $files   Files or list of files to check
     */
    public function assertFileIncluded($backend, $type, $files)
    {
        $includedFiles = $this->getBackendFiles($backend, $type);

        if (is_array($files)) {
            $failedMatches = [];
            foreach ($files as $file) {
                if (!array_key_exists($file, $includedFiles ?? [])) {
                    $failedMatches[] = $file;
                }
            }
            $this->assertCount(
                0,
                $failedMatches,
                "Failed asserting the $type files '"
                . implode("', '", $failedMatches)
                . "' have exact matches in the required elements:\n'"
                . implode("'\n'", array_keys($includedFiles ?? [])) . "'"
            );
        } else {
            $this->assertArrayHasKey(
                $files,
                $includedFiles,
                "Failed asserting the $type file '$files' has an exact match in the required elements:\n'"
                . implode("'\n'", array_keys($includedFiles ?? [])) . "'"
            );
        }
    }

    public function assertFileNotIncluded($backend, $type, $files)
    {
        $includedFiles = $this->getBackendFiles($backend, $type);
        if (is_array($files)) {
            $failedMatches = [];
            foreach ($files as $file) {
                if (array_key_exists($file, $includedFiles ?? [])) {
                    $failedMatches[] = $file;
                }
            }
            $this->assertCount(
                0,
                $failedMatches,
                "Failed asserting the $type files '"
                . implode("', '", $failedMatches)
                . "' do not have exact matches in the required elements:\n'"
                . implode("'\n'", array_keys($includedFiles ?? [])) . "'"
            );
        } else {
            $this->assertArrayNotHasKey(
                $files,
                $includedFiles,
                "Failed asserting the $type file '$files' does not have an exact match in the required elements:"
                        . "\n'" . implode("'\n'", array_keys($includedFiles ?? [])) . "'"
            );
        }
    }


    /**
     * Get files of the given type from the backend
     *
     * @param  Requirements_Backend $backend
     * @param  string               $type    js or css
     * @return array
     */
    protected function getBackendFiles($backend, $type)
    {
        $type = strtolower($type ?? '');
        switch (strtolower($type ?? '')) {
            case 'css':
                return $backend->getCSS();
            case 'js':
            case 'javascript':
            case 'script':
                return $backend->getJavascript();
        }
        return [];
    }

    public function testAddI18nJavascript()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->add_i18n_javascript('i18n');

        $actual = $backend->getJavascript();

        // English and English US should always be loaded no matter what
        $this->assertArrayHasKey('i18n/en.js', $actual);
        $this->assertArrayHasKey('i18n/en_US.js', $actual);
        $this->assertArrayHasKey('i18n/en-us.js', $actual);
    }

    public function testAddI18nJavascriptWithDefaultLocale()
    {
        i18n::config()->set('default_locale', 'fr_CA');

        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->add_i18n_javascript('i18n');

        $actual = $backend->getJavascript();


        $this->assertArrayHasKey('i18n/en.js', $actual);
        $this->assertArrayHasKey('i18n/en_US.js', $actual);
        $this->assertArrayHasKey('i18n/en-us.js', $actual);
        // Default locale should be loaded
        $this->assertArrayHasKey('i18n/fr.js', $actual);
        $this->assertArrayHasKey('i18n/fr_CA.js', $actual);
        $this->assertArrayHasKey('i18n/fr-ca.js', $actual);
    }

    public function testAddI18nJavascriptWithMemberLocale()
    {
        i18n::set_locale('en_GB');

        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->add_i18n_javascript('i18n');

        $actual = $backend->getJavascript();

        // The current member's Locale as defined by i18n::get_locale should be loaded
        $this->assertArrayHasKey('i18n/en.js', $actual);
        $this->assertArrayHasKey('i18n/en_US.js', $actual);
        $this->assertArrayHasKey('i18n/en-us.js', $actual);
        $this->assertArrayHasKey('i18n/en-gb.js', $actual);
        $this->assertArrayHasKey('i18n/en_GB.js', $actual);
    }

    public function testAddI18nJavascriptWithMissingLocale()
    {
        i18n::set_locale('fr_BE');

        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->add_i18n_javascript('i18n');

        $actual = $backend->getJavascript();

        // We don't have a file for French Belgium. Regular french should be loaded anyway.
        $this->assertArrayHasKey('i18n/en.js', $actual);
        $this->assertArrayHasKey('i18n/en_US.js', $actual);
        $this->assertArrayHasKey('i18n/en-us.js', $actual);
        $this->assertArrayHasKey('i18n/fr.js', $actual);
    }

    public function testSriAttributes()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);

        $backend->javascript('javascript/RequirementsTest_a.js', ['integrity' => 'abc', 'crossorigin' => 'use-credentials']);
        // Tests attribute appending AND lowercase string conversion
        $backend->customScriptWithAttributes("//TEST", ['type' => 'module', 'crossorigin' => 'Anonymous']);
        $backend->css('css/RequirementsTest_a.css', null, ['integrity' => 'def', 'crossorigin' => 'anonymous']);
        $html = $backend->includeInHTML(self::$html_template);

        /* Javascript has correct attributes */
        $this->assertMatchesRegularExpression(
            '#<script type="application/javascript" src=".*/javascript/RequirementsTest_a.js.*" integrity="abc" crossorigin="use-credentials"#',
            $html,
            'javascript has correct sri attributes'
        );

        /* Custom Javascript has correct attribute */
        $this->assertMatchesRegularExpression(
            '#<script type="module" crossorigin="anonymous"#',
            $html,
            'custom javascript has correct sri attributes'
        );
        /* CSS has correct attributes */
        $this->assertMatchesRegularExpression(
            '#<link .*href=".*/RequirementsTest_a\.css.*" integrity="def" crossorigin="anonymous"#',
            $html,
            'css has correct sri attributes'
        );
    }

    public function testUniquenessID()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);

        // Create requirements that are to be overwritten
        $backend->customScript("Do Not Display", 42);
        $backend->customCSS("Do Not Display", 42);
        $backend->insertHeadTags("<span>Do Not Display</span>", 42);

        // Override
        $backend->customScriptWithAttributes("Override", ['type' => 'module', 'crossorigin' => 'use-credentials'], 42);
        $backend->customCSS("Override", 42);
        $backend->insertHeadTags("<span>Override</span>", 42);

        $html = $backend->includeInHTML(self::$html_template);

        /* customScript is overwritten by customScriptWithAttributes */
        $this->assertMatchesRegularExpression(
            "#<script type=\"module\" crossorigin=\"use-credentials\">//<!\[CDATA\[\s*Override\s*//\]\]></script>#s",
            $html,
            'customScript is displaying latest write'
        );

        $this->assertDoesNotMatchRegularExpression(
            "#<script type=\"application/javascript\">//<!\[CDATA\[\s*Do Not Display\s*//\]\]></script>#s",
            $html,
            'customScript is correctly not displaying original write'
        );

        /* customCSS is overwritten */
        $this->assertMatchesRegularExpression(
            "#<style type=\"text/css\">\s*Override\s*</style>#",
            $html,
            'customCSS is displaying latest write'
        );

        $this->assertDoesNotMatchRegularExpression(
            "#<style type=\"text/css\">\s*Do Not Display\s*</style>#",
            $html,
            'customCSS is correctly not displaying original write'
        );

        /* Head Tags is overwritten */
        $this->assertMatchesRegularExpression(
            '#<span>Override</span>#',
            $html,
            'Head Tag is displaying latest write'
        );

        $this->assertDoesNotMatchRegularExpression(
            '#<span>Do Not Display</span>#',
            $html,
            'Head Tag is correctly not displaying original write'
        );
    }
}
