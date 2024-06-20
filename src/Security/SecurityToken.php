<?php

namespace SilverStripe\Security;

use Exception;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Session;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\HiddenField;
use SilverStripe\View\TemplateGlobalProvider;

/**
 * Cross Site Request Forgery (CSRF) protection for the {@link Form} class and other GET links.
 * Can be used globally (through {@link SecurityToken::inst()})
 * or on a form-by-form basis {@link Form->getSecurityToken()}.
 *
 * <b>Usage in forms</b>
 *
 * This protective measure is automatically turned on for all new {@link Form} instances,
 * and can be globally disabled through {@link disable()}.
 *
 * <b>Usage in custom controller actions</b>
 *
 * <code>
 * class MyController extends Controller {
 *  function mygetaction($request) {
 *      if(!SecurityToken::inst()->checkRequest($request)) return $this->httpError(400);
 *
 *      // valid action logic ...
 *  }
 * }
 * </code>
 *
 */
class SecurityToken implements TemplateGlobalProvider
{
    use Configurable;
    use Injectable;

    /**
     * @var string
     */
    protected static $default_name = 'SecurityID';

    /**
     * @var SecurityToken
     */
    protected static $inst = null;

    /**
     * @var boolean
     */
    protected static $enabled = true;

    /**
     * @var string $name
     */
    protected $name = null;

    /**
     * @param string $name
     */
    public function __construct($name = null)
    {
        $this->name = $name ?: SecurityToken::get_default_name();
    }

    /**
     * Gets a global token (or creates one if it doesnt exist already).
     *
     * @return SecurityToken
     */
    public static function inst()
    {
        if (!SecurityToken::$inst) {
            SecurityToken::$inst = new SecurityToken();
        }

        return SecurityToken::$inst;
    }

    /**
     * Globally disable the token (override with {@link NullSecurityToken})
     * implementation. Note: Does not apply for
     */
    public static function disable()
    {
        SecurityToken::$enabled = false;
        SecurityToken::$inst = new NullSecurityToken();
    }

    /**
     * Globally enable tokens that have been previously disabled through {@link disable}.
     */
    public static function enable()
    {
        SecurityToken::$enabled = true;
        SecurityToken::$inst = new SecurityToken();
    }

    /**
     * @return boolean
     */
    public static function is_enabled()
    {
        return SecurityToken::$enabled;
    }

    /**
     * @return string
     */
    public static function get_default_name()
    {
        return SecurityToken::$default_name;
    }

    /**
     * Returns the value of an the global SecurityToken in the current session
     * @return int
     */
    public static function getSecurityID()
    {
        $token = SecurityToken::inst();
        return $token->getValue();
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $val = $this->getValue();
        $this->name = $name;
        $this->setValue($val);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        $session = $this->getSession();
        $value = $session->get($this->getName());

        // only regenerate if the token isn't already set in the session
        if (!$value) {
            $value = $this->generate();
            $this->setValue($value);
        }

        return $value;
    }

    /**
     * @param string $val
     * @return $this
     */
    public function setValue($val)
    {
        $this->getSession()->set($this->getName(), $val);
        return $this;
    }

    /**
     * Returns the current session instance from the injector
     *
     * @return Session
     * @throws Exception If the HTTPRequest class hasn't been registered as a service and no controllers exist
     */
    protected function getSession()
    {
        $injector = Injector::inst();
        if ($injector->has(HTTPRequest::class)) {
            return $injector->get(HTTPRequest::class)->getSession();
        } elseif (Controller::has_curr()) {
            return Controller::curr()->getRequest()->getSession();
        }
        throw new Exception('No HTTPRequest object or controller available yet!');
    }

    /**
     * Reset the token to a new value.
     */
    public function reset()
    {
        $this->setValue($this->generate());
    }

    /**
     * Checks for an existing CSRF token in the current users session.
     * This check is automatically performed in {@link Form->httpSubmission()}
     * if a form has security tokens enabled.
     * This direct check is mainly used for URL actions on {@link FormField} that are not routed
     * through {@link Form->httpSubmission()}.
     *
     * Typically you'll want to check {@link Form->securityTokenEnabled()} before calling this method.
     *
     * @param string $compare
     * @return boolean
     */
    public function check($compare)
    {
        return ($compare && $this->getValue() && $compare == $this->getValue());
    }

    /**
     * See {@link check()}.
     *
     * @param HTTPRequest $request
     * @return bool
     */
    public function checkRequest($request)
    {
        $token = $this->getRequestToken($request);
        return $this->check($token);
    }

    /**
     * Get security token from request
     *
     * @param HTTPRequest $request
     * @return string
     */
    protected function getRequestToken($request)
    {
        $name = $this->getName();
        $header = 'X-' . ucwords(strtolower($name ?? ''));
        if ($token = $request->getHeader($header)) {
            return $token;
        }

        // Get from request var
        return $request->requestVar($name);
    }

    /**
     * Note: Doesn't call {@link FormField->setForm()}
     * on the returned {@link HiddenField}, you'll need to take
     * care of this yourself.
     *
     * @param FieldList $fieldset
     * @return HiddenField|false
     */
    public function updateFieldSet(&$fieldset)
    {
        if (!$fieldset->fieldByName($this->getName())) {
            $field = new HiddenField($this->getName(), null, $this->getValue());
            $fieldset->push($field);
            return $field;
        } else {
            return false;
        }
    }

    /**
     * @param string $url
     * @return string
     */
    public function addToUrl($url)
    {
        return Controller::join_links($url, sprintf('?%s=%s', $this->getName(), $this->getValue()));
    }

    /**
     * You can't disable an existing instance, it will need to be overwritten like this:
     * <code>
     * $old = SecurityToken::inst(); // isEnabled() returns true
     * SecurityToken::disable();
     * $new = SecurityToken::inst(); // isEnabled() returns false
     * </code>
     *
     * @return boolean
     */
    public function isEnabled()
    {
        return !($this instanceof NullSecurityToken);
    }

    /**
     * @uses RandomGenerator
     *
     * @return string
     */
    protected function generate()
    {
        $generator = new RandomGenerator();
        return $generator->randomToken('sha1');
    }

    public static function get_template_global_variables()
    {
        return [
            'getSecurityID',
            'SecurityID' => 'getSecurityID'
        ];
    }
}
