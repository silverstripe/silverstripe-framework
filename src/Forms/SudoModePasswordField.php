<?php

namespace SilverStripe\Forms;

use SilverStripe\Forms\PasswordField;

class SudoModePasswordField extends PasswordField
{
    public const FIELD_NAME = 'SudoModePasswordField';
    
    protected $schemaComponent = 'SudoModePasswordField';

    private bool $initiallyCollapsed = false;

    private bool $forGridField = false;

    private string $sectionTitle = '';

    public function __construct()
    {
        // Name must be "SudoModePasswordField" as there's logic elsewhere expecting this
        // $title and $value are set to null as the react component does not use these arguments
        // append a uniqid() ID to the field name for when running yarn dev / yarn watch which will
        // put the ID of the field in the global js namespace, thus making our webpack expose loader
        // unable to put the SudoModePasswordField component in the global namespace
        parent::__construct(SudoModePasswordField::FIELD_NAME . '-' . uniqid());
        // Set title to empty string to avoid rendering a label before the react component has loaded
        $this->setTitle('');
        $this->addExtraClass('SudoModePasswordField no-change-track');
    }

    public function performReadonlyTransformation()
    {
        // Readonly transformation should not be applied to this field
        // as this field is intended to be used on a form that has been set to read only mode
        return $this;
    }

    public function extraClass()
    {
        $arr = [parent::extraClass()];
        if ($this->initiallyCollapsed) {
            $arr[] = SudoModePasswordField::FIELD_NAME . '--initially-collapsed';
        }
        return implode(' ', $arr);
    }

    /**
     * Get whether the field should be collapsed when initially rendered
     */
    public function getInitiallyCollapsed(): bool
    {
        return $this->initiallyCollapsed;
    }

    /**
     * Set whether the field should be collapsed when initially rendered
     *
     * When collapsed the rendered component will include a way to expand the field
     * When not collapsed the rendered component will not be collapsible
     */
    public function setInitiallyCollapsed(bool $initiallyCollapsed): static
    {
        $this->initiallyCollapsed = $initiallyCollapsed;
        return $this;
    }

    /**
     * Get whether the field is being used in a GridField
     */
    public function getForGridField(): bool
    {
        return $this->forGridField;
    }

    /**
     * Set whether the field is being used in a GridField
     */
    public function setForGridField(bool $forGridField): static
    {
        $this->forGridField = $forGridField;
        return $this;
    }

    /**
     * Set the title of the section that the field is in
     */
    public function setSectionTitle(string $sectionTitle): static
    {
        $this->sectionTitle = $sectionTitle;
        return $this;
    }

    /**
     * Get the title of the section that the field is in
     */
    public function getSectionTitle(): string
    {
        return $this->sectionTitle;
    }

    public function getAttributes()
    {
        return array_merge(
            parent::getAttributes(),
            [
                'data-initially-collapsed' => $this->getInitiallyCollapsed(),
                'data-for-gridfield' => $this->getForGridField(),
                'data-section-title' => $this->getSectionTitle(),
            ]
        );
    }
}
