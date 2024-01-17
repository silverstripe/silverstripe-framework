<?php

namespace SilverStripe\Dev;

use PHPUnit\Framework\AssertionFailedError;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Config;
use SilverStripe\Security\BasicAuth;
use SilverStripe\Security\SecurityToken;
use SilverStripe\View\SSViewer;
use SimpleXMLElement;

/**
 * SilverStripe-specific testing object designed to support functional testing of your web app.  It simulates get/post
 * requests, form submission, and can validate resulting HTML, looking up content by CSS selector.
 *
 * The example below shows how it works.
 *
 * <code>
 *   public function testMyForm() {
 *   // Visit a URL
 *   $this->get("your/url");
 *
 *   // Submit a form on the page that you get in response
 *   $this->submitForm("MyForm_ID", "action_dologin", array("Email" => "invalid email ^&*&^"));
 *
 *   // Validate the content that is returned
 *   $this->assertExactMatchBySelector("#MyForm_ID p.error", array("That email address is invalid."));
 *  }
 * </code>
 */
abstract class FunctionalTest extends SapphireTest implements TestOnly
{
    /**
     * Set this to true on your sub-class to disable the use of themes in this test.
     * This can be handy for functional testing of modules without having to worry about whether a user has changed
     * behaviour by replacing the theme.
     *
     * @var bool
     */
    protected static $disable_themes = false;

    /**
     * @var TestSession
     */
    protected $mainSession = null;

    /**
     * CSSContentParser for the most recently requested page.
     *
     * @var CSSContentParser
     */
    protected $cssParser = null;

    /**
     * If this is true, then 30x Location headers will be automatically followed.
     * If not, then you will have to manually call $this->mainSession->followRedirection() to follow them.
     * However, this will let you inspect the intermediary headers
     *
     * @var bool
     */
    protected $autoFollowRedirection = true;

    /**
     * Returns the {@link Session} object for this test
     *
     * @return Session
     */
    public function session()
    {
        return $this->mainSession->session();
    }

    protected function setUp(): void
    {
        parent::setUp();

        // Skip calling FunctionalTest directly.
        if (static::class == __CLASS__) {
            $this->markTestSkipped(sprintf('Skipping %s ', static::class));
        }

        $this->mainSession = new TestSession();

        // Disable theme, if necessary
        if (static::get_disable_themes()) {
            SSViewer::config()->set('theme_enabled', false);
        }

        // Flush user
        $this->logOut();

        // Unprotect the site, tests are running with the assumption it's off. They will enable it on a case-by-case
        // basis.
        BasicAuth::protect_entire_site(false);

        SecurityToken::disable();
    }

    protected function tearDown(): void
    {
        SecurityToken::enable();
        unset($this->mainSession);
        parent::tearDown();
    }

    /**
     * Run a test while mocking the base url with the provided value
     * @param string $url The base URL to use for this test
     * @param callable $callback The test to run
     */
    protected function withBaseURL($url, $callback)
    {
        $oldBase = Config::inst()->get(Director::class, 'alternate_base_url');
        Config::modify()->set(Director::class, 'alternate_base_url', $url);
        $callback($this);
        Config::modify()->set(Director::class, 'alternate_base_url', $oldBase);
    }

    /**
     * Run a test while mocking the base folder with the provided value
     * @param string $folder The base folder to use for this test
     * @param callable $callback The test to run
     */
    protected function withBaseFolder($folder, $callback)
    {
        $oldFolder = Config::inst()->get(Director::class, 'alternate_base_folder');
        Config::modify()->set(Director::class, 'alternate_base_folder', $folder);
        $callback($this);
        Config::modify()->set(Director::class, 'alternate_base_folder', $oldFolder);
    }

    /**
     * Submit a get request
     * @uses Director::test()
     *
     * @param string $url
     * @param Session $session
     * @param array $headers
     * @param array $cookies
     * @return HTTPResponse
     */
    public function get($url, $session = null, $headers = null, $cookies = null)
    {
        $this->cssParser = null;
        $response = $this->mainSession->get($url, $session, $headers, $cookies);
        if ($this->autoFollowRedirection && is_object($response) && $response->getHeader('Location')) {
            $response = $this->mainSession->followRedirection();
        }
        return $response;
    }

    /**
     * Submit a post request
     *
     * @uses Director::test()
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param Session $session
     * @param string $body
     * @param array $cookies
     * @return HTTPResponse
     */
    public function post($url, $data, $headers = null, $session = null, $body = null, $cookies = null)
    {
        $this->cssParser = null;
        $response = $this->mainSession->post($url, $data, $headers, $session, $body, $cookies);
        if ($this->autoFollowRedirection && is_object($response) && $response->getHeader('Location')) {
            $response = $this->mainSession->followRedirection();
        }
        return $response;
    }

    /**
     * Submit the form with the given HTML ID, filling it out with the given data.
     * Acts on the most recent response.
     *
     * Any data parameters have to be present in the form, with exact form field name
     * and values, otherwise they are removed from the submission.
     *
     * Caution: Parameter names have to be formatted
     * as they are in the form submission, not as they are interpreted by PHP.
     * Wrong: array('mycheckboxvalues' => array(1 => 'one', 2 => 'two'))
     * Right: array('mycheckboxvalues[1]' => 'one', 'mycheckboxvalues[2]' => 'two')
     *
     * @see http://www.simpletest.org/en/form_testing_documentation.html
     *
     * @param string $formID HTML 'id' attribute of a form (loaded through a previous response)
     * @param string $button HTML 'name' attribute of the button (NOT the 'id' attribute)
     * @param array $data Map of GET/POST data.
     * @param bool $withSecurityToken Submit with the form's security token if there is one.
     */
    public function submitForm(string $formID, string $button = null, array $data = [], bool $withSecurityToken = true): HTTPResponse
    {
        $this->cssParser = null;
        $response = $this->mainSession->submitForm($formID, $button, $data, $withSecurityToken);
        if ($this->autoFollowRedirection && is_object($response) && $response->getHeader('Location')) {
            $response = $this->mainSession->followRedirection();
        }
        return $response;
    }

    /**
     * Return the most recent content
     *
     * @return string
     */
    public function content()
    {
        return $this->mainSession->lastContent();
    }

    /**
     * Find an attribute in a SimpleXMLElement object by name.
     * @param SimpleXMLElement $object
     * @param string $attribute Name of attribute to find
     * @return SimpleXMLElement object of the attribute
     */
    public function findAttribute($object, $attribute)
    {
        $found = false;
        foreach ($object->attributes() as $a => $b) {
            if ($a == $attribute) {
                $found = $b;
            }
        }
        return $found;
    }

    /**
     * Return a CSSContentParser for the most recent content.
     *
     * @return CSSContentParser
     */
    public function cssParser()
    {
        if (!$this->cssParser) {
            $this->cssParser = new CSSContentParser($this->mainSession->lastContent());
        }
        return $this->cssParser;
    }

    /**
     * Assert that the most recently queried page contains a number of content tags specified by a CSS selector.
     * The given CSS selector will be applied to the HTML of the most recent page.  The content of every matching tag
     * will be examined. The assertion fails if one of the expectedMatches fails to appear.
     *
     * Note: &nbsp; characters are stripped from the content; make sure that your assertions take this into account.
     *
     * @param string $selector A basic CSS selector, e.g. 'li.jobs h3'
     * @param array|string $expectedMatches The content of at least one of the matched tags
     * @param string $message
     * @throws AssertionFailedError
     */
    public function assertPartialMatchBySelector($selector, $expectedMatches, $message = null)
    {
        if (is_string($expectedMatches)) {
            $expectedMatches = [$expectedMatches];
        }

        $items = $this->cssParser()->getBySelector($selector);

        $actuals = [];
        if ($items) {
            foreach ($items as $item) {
                $actuals[trim(preg_replace('/\s+/', ' ', (string)$item))] = true;
            }
        }

        $message = $message ?:
        "Failed asserting the CSS selector '$selector' has a partial match to the expected elements:\n'"
            . implode("'\n'", $expectedMatches) . "'\n\n"
            . "Instead the following elements were found:\n'" . implode("'\n'", array_keys($actuals ?? [])) . "'";

        foreach ($expectedMatches as $match) {
            $this->assertTrue(isset($actuals[$match]), $message);
        }
    }

    /**
     * Assert that the most recently queried page contains a number of content tags specified by a CSS selector.
     * The given CSS selector will be applied to the HTML of the most recent page.  The full HTML of every matching tag
     * will be examined. The assertion fails if one of the expectedMatches fails to appear.
     *
     * Note: &nbsp; characters are stripped from the content; make sure that your assertions take this into account.
     *
     * @param string $selector A basic CSS selector, e.g. 'li.jobs h3'
     * @param array|string $expectedMatches The content of *all* matching tags as an array
     * @param string $message
     * @throws AssertionFailedError
     */
    public function assertExactMatchBySelector($selector, $expectedMatches, $message = null)
    {
        if (is_string($expectedMatches)) {
            $expectedMatches = [$expectedMatches];
        }

        $items = $this->cssParser()->getBySelector($selector);

        $actuals = [];
        if ($items) {
            foreach ($items as $item) {
                $actuals[] = trim(preg_replace('/\s+/', ' ', (string)$item) ?? '');
            }
        }

        $message = $message ?:
                "Failed asserting the CSS selector '$selector' has an exact match to the expected elements:\n'"
                . implode("'\n'", $expectedMatches) . "'\n\n"
            . "Instead the following elements were found:\n'" . implode("'\n'", $actuals) . "'";

        $this->assertTrue($expectedMatches == $actuals, $message);
    }

    /**
     * Assert that the most recently queried page contains a number of content tags specified by a CSS selector.
     * The given CSS selector will be applied to the HTML of the most recent page.  The content of every matching tag
     * will be examined. The assertion fails if one of the expectedMatches fails to appear.
     *
     * Note: &nbsp; characters are stripped from the content; make sure that your assertions take this into account.
     *
     * @param string $selector A basic CSS selector, e.g. 'li.jobs h3'
     * @param array|string $expectedMatches The content of at least one of the matched tags
     * @param string $message
     * @throws AssertionFailedError
     */
    public function assertPartialHTMLMatchBySelector($selector, $expectedMatches, $message = null)
    {
        if (is_string($expectedMatches)) {
            $expectedMatches = [$expectedMatches];
        }

        $items = $this->cssParser()->getBySelector($selector);

        $actuals = [];
        if ($items) {
            foreach ($items as $item) {
                $actuals[$item->asXML()] = true;
            }
        }

        $message = $message ?:
                "Failed asserting the CSS selector '$selector' has a partial match to the expected elements:\n'"
                . implode("'\n'", $expectedMatches) . "'\n\n"
            . "Instead the following elements were found:\n'" . implode("'\n'", array_keys($actuals ?? [])) . "'";

        foreach ($expectedMatches as $match) {
            $this->assertTrue(isset($actuals[$match]), $message);
        }
    }

    /**
     * Assert that the most recently queried page contains a number of content tags specified by a CSS selector.
     * The given CSS selector will be applied to the HTML of the most recent page.  The full HTML of every matching tag
     * will be examined. The assertion fails if one of the expectedMatches fails to appear.
     *
     * Note: &nbsp; characters are stripped from the content; make sure that your assertions take this into account.
     *
     * @param string $selector A basic CSS selector, e.g. 'li.jobs h3'
     * @param array|string $expectedMatches The content of *all* matched tags as an array
     * @param string $message
     * @throws AssertionFailedError
     */
    public function assertExactHTMLMatchBySelector($selector, $expectedMatches, $message = null)
    {
        $items = $this->cssParser()->getBySelector($selector);

        $actuals = [];
        if ($items) {
            foreach ($items as $item) {
                $actuals[] = $item->asXML();
            }
        }

        $message = $message ?:
            "Failed asserting the CSS selector '$selector' has an exact match to the expected elements:\n'"
            . implode("'\n'", $expectedMatches) . "'\n\n"
            . "Instead the following elements were found:\n'" . implode("'\n'", $actuals) . "'";

        $this->assertTrue($expectedMatches == $actuals, $message);
    }

    /**
     * @return bool
     */
    public static function get_disable_themes()
    {
        return static::$disable_themes;
    }
}
