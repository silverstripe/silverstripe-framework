<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\FieldType\DBHTMLText;

/**
 * The action buttons are `<input type="submit">` as well as <button> tags.
 *
 * Upon clicking the button below will redirect the user to doAction under the current controller.
 *
 * <code>
 * new FormAction (
 *    // doAction has to be a defined controller member
 *    $action = "doAction",
 *    $title = "Submit button"
 * )
 * </code>
 */
class FormAction extends FormField
{

    /**
     * @config
     * @var array
     */
    private static $casting = [
        'ButtonContent' => 'HTMLFragment',
    ];

    /**
     * Action name, normally prefixed with 'action_'
     *
     * @var string
     */
    protected $action;

    /**
     * Identifier of icon, if supported on the frontend
     *
     * @var string
     */
    protected $icon = null;

    /**
     * @var string
     */
    protected $schemaComponent = 'FormAction';

    /**
     * Enables the use of `<button>` instead of `<input>`
     * in {@link Field()} - for more customisable styling.
     *
     * @var boolean
     */
    public $useButtonTag = false;

    /**
     * Literal button content, used when useButtonTag is true.
     *
     * @var string
     */
    protected $buttonContent = null;

    /**
     * Should validation be skipped when performing this action?
     *
     * @var bool
     */
    protected $validationExempt = false;

    /**
     * Create a new action button.
     *
     * @param string $action The method to call when the button is clicked
     * @param string $title The label on the button. This should be plain text, not escaped as HTML.
     * @param Form $form The parent form, auto-set when the field is placed inside a form
     */
    public function __construct($action, $title = "", $form = null)
    {
        $this->action = "action_$action";
        $this->setForm($form);

        parent::__construct($this->action, $title);
    }

    /**
     * Add extra options to data
     */
    public function getSchemaDataDefaults()
    {
        $defaults = parent::getSchemaDataDefaults();
        $defaults['attributes']['type'] = $this->getUseButtonTag() ? 'button' : 'submit';
        $defaults['data']['icon'] = $this->getIcon();
        return $defaults;
    }

    /**
     * Get button icon, if supported
     *
     * @return string
     */
    public function getIcon()
    {
        return $this->icon;
    }

    /**
     * Sets button icon
     *
     * @param string $icon Icon identifier (not path)
     * @return $this
     */
    public function setIcon($icon)
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Get the action name
     *
     * @return string
     */
    public function actionName()
    {
        return substr($this->name ?? '', 7);
    }

    /**
     * Set the full action name, including action_
     * This provides an opportunity to replace it with something else
     *
     * @param string $fullAction
     * @return $this
     */
    public function setFullAction($fullAction)
    {
        $this->action = $fullAction;
        return $this;
    }

    /**
     * @param array $properties
     * @return DBHTMLText
     */
    public function Field($properties = [])
    {
        $properties = array_merge(
            $properties,
            [
                'Name' => $this->action,
                'Title' => ($this->description && !$this->useButtonTag) ? $this->description : $this->Title(),
                'UseButtonTag' => $this->useButtonTag
            ]
        );

        return parent::Field($properties);
    }

    /**
     * @param array $properties
     * @return DBHTMLText
     */
    public function FieldHolder($properties = [])
    {
        return $this->Field($properties);
    }

    public function Type()
    {
        return 'action';
    }

    public function getInputType()
    {
        if (isset($this->attributes['type'])) {
            return $this->attributes['type'];
        } else {
            return (isset($this->attributes['src'])) ? 'image' : 'submit';
        }
    }

    public function getAttributes()
    {
        $attributes = array_merge(
            parent::getAttributes(),
            [
                'disabled' => ($this->isReadonly() || $this->isDisabled()),
                'value'    => $this->Title(),
                'type'     => $this->getInputType(),
            ]
        );

        // Override title with description if supplied
        if ($this->getDescription()) {
            $attributes['title'] = $this->getDescription();
        }
        return $attributes;
    }

    /**
     * Add content inside a button field. This should be pre-escaped raw HTML and should be used sparingly.
     *
     * @param string $content
     * @return $this
     */
    public function setButtonContent($content)
    {
        $this->buttonContent = (string) $content;
        return $this;
    }

    /**
     * Gets the content inside the button field. This is raw HTML, and should be used sparingly.
     *
     * @return string
     */
    public function getButtonContent()
    {
        return $this->buttonContent;
    }

    /**
     * Enable or disable the rendering of this action as a <button />
     *
     * @param boolean $bool
     * @return $this
     */
    public function setUseButtonTag($bool)
    {
        $this->useButtonTag = $bool;
        return $this;
    }

    /**
     * Determine if this action is rendered as a <button />
     *
     * @return boolean
     */
    public function getUseButtonTag()
    {
        return $this->useButtonTag;
    }

    /**
     * Set whether this action can be performed without validating the data
     *
     * @param bool $exempt
     * @return $this
     */
    public function setValidationExempt($exempt = true)
    {
        $this->validationExempt = $exempt;
        return $this;
    }

    /**
     * Get whether this action can be performed without validating the data
     *
     * @return bool
     */
    public function getValidationExempt()
    {
        return $this->validationExempt;
    }

    /**
     * Does not transform to readonly by purpose.
     * Globally disabled buttons would break the CMS.
     */
    public function performReadonlyTransformation()
    {
        $clone = clone $this;
        $clone->setReadonly(true);
        return $clone;
    }
}
