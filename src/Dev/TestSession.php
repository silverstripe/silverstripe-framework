<?php

namespace SilverStripe\Dev;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Cookie_Backend;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Injector\Injector;
use SimpleByName;
use SimplePage;
use SimplePageBuilder;

/**
 * Represents a test usage session of a web-app
 * It will maintain session-state from request to request
 */
class TestSession
{
    use Extensible;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var Cookie_Backend
     */
    private $cookies;

    /**
     * @var HTTPResponse
     */
    private $lastResponse;

    /**
     * Necessary to use the mock session
     * created in {@link session} in the normal controller stack,
     * e.g. to overwrite Security::getCurrentUser() with custom login data.
     *
     * @var Controller
     */
    protected $controller;

    /**
     * Fake HTTP Referer Tracking, set in {@link get()} and {@link post()}.
     *
     * @var string
     */
    private $lastUrl;

    public function __construct()
    {
        $this->session = Injector::inst()->create(Session::class, array());
        $this->cookies = Injector::inst()->create(Cookie_Backend::class);
        $request = new HTTPRequest('GET', '/');
        $request->setSession($this->session());
        $this->controller = new Controller();
        $this->controller->setRequest($request);
        $this->controller->pushCurrent();
        $this->controller->doInit();
    }

    public function __destruct()
    {
        // Shift off anything else that's on the stack.  This can happen if something throws
        // an exception that causes a premature TestSession::__destruct() call
        while (Controller::has_curr() && Controller::curr() !== $this->controller) {
            Controller::curr()->popCurrent();
        }

        if (Controller::has_curr()) {
            $this->controller->popCurrent();
        }
    }

    /**
     * Submit a get request
     *
     * @uses Director::test()
     * @param string $url
     * @param Session $session
     * @param array $headers
     * @param array $cookies
     * @return HTTPResponse
     */
    public function get($url, $session = null, $headers = null, $cookies = null)
    {
        $this->extend('updateGetURL', $url, $session, $headers, $cookies);
        $headers = (array) $headers;
        if ($this->lastUrl && !isset($headers['Referer'])) {
            $headers['Referer'] = $this->lastUrl;
        }
        $this->lastResponse = Director::test(
            $url,
            null,
            $session ?: $this->session,
            null,
            null,
            $headers,
            $cookies ?: $this->cookies
        );
        $this->lastUrl = $url;
        if (!$this->lastResponse) {
            user_error("Director::test($url) returned null", E_USER_WARNING);
        }
        return $this->lastResponse;
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
        $this->extend('updatePostURL', $url, $data, $headers, $session, $body, $cookies);
        $headers = (array) $headers;
        if ($this->lastUrl && !isset($headers['Referer'])) {
            $headers['Referer'] = $this->lastUrl;
        }
        $this->lastResponse = Director::test(
            $url,
            $data,
            $session ?: $this->session,
            null,
            $body,
            $headers,
            $cookies ?: $this->cookies
        );
        $this->lastUrl = $url;
        if (!$this->lastResponse) {
            user_error("Director::test($url) returned null", E_USER_WARNING);
        }
        return $this->lastResponse;
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
     * @return HTTPResponse
     * @throws Exception
     */
    public function submitForm($formID, $button = null, $data = array())
    {
        $page = $this->lastPage();
        if ($page) {
            $form = $page->getFormById($formID);
            if (!$form) {
                user_error("TestSession::submitForm failed to find the form {$formID}");
            }

            foreach ($data as $k => $v) {
                $form->setField(new SimpleByName($k), $v);
            }

            if ($button) {
                $submission = $form->submitButton(new SimpleByName($button));
                if (!$submission) {
                    throw new Exception("Can't find button '$button' to submit as part of test.");
                }
            } else {
                $submission = $form->submit();
            }

            $url = Director::makeRelative($form->getAction()->asString());

            $postVars = array();
            parse_str($submission->_encode(), $postVars);
            return $this->post($url, $postVars);
        } else {
            user_error("TestSession::submitForm called when there is no form loaded."
                . " Visit the page with the form first", E_USER_WARNING);
        }
    }

    /**
     * If the last request was a 3xx response, then follow the redirection
     *
     * @return HTTPResponse The response given, or null if no redirect occurred
     */
    public function followRedirection()
    {
        if ($this->lastResponse->getHeader('Location')) {
            $url = Director::makeRelative($this->lastResponse->getHeader('Location'));
            $url = strtok($url, '#');
            return $this->get($url);
        }
    }

    /**
     * Returns true if the last response was a 3xx redirection
     *
     * @return bool
     */
    public function wasRedirected()
    {
        $code = $this->lastResponse->getStatusCode();
        return $code >= 300 && $code < 400;
    }

    /**
     * Get the most recent response
     *
     * @return HTTPResponse
     */
    public function lastResponse()
    {
        return $this->lastResponse;
    }

    /**
     * Return the fake HTTP_REFERER; set each time get() or post() is called.
     *
     * @return string
     */
    public function lastUrl()
    {
        return $this->lastUrl;
    }

    /**
     * Get the most recent response's content
     *
     * @return string
     */
    public function lastContent()
    {
        if (is_string($this->lastResponse)) {
            return $this->lastResponse;
        } else {
            return $this->lastResponse->getBody();
        }
    }

    /**
     * Return a CSSContentParser containing the most recent response
     *
     * @return CSSContentParser
     */
    public function cssParser()
    {
        return new CSSContentParser($this->lastContent());
    }

    /**
     * Get the last response as a SimplePage object
     *
     * @return SimplePage The response if available
     */
    public function lastPage()
    {
        require_once("simpletest/http.php");
        require_once("simpletest/page.php");
        require_once("simpletest/form.php");

        $builder = new SimplePageBuilder();
        if ($this->lastResponse) {
            $page = &$builder->parse(new TestSession_STResponseWrapper($this->lastResponse));
            $builder->free();
            unset($builder);

            return $page;
        }
    }

    /**
     * Get the current session, as a Session object
     *
     * @return Session
     */
    public function session()
    {
        return $this->session;
    }
}
