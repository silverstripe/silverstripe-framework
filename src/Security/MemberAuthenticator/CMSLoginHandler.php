<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\PasswordExpirationMiddleware;
use SilverStripe\Security\CMSSecurity;
use SilverStripe\Security\Security;

class CMSLoginHandler extends LoginHandler
{
    private static $allowed_actions = [
        'LoginForm'
    ];

    /**
     * Return the CMSMemberLoginForm form
     *
     * @return CMSMemberLoginForm
     */
    public function loginForm()
    {
        return CMSMemberLoginForm::create(
            $this,
            get_class($this->authenticator),
            'LoginForm'
        );
    }

    /**
     * @return HTTPResponse
     */
    public function redirectBackToForm()
    {
        // Redirect back to form
        $url = $this->addBackURLParam($this->getReturnReferer());
        return $this->redirect($url);
    }

    public function getReturnReferer()
    {
        // Try to retain referer (includes tempid param)
        $referer = $this->getReferer();
        if ($referer && Director::is_site_url($referer)) {
            return $referer;
        }
        return CMSSecurity::singleton()->Link('login');
    }

    /**
     * Redirect the user to the change password form.
     *
     * @return HTTPResponse
     */
    protected function redirectToChangePassword()
    {
        // Since this form is loaded via an iframe, this redirect must be performed via javascript
        $changePasswordForm = ChangePasswordForm::create($this, 'ChangePasswordForm');
        $changePasswordForm->sessionMessage(
            _t('SilverStripe\\Security\\Member.PASSWORDEXPIRED', 'Your password has expired. Please choose a new one.'),
            'good'
        );

        // Get redirect url
        $changedPasswordLink = Security::singleton()->Link('changepassword');
        $changePasswordURL = $this->addBackURLParam($changedPasswordLink);

        if (Injector::inst()->has(PasswordExpirationMiddleware::class)) {
            $session = $this->getRequest()->getSession();
            $passwordExpirationMiddleware = Injector::inst()->get(PasswordExpirationMiddleware::class);
            $passwordExpirationMiddleware->allowCurrentRequest($session);
        }

        $changePasswordURLATT = Convert::raw2att($changePasswordURL);
        $changePasswordURLJS = Convert::raw2js($changePasswordURL);
        $message = _t(
            'SilverStripe\\Security\\CMSMemberLoginForm.PASSWORDEXPIRED',
            '<p>Your password has expired. <a target="_top" href="{link}">Please choose a new one.</a></p>',
            'Message displayed to user if their session cannot be restored',
            ['link' => $changePasswordURLATT]
        );

        // Redirect to change password page
        $response = HTTPResponse::create()
            ->setBody(<<<PHP
<!DOCTYPE html>
<html><body>
$message
<script type="application/javascript">
setTimeout(function(){top.location.href = "$changePasswordURLJS";}, 0);
</script>
</body></html>
PHP
        );
        return $response;
    }

    /**
     * Send user to the right location after login
     *
     * @return HTTPResponse
     */
    protected function redirectAfterSuccessfulLogin()
    {
        // Check password expiry
        if (Security::getCurrentUser()->isPasswordExpired()) {
            // Redirect the user to the external password change form if necessary
            return $this->redirectToChangePassword();
        }

        // Link to success template
        $url = CMSSecurity::singleton()->Link('success');
        return $this->redirect($url);
    }
}
