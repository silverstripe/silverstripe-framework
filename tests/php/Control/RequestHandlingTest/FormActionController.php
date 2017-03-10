<?php

namespace SilverStripe\Control\Tests\RequestHandlingTest;

use SilverStripe\Control\Controller;
use SilverStripe\Dev\TestOnly;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\TextField;
use SilverStripe\View\SSViewer;

class FormActionController extends Controller implements TestOnly
{
    protected $template = 'BlankPage';

    private static $url_segment = 'FormActionController';

    private static $allowed_actions = array(
        'controlleraction',
        'Form',
        'formactionInAllowedActions'
        //'formaction', // left out, implicitly allowed through form action
    );

    public function controlleraction($request)
    {
        return 'controlleraction';
    }

    public function disallowedcontrollermethod()
    {
        return 'disallowedcontrollermethod';
    }

    /**
     * @skipUpgrade
     */
    public function Form()
    {
        return new Form(
            $this,
            "Form",
            new FieldList(
                new TextField("MyField")
            ),
            new FieldList(
                new FormAction("formaction"),
                new FormAction('formactionInAllowedActions')
            )
        );
    }

    /**
     * @param array $data
     * @param Form  $form Made optional to simulate error behaviour in "live" environment (missing arguments don't throw a fatal error there)
     *  (missing arguments don't throw a fatal error there)
     * @return string
     */
    public function formaction($data, $form = null)
    {
        return 'formaction';
    }

    public function formactionInAllowedActions($data, $form = null)
    {
        return 'formactionInAllowedActions';
    }

    public function getViewer($action = null)
    {
        return new SSViewer('BlankPage');
    }
}
