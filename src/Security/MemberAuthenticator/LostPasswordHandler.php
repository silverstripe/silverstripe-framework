<?php

namespace SilverStripe\Security\MemberAuthenticator;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\Form;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Exception\RfcComplianceException;

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
        'passwordsent' => 'passwordsent',
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

    /**
     * Link to this handler
     *
     * @var string
     */
    protected $link = null;

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
     * @param string|null $action
     * @return string
     */
    public function Link($action = null)
    {
        $link = Controller::join_links($this->link, $action);
        $this->extend('updateLink', $link, $action);
        return $link;
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
        $message = _t(
            'SilverStripe\\Security\\Security.PASSWORDRESETSENTTEXT',
            "Thank you. A reset link has been sent, provided an account exists for this email address."
        );

        return [
            'Title' => _t(
                'SilverStripe\\Security\\Security.PASSWORDRESETSENTHEADER',
                "Password reset link sent"
            ),
            'Content' => DBField::create_field('HTMLFragment', "<p>$message</p>"),
        ];
    }


    /**
     * Factory method for the lost password form
     *
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
     */
    public function forgotPassword(array $data, Form $form): HTTPResponse
    {
        // Run a first pass validation check on the data
        $dataValidation = $this->validateForgotPasswordData($data, $form);
        if ($dataValidation instanceof HTTPResponse) {
            return $dataValidation;
        }

        $member = $this->getMemberFromData($data);

        // Allow vetoing forgot password requests
        $results = $this->extend('forgotPassword', $member);
        if ($results && is_array($results) && in_array(false, $results ?? [], true)) {
            return $this->redirectToLostPassword();
        }

        if ($member) {
            $token = $member->generateAutologinTokenAndStoreHash();

            $success = $this->sendEmail($member, $token);
            if (!$success) {
                $form->sessionMessage(
                    _t(
                        Member::class . '.EMAIL_FAILED',
                        'There was an error when trying to email you a password reset link.'
                    ),
                    'bad'
                );

                return $this->redirectToLostPassword();
            }
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
        try {
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

            $member->extend('updateForgotPasswordEmail', $email);
            $email->send();
            return true;
        } catch (TransportExceptionInterface | RfcComplianceException $e) {
            /** @var LoggerInterface $logger */
            $logger = Injector::inst()->get(LoggerInterface::class . '.errorhandler');
            $logger->error('Error sending email in ' . __FILE__ . ' line ' . __LINE__ . ": {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Avoid information disclosure by displaying the same status, regardless whether the email address actually exists
     *
     * @param array $data
     * @return HTTPResponse
     */
    protected function redirectToSuccess(array $data)
    {
        $link = $this->link('passwordsent');

        return $this->redirect($this->addBackURLParam($link));
    }
}
