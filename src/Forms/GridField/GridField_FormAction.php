<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;

/**
 * This class is the base class when you want to have an action that alters the state of the
 * {@link GridField}, rendered as a button element.
 */
class GridField_FormAction extends FormAction
{
    /**
     * @var GridField
     */
    protected $gridField;

    /**
     * @var array
     */
    protected $stateValues;

    /**
     * @var array
     */
    protected $args = array();

    /**
     * @var string
     */
    protected $actionName;

    /**
     * @var boolean
     */
    public $useButtonTag = true;

    /**
     * @param GridField $gridField
     * @param string $name
     * @param string $title
     * @param string $actionName
     * @param array $args
     */
    public function __construct(GridField $gridField, $name, $title, $actionName, $args)
    {
        $this->gridField = $gridField;
        $this->actionName = $actionName;
        $this->args = $args;

        parent::__construct($name, $title);
    }

    /**
     * Encode all non-word characters.
     *
     * @param string $value
     *
     * @return string
     */
    public function nameEncode($value)
    {
        return (string)preg_replace_callback('/[^\w]/', array($this, '_nameEncode'), $value);
    }

    /**
     * @param array $match
     *
     * @return string
     */
    public function _nameEncode($match)
    {
        return '%' . dechex(ord($match[0]));
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        // Store state in session, and pass ID to client side.
        $state = array(
            'grid' => $this->getNameFromParent(),
            'actionName' => $this->actionName,
            'args' => $this->args,
        );

        // Ensure $id doesn't contain only numeric characters
        $id = 'gf_' . substr(md5(serialize($state)), 0, 8);

        $session = Controller::curr()->getRequest()->getSession();
        $session->set($id, $state);
        $actionData['StateID'] = $id;

        return array_merge(
            parent::getAttributes(),
            array(
                // Note:  This field needs to be less than 65 chars, otherwise Suhosin security patch
                // will strip it from the requests
                'name' => 'action_gridFieldAlterAction' . '?' . http_build_query($actionData),
                'data-url' => $this->gridField->Link(),
            )
        );
    }

    /**
     * Calculate the name of the gridfield relative to the form.
     *
     * @return string
     */
    protected function getNameFromParent()
    {
        $base = $this->gridField;
        $name = array();

        do {
            array_unshift($name, $base->getName());
            $base = $base->getForm();
        } while ($base && !($base instanceof Form));

        return implode('.', $name);
    }
}
