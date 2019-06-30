<?php declare(strict_types = 1);

namespace SilverStripe\Control\Tests\HTTPCacheControlIntegrationTest;

use SilverStripe\Control\Controller;
use SilverStripe\Control\Middleware\HTTPCacheControlMiddleware;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\Security\Permission;
use SilverStripe\Security\SecurityToken;

class SessionController extends Controller implements TestOnly
{
    private static $url_segment = 'HTTPCacheControlIntegrationTest_SessionController';

    private static $allowed_actions = [
        'showform',
        'privateaction',
        'publicaction',
        'showpublicform',
        'Form',
    ];

    protected function init()
    {
        parent::init();
        // Prefer public by default
        HTTPCacheControlMiddleware::singleton()->publicCache();
    }

    public function getContent()
    {
        return '<p>Hello world</p>';
    }

    public function showform()
    {
        // Form should be set to private due to CSRF
        SecurityToken::enable();
        return $this->renderWith('BlankPage');
    }

    public function showpublicform()
    {
        // Public form doesn't use CSRF and thus no session usage
        SecurityToken::disable();
        return $this->renderWith('BlankPage');
    }

    /**
     * @return string
     * @throws \SilverStripe\Control\HTTPResponse_Exception
     */
    public function privateaction()
    {
        if (!Permission::check('ANYCODE')) {
            $this->httpError(403, 'Not allowed');
        }
        return 'ok';
    }

    public function publicaction()
    {
        return 'Hello!';
    }

    public function Form()
    {
        $form = new Form(
            $this,
            'Form',
            new FieldList(new TextField('Name')),
            new FieldList(new FormAction('submit', 'Submit'))
        );
        $form->setFormMethod('GET');
        return $form;
    }
}
