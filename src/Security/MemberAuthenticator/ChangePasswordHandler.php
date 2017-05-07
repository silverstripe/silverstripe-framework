<?php


namespace SilverStripe\Security\MemberAuthenticator;

use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Session;
use SilverStripe\Forms\FormRequestHandler;
use SilverStripe\Security\Member;
use SilverStripe\Security\Security;
use SilverStripe\Security\IdentityStore;

class ChangePasswordHandler extends FormRequestHandler
{
    /**
     * Change the password
     *
     * @param array $data The user submitted data
     * @return HTTPResponse
     */
    public function doChangePassword(array $data, $form)
    {
        $member = Member::currentUser();
        // The user was logged in, check the current password
        if ($member && (
            empty($data['OldPassword']) ||
            !$member->checkPassword($data['OldPassword'])->isValid()
        )) {
            $this->form->sessionMessage(
                _t('SilverStripe\\Security\\Member.ERRORPASSWORDNOTMATCH', "Your current password does not match, please try again"),
                "bad"
            );
            // redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
            return $this->redirectBackToForm();
        }

        if (!$member) {
            if (Session::get('AutoLoginHash')) {
                $member = Member::member_from_autologinhash(Session::get('AutoLoginHash'));
            }

            // The user is not logged in and no valid auto login hash is available
            if (!$member) {
                Session::clear('AutoLoginHash');
                return $this->redirect($this->addBackURLParam(Security::singleton()->Link('login')));
            }
        }

        // Check the new password
        if (empty($data['NewPassword1'])) {
            $this->form->sessionMessage(
                _t('SilverStripe\\Security\\Member.EMPTYNEWPASSWORD', "The new password can't be empty, please try again"),
                "bad"
            );

            // redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
            return $this->redirectBackToForm();
        }

        // Fail if passwords do not match
        if ($data['NewPassword1'] !== $data['NewPassword2']) {
            $this->form->sessionMessage(
                _t('SilverStripe\\Security\\Member.ERRORNEWPASSWORD', "You have entered your new password differently, try again"),
                "bad"
            );
            // redirect back to the form, instead of using redirectBack() which could send the user elsewhere.
            return $this->redirectBackToForm();
        }

        // Check if the new password is accepted
        $validationResult = $member->changePassword($data['NewPassword1']);
        if (!$validationResult->isValid()) {
            $this->form->setSessionValidationResult($validationResult);
            return $this->redirectBackToForm();
        }

        // Clear locked out status
        $member->LockedOutUntil = null;
        $member->FailedLoginCount = null;
        $member->write();

        if ($member->canLogIn()->isValid()) {
            Injector::inst()->get(IdentityStore::class)
                ->logIn($member, false, $form->getRequestHandler()->getRequest());
        }

        // TODO Add confirmation message to login redirect
        Session::clear('AutoLoginHash');

        // Redirect to backurl
        $backURL = $this->getBackURL();
        if ($backURL) {
            return $this->redirect($backURL);
        }

        // Redirect to default location - the login form saying "You are logged in as..."
        $url = Security::singleton()->Link('login');
        return $this->redirect($url);
    }

    public function redirectBackToForm()
    {
        // Redirect back to form
        $url = $this->addBackURLParam(CMSSecurity::singleton()->Link('changepassword'));
        return $this->redirect($url);
    }
}
