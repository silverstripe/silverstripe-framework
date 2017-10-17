<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Convert;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

/**
 * Handle login requests from MemberLoginForm
 */
class LostPasswordHandler extends RequestHandler
{
    /**
     * Authentication class to use
     * @var string
     */
    protected $authenticatorClass = MemberAuthenticator::class;

    /**
     * @var array
     */
    private static $url_handlers = [
        'passwordsent/$EmailAddress' => 'passwordsent',
        ''                           => 'lostpassword',
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
     * @param string $link The URL to recreate this request handler
     */
    public function __construct($link)
    {
        $this->link = $link;
        parent::__construct();
    }

    /**
     * Return a link to this request handler.
     * The link returned is supplied in the constructor
     *
     * @param string $action
     * @return string
     */
    public function link($action = null)
    {
        if ($action) {
            return Controller::join_links($this->link, $action);
        }

        return $this->link;
    }

    /**
     * URL handler for the initial lost-password screen
     *
     * @return array
     */
    public function lostpassword()
    {

        $message = _t(
            'SilverStripe\\Security\\Security.NOTERESETPASSWORD',
            'Enter your e-mail address and we will send you a link with which you can reset your password'
        );

        return [
            'Content' => DBField::create_field('HTMLFragment', "<p>$message</p>"),
            'Form'    => $this->lostPasswordForm(),
        ];
    }

    /**
     * Show the "password sent" page, after a user has requested
     * to reset their password.
     *
     * @return array
     */
    public function passwordsent()
    {
        $request = $this->getRequest();
        $email = Convert::raw2xml(rawurldecode($request->param('EmailAddress')));
        if ($request->getExtension()) {
            $email = $email . '.' . Convert::raw2xml($request->getExtension());
        }

        $message = _t(
            'SilverStripe\\Security\\Security.PASSWORDSENTTEXT',
            "Thank you! A reset link has been sent to '{email}', provided an account exists for this email"
            . " address.",
            ['email' => Convert::raw2xml($email)]
        );

        return [
            'Title'   => _t(
                'SilverStripe\\Security\\Security.PASSWORDSENTHEADER',
                "Password reset link sent to '{email}'",
                array('email' => $email)
            ),
            'Content' => DBField::create_field('HTMLFragment', "<p>$message</p>"),
            'Email'   => $email
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
        return LostPasswordForm::create(
            $this,
            $this->authenticatorClass,
            'lostPasswordForm',
            null,
            null,
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
     * @param LostPasswordForm $form
     * @return HTTPResponse
     */
    public function forgotPassword($data, $form)
    {
        // Run a first pass validation check on the data
        $dataValidation = $this->validateForgotPasswordData($data, $form);
        if ($dataValidation instanceof HTTPResponse) {
            return $dataValidation;
        }

        /** @var Member $member */
        $member = $this->getMemberFromData($data);

        // Allow vetoing forgot password requests
        $results = $this->extend('forgotPassword', $member);
        if ($results && is_array($results) && in_array(false, $results, true)) {
            return $this->redirectToLostPassword();
        }

        if ($member) {
            $token = $member->generateAutologinTokenAndStoreHash();

            $this->sendEmail($member, $token);
        }

        return $this->redirectToSuccess($data);
    }

    /**
     * Ensure that the user has provided an email address. Note that the "Email" key is specific to this
     * implementation, but child classes can override this method to use another unique identifier field
     * for validation.
     *
     * @param  array $data
     * @param  LostPasswordForm $form
     * @return HTTPResponse|null
     */
    protected function validateForgotPasswordData(array $data, LostPasswordForm $form)
    {
        if (empty($data['Email'])) {
            $form->sessionMessage(
                _t(
                    'SilverStripe\\Security\\Member.ENTEREMAIL',
                    'Please enter an email address to get a password reset link.'
                ),
                'bad'
            );

            return $this->redirectToLostPassword();
        }
    }

    /**
     * Load an existing Member from the provided data
     *
     * @param  array $data
     * @return Member|null
     */
    protected function getMemberFromData(array $data)
    {
        if (!empty($data['Email'])) {
            $uniqueIdentifier = Member::config()->get('unique_identifier_field');
            return Member::get()->filter([$uniqueIdentifier => $data['Email']])->first();
        }
    }

    /**
     * Send the email to the member that requested a reset link
     * @param Member $member
     * @param string $token
     * @return bool
     */
    protected function sendEmail($member, $token)
    {
        /** @var Email $email */
        $email = Email::create()
            ->setHTMLTemplate('SilverStripe\\Control\\Email\\ForgotPasswordEmail')
            ->setData($member)
            ->setSubject(_t(
                'SilverStripe\\Security\\Member.SUBJECTPASSWORDRESET',
                "Your password reset link",
                'Email subject'
            ))
            ->addData('PasswordResetLink', Security::getPasswordResetLink($member, $token))
            ->setTo($member->Email);
        return $email->send();
    }

    /**
     * Avoid information disclosure by displaying the same status, regardless wether the email address actually exists
     *
     * @param array $data
     * @return HTTPResponse
     */
    protected function redirectToSuccess(array $data)
    {
        $link = Controller::join_links(
            $this->link('passwordsent'),
            rawurlencode($data['Email']),
            '/'
        );

        return $this->redirect($this->addBackURLParam($link));
    }
}
