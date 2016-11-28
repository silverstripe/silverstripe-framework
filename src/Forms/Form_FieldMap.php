<?php

namespace SilverStripe\Forms;

use SilverStripe\View\ViewableData;

class Form_FieldMap extends ViewableData
{

    /**
     * @var Form
     */
    protected $form;

    public function __construct($form)
    {
        $this->form = $form;
        parent::__construct();
    }

    /**
     * Ensure that all potential method calls get passed to __call(), therefore to dataFieldByName
     * @param string $method
     * @return bool
     */
    public function hasMethod($method)
    {
        return true;
    }

    public function __call($method, $args = null)
    {
        return $this->form->Fields()->fieldByName($method);
    }
}
