<?php

namespace SilverStripe\View\Tests;

use InvalidArgumentException;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Dev\SapphireTest;
use SilverStripe\View\Requirements;
use SilverStripe\View\ArrayData;
use SilverStripe\Assets\Tests\Storage\AssetStoreTest\TestAssetStore;
use SilverStripe\View\Requirements_Backend;
use SilverStripe\Core\Manifest\ResourceURLGenerator;
use SilverStripe\Control\SimpleResourceURLGenerator;

/**
 * @todo Test that order of combine_files() is correct
 * @todo Figure out how to clear the modified state of Requirements class - might affect other tests.
 * @skipUpgrade
 */
class RequirementsTest extends SapphireTest
{

    static $html_template = '<html><head></head><body></body></html>';

    protected function setUp()
    {
        parent::setUp();
        TestAssetStore::activate('RequirementsTest'); // Set backend root to /RequirementsTest
    }

    protected function tearDown()
    {
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

        $this->assertContains('http://www.mydomain.com/test.js', $html, 'Load external javascript URL');
        $this->assertContains('https://www.mysecuredomain.com/test.js', $html, 'Load external secure javascript URL');
        $this->assertContains('//scheme-relative.example.com/test.js', $html, 'Load external scheme-relative JS');
        $this->assertContains('http://www.mydomain.com:3000/test.js', $html, 'Load external with port');
        $this->assertContains('http://www.mydomain.com/test.css', $html, 'Load external CSS URL');
        $this->assertContains('https://www.mysecuredomain.com/test.css', $html, 'Load external secure CSS URL');
        $this->assertContains('//scheme-relative.example.com/test.css', $html, 'Load scheme-relative CSS URL');
        $this->assertContains('http://www.mydomain.com:3000/test.css', $html, 'Load external with port');
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
        $backend->setMinifyCombinedFiles(false);
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
        $basePath = $this->getThemeRoot();
        $this->setupRequirements($backend);

        // require files normally (e.g. called from a FormField instance)
        $backend->javascript($basePath . '/javascript/RequirementsTest_a.js');
        $backend->javascript($basePath . '/javascript/RequirementsTest_b.js');
        $backend->javascript($basePath . '/javascript/RequirementsTest_c.js');

        // require two of those files as combined includes
        $backend->combineFiles(
            'RequirementsTest_bc.js',
            array(
                $basePath . '/javascript/RequirementsTest_b.js',
                $basePath . '/javascript/RequirementsTest_c.js'
            )
        );
    }

    /**
     * Setup combined files with the backend
     *
     * @param Requirements_Backend $backend
     */
    protected function setupCombinedNonrequiredRequirements($backend)
    {
        $basePath = $this->getThemeRoot();
        $this->setupRequirements($backend);

        // require files as combined includes
        $backend->combineFiles(
            'RequirementsTest_bc.js',
            array(
                $basePath . '/javascript/RequirementsTest_b.js',
                $basePath . '/javascript/RequirementsTest_c.js'
            )
        );
    }

    /**
     * @param Requirements_Backend $backend
     * @param bool                 $async
     * @param bool                 $defer
     */
    protected function setupCombinedRequirementsJavascriptAsyncDefer($backend, $async, $defer)
    {
        $basePath = $this->getThemeRoot();
        $this->setupRequirements($backend);

        // require files normally (e.g. called from a FormField instance)
        $backend->javascript($basePath . '/javascript/RequirementsTest_a.js');
        $backend->javascript($basePath . '/javascript/RequirementsTest_b.js');
        $backend->javascript($basePath . '/javascript/RequirementsTest_c.js');

        // require two of those files as combined includes
        $backend->combineFiles(
            'RequirementsTest_bc.js',
            array(
                $basePath . '/javascript/RequirementsTest_b.js',
                $basePath . '/javascript/RequirementsTest_c.js'
            ),
            array(
                'async' => $async,
                'defer' => $defer,
            )
        );
    }

    public function testCustomType()
    {
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $basePath = $this->getThemeRoot();
        $this->setupRequirements($backend);

        // require files normally (e.g. called from a FormField instance)
        $backend->javascript(
            $basePath . '/javascript/RequirementsTest_a.js',
            [
            'type' => 'application/json'
            ]
        );
        $backend->javascript($basePath . '/javascript/RequirementsTest_b.js');
        $result = $backend->includeInHTML(self::$html_template);
        $this->assertRegExp(
            '#<script type="application/json" src=".*/javascript/RequirementsTest_a.js#',
            $result
        );
        $this->assertRegExp(
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
        $this->assertRegExp(
            '/src=".*' . preg_quote($combinedFileName, '/') . '/',
            $html,
            'combined javascript file is included in html header'
        );

        /* COMBINED JAVASCRIPT FILE EXISTS */
        $this->assertTrue(
            file_exists($combinedFilePath),
            'combined javascript file exists'
        );

        /* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
        $this->assertContains(
            "alert('b')",
            file_get_contents($combinedFilePath),
            'combined javascript has correct content'
        );
        $this->assertContains(
            "alert('c')",
            file_get_contents($combinedFilePath),
            'combined javascript has correct content'
        );

        /* COMBINED FILES ARE NOT INCLUDED TWICE */
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_b\.js/',
            $html,
            'combined files are not included twice'
        );
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_c\.js/',
            $html,
            'combined files are not included twice'
        );

        /* NORMAL REQUIREMENTS ARE STILL INCLUDED */
        $this->assertRegExp(
            '/src=".*\/RequirementsTest_a\.js/',
            $html,
            'normal requirements are still included'
        );

        // Then do it again, this time not requiring the files beforehand
        unlink($combinedFilePath);
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupCombinedNonrequiredRequirements($backend);
        $html = $backend->includeInHTML(self::$html_template);

        /* COMBINED JAVASCRIPT FILE IS INCLUDED IN HTML HEADER */
        $this->assertRegExp(
            '/src=".*' . preg_quote($combinedFileName, '/') . '/',
            $html,
            'combined javascript file is included in html header'
        );

        /* COMBINED JAVASCRIPT FILE EXISTS */
        $this->assertTrue(
            file_exists($combinedFilePath),
            'combined javascript file exists'
        );

        /* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
        $this->assertContains(
            "alert('b')",
            file_get_contents($combinedFilePath),
            'combined javascript has correct content'
        );
        $this->assertContains(
            "alert('c')",
            file_get_contents($combinedFilePath),
            'combined javascript has correct content'
        );

        /* COMBINED FILES ARE NOT INCLUDED TWICE */
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_b\.js/',
            $html,
            'combined files are not included twice'
        );
        $this->assertNotRegExp(
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

        $html = $backend->includeInHTML(false, self::$html_template);

        /* ASYNC IS INCLUDED IN SCRIPT TAG */
        $this->assertRegExp(
            '/src=".*' . preg_quote($combinedFileName, '/') . '" async/',
            $html,
            'async is included in script tag'
        );

        /* DEFER IS NOT INCLUDED IN SCRIPT TAG */
        $this->assertNotContains('defer', $html, 'defer is not included');

        /* COMBINED JAVASCRIPT FILE EXISTS */
        clearstatcache(); // needed to get accurate file_exists() results
        $this->assertFileExists(
            $combinedFilePath,
            'combined javascript file exists'
        );

        /* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
        $this->assertContains(
            "alert('b')",
            file_get_contents($combinedFilePath),
            'combined javascript has correct content'
        );
        $this->assertContains(
            "alert('c')",
            file_get_contents($combinedFilePath),
            'combined javascript has correct content'
        );

        /* COMBINED FILES ARE NOT INCLUDED TWICE */
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_b\.js/',
            $html,
            'combined files are not included twice'
        );
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_c\.js/',
            $html,
            'combined files are not included twice'
        );

        /* NORMAL REQUIREMENTS ARE STILL INCLUDED */
        $this->assertRegExp(
            '/src=".*\/RequirementsTest_a\.js/',
            $html,
            'normal requirements are still included'
        );

        /* NORMAL REQUIREMENTS DON'T HAVE ASYNC/DEFER */
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async/',
            $html,
            'normal requirements don\'t have async'
        );
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" defer/',
            $html,
            'normal requirements don\'t have defer'
        );
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async defer/',
            $html,
            'normal requirements don\'t have async/defer'
        );

        // setup again for testing defer
        unlink($combinedFilePath);
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);

        $this->setupCombinedRequirementsJavascriptAsyncDefer($backend, false, true);

        $html = $backend->includeInHTML(self::$html_template);

        /* DEFER IS INCLUDED IN SCRIPT TAG */
        $this->assertRegExp(
            '/src=".*' . preg_quote($combinedFileName, '/') . '" defer/',
            $html,
            'defer is included in script tag'
        );

        /* ASYNC IS NOT INCLUDED IN SCRIPT TAG */
        $this->assertNotContains('async', $html, 'async is not included');

        /* COMBINED JAVASCRIPT FILE EXISTS */
        clearstatcache(); // needed to get accurate file_exists() results
        $this->assertFileExists(
            $combinedFilePath,
            'combined javascript file exists'
        );

        /* COMBINED JAVASCRIPT HAS CORRECT CONTENT */
        $this->assertContains(
            "alert('b')",
            file_get_contents($combinedFilePath),
            'combined javascript has correct content'
        );
        $this->assertContains(
            "alert('c')",
            file_get_contents($combinedFilePath),
            'combined javascript has correct content'
        );

        /* COMBINED FILES ARE NOT INCLUDED TWICE */
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_b\.js/',
            $html,
            'combined files are not included twice'
        );
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_c\.js/',
            $html,
            'combined files are not included twice'
        );

        /* NORMAL REQUIREMENTS ARE STILL INCLUDED */
        $this->assertRegExp(
            '/src=".*\/RequirementsTest_a\.js/',
            $html,
            'normal requirements are still included'
        );

        /* NORMAL REQUIREMENTS DON'T HAVE ASYNC/DEFER */
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async/',
            $html,
            'normal requirements don\'t have async'
        );
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" defer/',
            $html,
            'normal requirements don\'t have defer'
        );
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async defer/',
            $html,
            'normal requirements don\'t have async/defer'
        );

        // setup again for testing async and defer
        unlink($combinedFilePath);
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);

        $this->setupCombinedRequirementsJavascriptAsyncDefer($backend, true, true);

        $html = $backend->includeInHTML(self::$html_template);

        /* ASYNC/DEFER IS INCLUDED IN SCRIPT TAG */
        $this->assertRegExp(
            '/src=".*' . preg_quote($combinedFileName, '/') . '" async="async" defer="defer"/',
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
        $this->assertContains(
            "alert('b')",
            file_get_contents($combinedFilePath),
            'combined javascript has correct content'
        );
        $this->assertContains(
            "alert('c')",
            file_get_contents($combinedFilePath),
            'combined javascript has correct content'
        );

        /* COMBINED FILES ARE NOT INCLUDED TWICE */
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_b\.js/',
            $html,
            'combined files are not included twice'
        );
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_c\.js/',
            $html,
            'combined files are not included twice'
        );

        /* NORMAL REQUIREMENTS ARE STILL INCLUDED */
        $this->assertRegExp(
            '/src=".*\/RequirementsTest_a\.js/',
            $html,
            'normal requirements are still included'
        );

        /* NORMAL REQUIREMENTS DON'T HAVE ASYNC/DEFER */
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async/',
            $html,
            'normal requirements don\'t have async'
        );
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" defer/',
            $html,
            'normal requirements don\'t have defer'
        );
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_a\.js\?m=\d+" async defer/',
            $html,
            'normal requirements don\'t have async/defer'
        );

        unlink($combinedFilePath);
    }

    public function testCombinedCss()
    {
        $basePath = $this->getThemeRoot();
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);

        $backend->combineFiles(
            'print.css',
            array(
                $basePath . '/css/RequirementsTest_print_a.css',
                $basePath . '/css/RequirementsTest_print_b.css'
            ),
            array(
                'media' => 'print'
            )
        );

        $html = $backend->includeInHTML(self::$html_template);

        $this->assertRegExp(
            '/href=".*\/print\-94e723d\.css/',
            $html,
            'Print stylesheets have been combined.'
        );
        $this->assertRegExp(
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
            array(
                $basePath . '/css/RequirementsTest_b.css',
                $basePath . '/css/RequirementsTest_c.css'
            )
        );
        $backend->combineFiles(
            'style.css',
            array(
                $basePath . '/css/RequirementsTest_b.css',
                $basePath . '/css/RequirementsTest_c.css'
            )
        );

        $html = $backend->includeInHTML(self::$html_template);
        $this->assertRegExp(
            '/href=".*\/style\-bcd90f5\.css/',
            $html,
            'Stylesheets have been combined.'
        );
    }

    public function testBlockedCombinedJavascript()
    {
        $basePath = $this->getThemeRoot();
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupCombinedRequirements($backend);
        $combinedFileName = '/_combinedfiles/RequirementsTest_bc-2a55d56.js';
        $combinedFilePath = TestAssetStore::base_path() . $combinedFileName;

        /* BLOCKED COMBINED FILES ARE NOT INCLUDED */
        $backend->block('RequirementsTest_bc.js');

        clearstatcache(); // needed to get accurate file_exists() results
        $html = $backend->includeInHTML(self::$html_template);
        $this->assertFileNotExists($combinedFilePath);
        $this->assertNotRegExp(
            '/src=".*\/RequirementsTest_bc\.js/',
            $html,
            'blocked combined files are not included'
        );
        $backend->unblock('RequirementsTest_bc.js');

        /* BLOCKED UNCOMBINED FILES ARE NOT INCLUDED */
        $this->setupCombinedRequirements($backend);
        $backend->block($basePath . '/javascript/RequirementsTest_b.js');
        $combinedFileName2 = '/_combinedfiles/RequirementsTest_bc-3748f67.js'; // SHA1 without file b included
        $combinedFilePath2 = TestAssetStore::base_path() . $combinedFileName2;
        clearstatcache(); // needed to get accurate file_exists() results
        $backend->includeInHTML(self::$html_template);
        $this->assertFileExists($combinedFilePath2);
        $this->assertNotContains(
            "alert('b')",
            file_get_contents($combinedFilePath2),
            'blocked uncombined files are not included'
        );
        $backend->unblock($basePath . '/javascript/RequirementsTest_b.js');

        /* A SINGLE FILE CAN'T BE INCLUDED IN TWO COMBINED FILES */
        $this->setupCombinedRequirements($backend);
        clearstatcache(); // needed to get accurate file_exists() results

        // Exception generated from including invalid file
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(sprintf(
            "Requirements_Backend::combine_files(): Already included file(s) %s in combined file '%s'",
            $basePath . '/javascript/RequirementsTest_c.js',
            'RequirementsTest_bc.js'
        ));
        $backend->combineFiles(
            'RequirementsTest_ac.js',
            array(
                $basePath . '/javascript/RequirementsTest_a.js',
                $basePath . '/javascript/RequirementsTest_c.js'
            )
        );
    }

    public function testArgsInUrls()
    {
        $basePath = $this->getThemeRoot();

        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);

        $backend->javascript($basePath . '/javascript/RequirementsTest_a.js?test=1&test=2&test=3');
        $backend->css($basePath . '/css/RequirementsTest_a.css?test=1&test=2&test=3');
        $html = $backend->includeInHTML(self::$html_template);

        /* Javascript has correct path */
        $this->assertRegExp(
            '/src=".*\/RequirementsTest_a\.js\?test=1&amp;test=2&amp;test=3&amp;m=\d\d+/',
            $html,
            'javascript has correct path'
        );

        /* CSS has correct path */
        $this->assertRegExp(
            '/href=".*\/RequirementsTest_a\.css\?test=1&amp;test=2&amp;test=3&amp;m=\d\d+/',
            $html,
            'css has correct path'
        );
    }

    public function testRequirementsBackend()
    {
        $basePath = $this->getThemeRoot();

        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->javascript($basePath . '/a.js');

        $this->assertCount(
            1,
            $backend->getJavascript(),
            "There should be only 1 file included in required javascript."
        );
        $this->assertArrayHasKey(
            $basePath . '/a.js',
            $backend->getJavascript(),
            "a.js should be included in required javascript."
        );

        $backend->javascript($basePath . '/b.js');
        $this->assertCount(
            2,
            $backend->getJavascript(),
            "There should be 2 files included in required javascript."
        );

        $backend->block($basePath . '/a.js');
        $this->assertCount(
            1,
            $backend->getJavascript(),
            "There should be only 1 file included in required javascript."
        );
        $this->assertArrayNotHasKey(
            $basePath . '/a.js',
            $backend->getJavascript(),
            "a.js should not be included in required javascript after it has been blocked."
        );
        $this->assertArrayHasKey(
            $basePath . '/b.js',
            $backend->getJavascript(),
            "b.js should be included in required javascript."
        );

        $backend->css($basePath . '/a.css');
        $this->assertCount(
            1,
            $backend->getCSS(),
            "There should be only 1 file included in required css."
        );
        $this->assertArrayHasKey(
            $basePath . '/a.css',
            $backend->getCSS(),
            "a.css should be in required css."
        );

        $backend->block($basePath . '/a.css');
        $this->assertCount(
            0,
            $backend->getCSS(),
            "There should be nothing in required css after file has been blocked."
        );
    }

    public function testAppendAndBlockWithModuleResourceLoader()
    {
        $basePath = $this->getThemeRoot();

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
        $testPath = $this->getThemeRoot();

        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $holder = Requirements::backend();
        Requirements::set_backend($backend);
        $data = new ArrayData(
            array(
            'FailTest' => true,
            )
        );

        // Note: SSViewer theme automatically registered due to 'templates' directory
        $data->renderWith('RequirementsTest_Conditionals');
        $this->assertFileIncluded($backend, 'css', $testPath . '/css/RequirementsTest_a.css');
        $this->assertFileIncluded(
            $backend,
            'js',
            array(
                $testPath . '/javascript/RequirementsTest_b.js',
                $testPath . '/javascript/RequirementsTest_c.js'
            )
        );
        $this->assertFileNotIncluded($backend, 'js', $testPath . '/javascript/RequirementsTest_a.js');
        $this->assertFileNotIncluded(
            $backend,
            'css',
            array(
                $testPath . '/css/RequirementsTest_b.css',
                $testPath . '/css/RequirementsTest_c.css'
            )
        );
        $backend->clear();
        $data = new ArrayData(
            array(
            'FailTest' => false,
            )
        );
        $data->renderWith('RequirementsTest_Conditionals');
        $this->assertFileNotIncluded($backend, 'css', $testPath . '/css/RequirementsTest_a.css');
        $this->assertFileNotIncluded(
            $backend,
            'js',
            array(
                $testPath . '/javascript/RequirementsTest_b.js',
                $testPath . '/javascript/RequirementsTest_c.js'
            )
        );
        $this->assertFileIncluded($backend, 'js', $testPath . '/javascript/RequirementsTest_a.js');
        $this->assertFileIncluded(
            $backend,
            'css',
            array(
                $testPath . '/css/RequirementsTest_b.css',
                $testPath . '/css/RequirementsTest_c.css'
            )
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
        $this->assertContains('<head><script', $html);

        $backend->setWriteJavascriptToBody(true);
        $html = $backend->includeInHTML($template);
        $this->assertNotContains('<head><script', $html);
        $this->assertContains("</script>\n</body>", $html);
    }

    public function testIncludedJsIsNotCommentedOut()
    {
        $template = '<html><head></head><body><!--<script>alert("commented out");</script>--></body></html>';
        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->javascript($this->getThemeRoot() . '/javascript/RequirementsTest_a.js');
        $html = $backend->includeInHTML($template);
        //wiping out commented-out html
        $html = preg_replace('/<!--(.*)-->/Uis', '', $html);
        $this->assertContains("RequirementsTest_a.js", $html);
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

        $src = $this->getThemeRoot() . '/javascript/RequirementsTest_a.js';
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
        $basePath = $this->getThemeRoot();

        /** @var Requirements_Backend $backend */
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);

        $backend->javascript($basePath . '/javascript/RequirementsTest_a.js');
        $backend->javascript($basePath . '/javascript/RequirementsTest_b.js?foo=bar&bla=blubb');
        $backend->css($basePath . '/css/RequirementsTest_a.css');
        $backend->css($basePath . '/css/RequirementsTest_b.css?foo=bar&bla=blubb');

        $urlGenerator->setNonceStyle('mtime');
        $html = $backend->includeInHTML($template);
        $this->assertRegExp('/RequirementsTest_a\.js\?m=[\d]*"/', $html);
        $this->assertRegExp('/RequirementsTest_b\.js\?foo=bar&amp;bla=blubb&amp;m=[\d]*"/', $html);
        $this->assertRegExp('/RequirementsTest_a\.css\?m=[\d]*"/', $html);
        $this->assertRegExp('/RequirementsTest_b\.css\?foo=bar&amp;bla=blubb&amp;m=[\d]*"/', $html);

        $urlGenerator->setNonceStyle(null);
        $html = $backend->includeInHTML($template);
        $this->assertNotContains('RequirementsTest_a.js=', $html);
        $this->assertNotRegExp('/RequirementsTest_a\.js\?m=[\d]*"/', $html);
        $this->assertNotRegExp('/RequirementsTest_b\.js\?foo=bar&amp;bla=blubb&amp;m=[\d]*"/', $html);
        $this->assertNotRegExp('/RequirementsTest_a\.css\?m=[\d]*"/', $html);
        $this->assertNotRegExp('/RequirementsTest_b\.css\?foo=bar&amp;bla=blubb&amp;m=[\d]*"/', $html);
    }

    /**
     * Tests that provided files work
     */
    public function testProvidedFiles()
    {
        /** @var Requirements_Backend $backend */
        $template = '<html><head></head><body><header>My header</header><p>Body</p></body></html>';
        $basePath = $this->getThemeRoot();

        // Test that provided files block subsequent files
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->javascript($basePath . '/javascript/RequirementsTest_a.js');
        $backend->javascript(
            $basePath . '/javascript/RequirementsTest_b.js',
            [
            'provides' => [
                $basePath . '/javascript/RequirementsTest_a.js',
                $basePath . '/javascript/RequirementsTest_c.js'
            ]
            ]
        );
        $backend->javascript($basePath . '/javascript/RequirementsTest_c.js');
        // Note that _a.js isn't considered provided because it was included
        // before it was marked as provided
        $this->assertEquals(
            [
            $basePath . '/javascript/RequirementsTest_c.js' => $basePath . '/javascript/RequirementsTest_c.js'
            ],
            $backend->getProvidedScripts()
        );
        $html = $backend->includeInHTML($template);
        $this->assertRegExp('/src=".*\/RequirementsTest_a\.js/', $html);
        $this->assertRegExp('/src=".*\/RequirementsTest_b\.js/', $html);
        $this->assertNotRegExp('/src=".*\/RequirementsTest_c\.js/', $html);

        // Test that provided files block subsequent combined files
        $backend = Injector::inst()->create(Requirements_Backend::class);
        $this->setupRequirements($backend);
        $backend->combineFiles('combined_a.js', [$basePath . '/javascript/RequirementsTest_a.js']);
        $backend->javascript(
            $basePath . '/javascript/RequirementsTest_b.js',
            [
            'provides' => [
                $basePath . '/javascript/RequirementsTest_a.js',
                $basePath . '/javascript/RequirementsTest_c.js'
            ]
            ]
        );
        $backend->combineFiles('combined_c.js', [$basePath . '/javascript/RequirementsTest_c.js']);
        $this->assertEquals(
            [
            $basePath . '/javascript/RequirementsTest_c.js' => $basePath . '/javascript/RequirementsTest_c.js'
            ],
            $backend->getProvidedScripts()
        );
        $html = $backend->includeInHTML($template);
        $this->assertRegExp('/src=".*\/combined_a/', $html);
        $this->assertRegExp('/src=".*\/RequirementsTest_b\.js/', $html);
        $this->assertNotRegExp('/src=".*\/combined_c/', $html);
        $this->assertNotRegExp('/src=".*\/RequirementsTest_c\.js/', $html);
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
            $failedMatches = array();
            foreach ($files as $file) {
                if (!array_key_exists($file, $includedFiles)) {
                    $failedMatches[] = $file;
                }
            }
            $this->assertCount(
                0,
                $failedMatches,
                "Failed asserting the $type files '"
                . implode("', '", $failedMatches)
                . "' have exact matches in the required elements:\n'"
                . implode("'\n'", array_keys($includedFiles)) . "'"
            );
        } else {
            $this->assertArrayHasKey(
                $files,
                $includedFiles,
                "Failed asserting the $type file '$files' has an exact match in the required elements:\n'"
                . implode("'\n'", array_keys($includedFiles)) . "'"
            );
        }
    }

    public function assertFileNotIncluded($backend, $type, $files)
    {
        $includedFiles = $this->getBackendFiles($backend, $type);
        if (is_array($files)) {
            $failedMatches = array();
            foreach ($files as $file) {
                if (array_key_exists($file, $includedFiles)) {
                    $failedMatches[] = $file;
                }
            }
            $this->assertCount(
                0,
                $failedMatches,
                "Failed asserting the $type files '"
                . implode("', '", $failedMatches)
                . "' do not have exact matches in the required elements:\n'"
                . implode("'\n'", array_keys($includedFiles)) . "'"
            );
        } else {
            $this->assertArrayNotHasKey(
                $files,
                $includedFiles,
                "Failed asserting the $type file '$files' does not have an exact match in the required elements:"
                        . "\n'" . implode("'\n'", array_keys($includedFiles)) . "'"
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
        $type = strtolower($type);
        switch (strtolower($type)) {
            case 'css':
                return $backend->getCSS();
            case 'js':
            case 'javascript':
            case 'script':
                return $backend->getJavascript();
        }
        return array();
    }

    /**
     * Get base directory of theme to use for this test
     *
     * @return string
     */
    protected function getThemeRoot()
    {
        return $this->getCurrentRelativePath() . '/SSViewerTest';
    }
}
