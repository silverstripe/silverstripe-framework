<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Control\RequestHandler;
use SilverStripe\ORM\ValidationResult;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\EmailField;
use SilverStripe\Forms\FormAction;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\FieldType\DBField;

/**
 * Handle login requests from MemberLoginForm
 */
class LostPasswordHandler extends RequestHandler
{
    protected $authenticatorClass = MemberAuthenticator::class;

    private static $url_handlers = [
        'passwordsent/$EmailAddress' => 'passwordsent',
        '' => 'lostpassword',
    ];

    /**
     * Since the logout and dologin actions may be conditionally removed, it's necessary to ensure these
     * remain valid actions regardless of the member login state.
     *
     * @var array
     * @config
     */
    private static $allowed_actions = [
        'lostpassword',
        'LostPasswordForm',
        'passwordsent',
    ];

    private $link = null;

    /**
     * @param $link The URL to recreate this request handler
     */
    public function __construct($link)
    {
        $this->link = $link;
        parent::__construct();
    }

    /**
     * Return a link to this request handler.
     * The link returned is supplied in the constructor
     * @return string
     */
    public function link($action = null)
    {
        if ($action) {
            return Controller::join_links($this->link, $action);
        } else {
            return $this->link;
        }
    }

    /**
     * URL handler for the initial lost-password screen
     */
    public function lostpassword()
    {

        $message = _t(
            'Security.NOTERESETPASSWORD',
            'Enter your e-mail address and we will send you a link with which you can reset your password'
        );

        return [
            'Content' => DBField::create_field('HTMLFragment', "<p>$message</p>"),
            'Form' => $this->lostPasswordForm(),
        ];
    }

    /**
     * Show the "password sent" page, after a user has requested
     * to reset their password.
     */
    public function passwordsent()
    {
        $request = $this->getRequest();
        $email = Convert::raw2xml(rawurldecode($request->param('EmailAddress')) . '.' . $request->getExtension());

        $message = _t(
            'Security.PASSWORDSENTTEXT',
            "Thank you! A reset link has been sent to '{email}', provided an account exists for this email"
            . " address.",
            [ 'email' => Convert::raw2xml($email) ]
        );

        return [
            'Title' => _t(
                'Security.PASSWORDSENTHEADER',
                "Password reset link sent to '{email}'",
                array('email' => $email)
            ),
            'Content' => DBField::create_field('HTMLFragment', "<p>$message</p>"),
            'Email' => $email
        ];
    }


    /**
     * Factory method for the lost password form
     *
     * @skipUpgrade
     * @return Form Returns the lost password form
     */
    public function lostPasswordForm()
    {
        return LoginForm::create(
            $this,
            $this->authenticatorClass,
            'LostPasswordForm',
            new FieldList(
                new EmailField('Email', _t('Member.EMAIL', 'Email'))
            ),
            new FieldList(
                new FormAction(
                    'forgotPassword',
                    _t('Security.BUTTONSEND', 'Send me the password reset link')
                )
            ),
            false
        );
    }

    /**
     * Redirect to password recovery form
     *
     * @return HTTPResponse
     */
    public function redirectToLostPassword()
    {
        $lostPasswordLink = Security::singleton()->Link('lostpassword');
        return $this->redirect($this->addBackURLParam($lostPasswordLink));
    }

    public function getReturnReferer()
    {
        return $this->link();
    }

    /**
     * Log out form handler method
     *
     * This method is called when the user clicks on "logout" on the form
     * created when the parameter <i>$checkCurrentUser</i> of the
     * {@link __construct constructor} was set to TRUE and the user was
     * currently logged in.
     *
     * @return HTTPResponse
     */
    public function logout()
    {
        return Security::singleton()->logout();
    }

    /**
     * Try to authenticate the user
     *
     * @param array $data Submitted data
     * @return Member Returns the member object on successful authentication
     *                or NULL on failure.
     */
    public function performLogin($data)
    {
        $member = call_user_func_array(
            [$this->authenticator_class, 'authenticate'],
            [$data, $this->form]
        );
        if ($member) {
            $member->LogIn(isset($data['Remember']));
            return $member;
        }

        // No member, can't login
        $this->extend('authenticationFailed', $data);
        return null;
    }

    /**
     * Forgot password form handler method.
     * Called when the user clicks on "I've lost my password".
     * Extensions can use the 'forgotPassword' method to veto executing
     * the logic, by returning FALSE. In this case, the user will be redirected back
     * to the form without further action. It is recommended to set a message
     * in the form detailing why the action was denied.
     *
     * @skipUpgrade
     * @param array $data Submitted data
     * @return HTTPResponse
     */
    public function forgotPassword($data)
    {
        // Ensure password is given
        if (empty($data['Email'])) {
            $this->form->sessionMessage(
                _t('Member.ENTEREMAIL', 'Please enter an email address to get a password reset link.'),
                'bad'
            );
            return $this->redirectToLostPassword();
        }

        // Find existing member
        /** @var Member $member */
        $member = Member::get()->filter("Email", $data['Email'])->first();

        // Allow vetoing forgot password requests
        $results = $this->extend('forgotPassword', $member);
        if ($results && is_array($results) && in_array(false, $results, true)) {
            return $this->redirectToLostPassword();
        }

        if ($member) {
            $token = $member->generateAutologinTokenAndStoreHash();

            Email::create()
                ->setHTMLTemplate('SilverStripe\\Control\\Email\\ForgotPasswordEmail')
                ->setData($member)
                ->setSubject(_t('Member.SUBJECTPASSWORDRESET', "Your password reset link", 'Email subject'))
                ->addData('PasswordResetLink', Security::getPasswordResetLink($member, $token))
                ->setTo($member->Email)
                ->send();
        }

        // Avoid information disclosure by displaying the same status,
        // regardless wether the email address actually exists
        $link = Controller::join_links(
            $this->link('passwordsent'),
            rawurlencode($data['Email']),
            '/'
        );
        return $this->redirect($this->addBackURLParam($link));
    }

    /**
     * @todo copypaste from FormRequestHandler - refactor
     */
    protected function addBackURLParam($link)
    {
        $backURL = $this->getBackURL();
        if ($backURL) {
            return Controller::join_links($link, '?BackURL=' . urlencode($backURL));
        }
        return $link;
    }
}
