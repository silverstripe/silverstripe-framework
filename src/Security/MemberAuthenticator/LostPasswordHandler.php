<?php

namespace SilverStripe\Security\MemberAuthenticator;

use Psr\Log\LoggerInterface;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Email\Email;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\RequestHandler;
use SilverStripe\Core\Cache\RateLimiter;
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
     * Number of password reset requests from one IP address for one member after which further
     * requests are blocked, until lock_out_delay_mins have passed since the first request.
     * Set to 0 to disable the lockout.
     */
    private static int $lock_out_after_reset_requests = 3;

    /**
     * Minutes, from the first password reset request, over which lock_out_after_reset_requests is counted.
     * Only applies if lock_out_after_reset_requests is greater than 0.
     */
    private static int $lock_out_delay_mins = 15;

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
     * Extensions can use the 'onForgotPassword' method to veto executing
     * the logic, by returning FALSE. In this case, the user will be redirected back
     * to the form without further action. It is recommended to set a message
     * in the form detailing why the action was denied.
     */
    public function forgotPassword(array $data, Form $form): HTTPResponse
    {
        return Security::withMinimumExecutionTime(function () use ($data, $form) {
            // Run a first pass validation check on the data
            $dataValidation = $this->validateForgotPasswordData($data, $form);
            if ($dataValidation instanceof HTTPResponse) {
                return $dataValidation;
            }

            $member = $this->getMemberFromData($data);

            // A rate-limited request gets the normal success page, so the response does not show the limit
            if ($this->isRateLimited($member, $data)) {
                return $this->redirectToSuccess($data);
            }

            // Allow vetoing forgot password requests
            $results = $this->extend('onForgotPassword', $member);
            if ($results && is_array($results) && in_array(false, $results ?? [], true)) {
                return $this->redirectToLostPassword();
            }

            if ($member) {
                $token = $member->generateAutologinTokenAndStoreHash();
                $this->sendEmail($member, $token);
            }

            return $this->redirectToSuccess($data);
        });
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
     * Count a password reset request from one IP address for one member, or for one address with no member,
     * and return whether it is over the limit
     */
    private function isRateLimited(?Member $member, array $data): bool
    {
        $maxAttempts = (int) $this->config()->get('lock_out_after_reset_requests');
        if ($maxAttempts <= 0) {
            return false;
        }
        $target = 'email-' . strtolower(trim((string) $data['Email']));
        if ($member) {
            // MySQL's default utf8mb4_unicode_ci collation matches mémber@example.com to member@example.com,
            // so keying on the submitted address would give each accented spelling of one member its own limit
            $target = 'member-' . $member->ID;
        }
        // Include the IP address so requests from one IP address do not use up the limit for another
        $ip = $this->getRateLimitIP();
        $limiter = RateLimiter::create(
            'lostpassword-' . md5($target . '|' . $ip),
            $maxAttempts,
            (int) $this->config()->get('lock_out_delay_mins')
        );
        if (!$limiter->canAccess()) {
            return true;
        }
        $limiter->hit();
        return false;
    }

    /**
     * Get the IP address of the request, reduced to its /64 network for an IPv6 address
     */
    private function getRateLimitIP(): string
    {
        $ip = (string) $this->getRequest()->getIP();
        if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            return $ip;
        }
        $packedIP = inet_pton($ip);
        // An IPv4-mapped address (::ffff:192.0.2.1) is one IPv4 caller, and its /64 prefix is all zeros
        if (str_starts_with($packedIP, str_repeat("\0", 10) . "\xff\xff")) {
            return inet_ntop($packedIP);
        }
        // One IPv6 connection is usually given a whole /64, so a caller could rotate addresses inside it
        return bin2hex(substr($packedIP, 0, 8)) . '::/64';
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
            $logger = Injector::inst()->get(LoggerInterface::class);
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
