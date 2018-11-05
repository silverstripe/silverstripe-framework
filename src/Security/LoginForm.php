<?php

namespace SilverStripe\Security;

use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;

/**
 * Abstract base class for a login form
 *
 * This class is used as a base class for the different log-in forms like
 * {@link MemberLoginForm} or {@link OpenIDLoginForm}.
 *
 * @author Markus Lanthaler <markus@silverstripe.com>
 */
abstract class LoginForm extends Form
{
    /**
     * @deprecated 4.4.0:5.0.0 Use getAuthenticatorClass() or setAuthenticatorClass() instead
     * @var string
     */
    protected $authenticator_class;

    /**
     * Authenticator class to use with this login form
     *
     * Set this variable to the authenticator class to use with this login form.
     *
     * @var string
     */
    protected $authenticatorClass;

    /**
     * Set the authenticator class name to use
     *
     * @param string $class
     * @return $this
     */
    public function setAuthenticatorClass($class)
    {
        $this->authenticatorClass = $class;

        /** @var FieldList|null $fields */
        $fields = $this->Fields();
        if (!$fields) {
            return $this;
        }

        $authenticatorField = $fields->dataFieldByName('AuthenticationMethod');
        if ($authenticatorField) {
            $authenticatorField->setValue($class);
        }

        return $this;
    }

    /**
     * Returns the authenticator class name to use
     *
     * @return string
     */
    public function getAuthenticatorClass()
    {
        // B/C for deprecated authenticator_class property
        return $this->authenticator_class ?: $this->authenticatorClass;
    }

    /**
     * Return the title of the form for use in the frontend
     * For tabs with multiple login methods, for example.
     * This replaces the old `get_name` method
     * @return string
     */
    abstract public function getAuthenticatorName();

    /**
     * Required FieldList creation on a LoginForm
     *
     * @return FieldList
     */
    abstract protected function getFormFields();

    /**
     * Required FieldList creation for the login actions on this LoginForm
     *
     * @return FieldList
     */
    abstract protected function getFormActions();
}
