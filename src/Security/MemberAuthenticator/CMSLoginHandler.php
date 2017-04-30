<?php

namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Security\CMSSecurity;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;

class CMSLoginHandler extends LoginHandler
{
    private static $allowed_actions = [
        'LoginForm'
    ];

    /**
     * Return the CMSMemberLoginForm form
     */
    public function loginForm()
    {
        return CMSMemberLoginForm::create(
            $this,
            get_class($this->authenticator),
            'LoginForm'
        );
    }

    public function redirectBackToForm()
    {
        // Redirect back to form
        $url = $this->addBackURLParam(CMSSecurity::singleton()->Link('login'));
        return $this->redirect($url);
    }

    /**
     * Redirect the user to the change password form.
     *
     * @skipUpgrade
     * @return HTTPResponse
     */
    protected function redirectToChangePassword()
    {
        // Since this form is loaded via an iframe, this redirect must be performed via javascript
        $changePasswordForm = ChangePasswordForm::create($this->form->getController(), 'ChangePasswordForm');
        $changePasswordForm->sessionMessage(
            _t('SilverStripe\\Security\\Member.PASSWORDEXPIRED', 'Your password has expired. Please choose a new one.'),
            'good'
        );

        // Get redirect url
        $changePasswordURL = $this->addBackURLParam(Security::singleton()->Link('changepassword'));
        $changePasswordURLATT = Convert::raw2att($changePasswordURL);
        $changePasswordURLJS = Convert::raw2js($changePasswordURL);
        $message = _t(
            'SilverStripe\\Security\\CMSMemberLoginForm.PASSWORDEXPIRED',
            '<p>Your password has expired. <a target="_top" href="{link}">Please choose a new one.</a></p>',
            'Message displayed to user if their session cannot be restored',
            array('link' => $changePasswordURLATT)
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
        if (Member::currentUser()->isPasswordExpired()) {
            // Redirect the user to the external password change form if necessary
            return $this->redirectToChangePassword();
        }

        // Link to success template
        $url = CMSSecurity::singleton()->Link('success');
        return $this->redirect($url);
    }
}
