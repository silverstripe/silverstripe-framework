<?php

namespace SilverStripe\Security;

use SilverStripe\Admin\AdminRootController;
use SilverStripe\Admin\LeftAndMain;
use SilverStripe\Control\Controller;
use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Manifest\ModuleLoader;
use SilverStripe\ORM\FieldType\DBField;
use SilverStripe\ORM\FieldType\DBHTMLText;
use SilverStripe\View\Requirements;
use SilverStripe\View\SSViewer;

/**
 * Provides a security interface functionality within the cms
 */
class CMSSecurity extends Security
{
    private static $allowed_actions = [
        'login',
        'success'
    ];

    /**
     * Enable in-cms reauthentication
     *
     * @var boolean
     * @config
     */
    private static $reauth_enabled = true;

    protected function init()
    {
        parent::init();

        // Assign default cms theme and replace user-specified themes
        SSViewer::set_themes(LeftAndMain::config()->uninherited('admin_themes'));

        if (ModuleLoader::getModule('silverstripe/admin')) {
            // Core styles / vendor scripts
            Requirements::javascript('silverstripe/admin: client/dist/js/vendor.js');
            Requirements::css('silverstripe/admin: client/dist/styles/bundle.css');
        }
    }

    public function login($request = null, $service = Authenticator::CMS_LOGIN)
    {
        return parent::login($request, Authenticator::CMS_LOGIN);
    }

    public function Link($action = null)
    {
        return Controller::join_links(Director::baseURL(), "CMSSecurity", $action);
    }

    protected function getAuthenticator($name = 'cms')
    {
        return parent::getAuthenticator($name);
    }

    public function getApplicableAuthenticators($service = Authenticator::CMS_LOGIN)
    {
        return parent::getApplicableAuthenticators($service);
    }

    /**
     * Get known logged out member
     *
     * @return Member
     */
    public function getTargetMember()
    {
        $tempid = $this->getRequest()->requestVar('tempid');
        if ($tempid) {
            return Member::member_from_tempid($tempid);
        }

        return null;
    }

    public function getResponseController($title)
    {
        // Use $this to prevent use of Page to render underlying templates
        return $this;
    }

    protected function getSessionMessage(&$messageType = null)
    {
        $message =  parent::getSessionMessage($messageType);
        if ($message) {
            return $message;
        }

        // Format
        return _t(
            __CLASS__ . '.LOGIN_MESSAGE',
            '<p>Your session has timed out due to inactivity</p>'
        );
    }

    /**
     * Check if there is a logged in member
     *
     * @return bool
     */
    public function getIsloggedIn()
    {
        return !!Security::getCurrentUser();
    }

    /**
     * Redirects the user to the external login page
     *
     * @return HTTPResponse
     */
    protected function redirectToExternalLogin()
    {
        $loginURL = Security::create()->Link('login');
        $loginURLATT = Convert::raw2att($loginURL);
        $loginURLJS = Convert::raw2js($loginURL);
        $message = _t(
            __CLASS__ . '.INVALIDUSER',
            '<p>Invalid user. <a target="_top" href="{link}">Please re-authenticate here</a> to continue.</p>',
            'Message displayed to user if their session cannot be restored',
            ['link' => $loginURLATT]
        );
        $response = $this->getResponse();
        $response->setStatusCode(200);
        $response->setBody(<<<PHP
<!DOCTYPE html>
<html><body>
$message
<script type="application/javascript">
setTimeout(function(){top.location.href = "$loginURLJS";}, 0);
</script>
</body></html>
PHP
        );
        $this->setResponse($response);

        return $response;
    }

    protected function preLogin()
    {
        // If no member has been previously logged in for this session, force a redirect to the main login page
        if (!$this->getTargetMember()) {
            return $this->redirectToExternalLogin();
        }

        return parent::preLogin();
    }

    /**
     * Determine if CMSSecurity is enabled
     *
     * @return bool
     */
    public function enabled()
    {
        // Disable shortcut
        if (!static::config()->get('reauth_enabled')) {
            return false;
        }

        return count($this->getApplicableAuthenticators(Authenticator::CMS_LOGIN) ?? []) > 0;
    }

    /**
     * Given a successful login, tell the parent frame to close the dialog
     *
     * @return HTTPResponse|DBField
     */
    public function success()
    {
        // Ensure member is properly logged in
        if (!Security::getCurrentUser() || !class_exists(AdminRootController::class)) {
            return $this->redirectToExternalLogin();
        }

        // Get redirect url
        $controller = $this->getResponseController(_t(__CLASS__ . '.SUCCESS', 'Success'));
        $backURLs = [
            $this->getRequest()->requestVar('BackURL'),
            $this->getRequest()->getSession()->get('BackURL'),
            Director::absoluteURL(AdminRootController::config()->get('url_base')),
        ];
        $backURL = null;
        foreach ($backURLs as $backURL) {
            if ($backURL && Director::is_site_url($backURL)) {
                break;
            }
        }

        // Show login
        $controller = $controller->customise([
            'Content' => DBField::create_field(DBHTMLText::class, _t(
                __CLASS__ . '.SUCCESSCONTENT',
                '<p>Login success. If you are not automatically redirected ' . '<a target="_top" href="{link}">click here</a></p>',
                'Login message displayed in the cms popup once a user has re-authenticated themselves',
                ['link' => Convert::raw2att($backURL)]
            ))
        ]);

        return $controller->renderWith($this->getTemplatesFor('success'));
    }
}
