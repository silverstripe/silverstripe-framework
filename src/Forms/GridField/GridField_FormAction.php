<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\GridField\FormAction\StateStore;

/**
 * This class is the base class when you want to have an action that alters the state of the
 * {@link GridField}, rendered as a button element.
 */
class GridField_FormAction extends FormAction
{
    /**
     * A common string prefix for keys generated to store form action "state" against
     */
    const STATE_KEY_PREFIX = 'gf_';

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
    protected $args = [];

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
        return (string)preg_replace_callback('/[^\w]/', [$this, '_nameEncode'], $value ?? '');
    }

    /**
     * @param array $match
     *
     * @return string
     */
    public function _nameEncode($match)
    {
        return '%' . dechex(ord($match[0] ?? ''));
    }

    /**
     * @return array
     */
    public function getAttributes()
    {
        // Determine the state that goes with this action
        $state = [
            'grid' => $this->getNameFromParent(),
            'actionName' => $this->actionName,
            'args' => $this->args,
        ];

        // Generate a key and attach it to the action name
        $key = static::STATE_KEY_PREFIX . substr(md5(serialize($state)), 0, 8);
        // Note: This field needs to be less than 65 chars, otherwise Suhosin security patch will strip it
        $name = 'action_gridFieldAlterAction?StateID=' . $key;

        // Define attributes
        $attributes = [
            'name' => $name,
            'data-url' => $this->gridField->Link(),
            'type' => "button",
        ];

        // Create a "store" for the "state" of this action
        /** @var StateStore $store */
        $store = Injector::inst()->create(StateStore::class . '.' . $this->gridField->getName());
        // Store the state and update attributes as required
        $attributes += $store->save($key, $state);

        // Return attributes
        return array_merge(
            parent::getAttributes(),
            $attributes
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
        $name = [];

        do {
            array_unshift($name, $base->getName());
            $base = $base->getForm();
        } while ($base && !($base instanceof Form));

        return implode('.', $name);
    }
}
