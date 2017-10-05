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
     * Authenticator class to use with this login form
     *
     * Set this variable to the authenticator class to use with this login
     * form.
     * @var string
     */
    protected $authenticator_class;

    public function setAuthenticatorClass($class)
    {
        $this->authenticator_class = $class;
        $authenticatorField = $this->Fields()->dataFieldByName('AuthenticationMethod');
        if ($authenticatorField) {
            $authenticatorField->setValue($class);
        }
        return $this;
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
