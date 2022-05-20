<?php

namespace SilverStripe\Forms\GridField;

use SilverStripe\Forms\Form;
use SilverStripe\View\ArrayData;
use SilverStripe\View\SSViewer;

class GridFieldImportButton extends AbstractGridFieldComponent implements GridField_HTMLProvider
{
    /**
     * Fragment to write the button to
     */
    protected $targetFragment;

    /**
     * Import form
     *
     * @var Form
     */
    protected $importForm;

    /**
     * @var string
     */
    protected $modalTitle = null;

    /**
     * URL for iframe
     *
     * @var string
     */
    protected $importIframe = null;

    /**
     * @param string $targetFragment The HTML fragment to write the button into
     */
    public function __construct($targetFragment = "after")
    {
        $this->targetFragment = $targetFragment;
    }

    /**
     * Place the export button in a <p> tag below the field
     *
     * @param GridField $gridField
     * @return array
     */
    public function getHTMLFragments($gridField)
    {
        $modalID = $gridField->ID() . '_ImportModal';

        // Check for form message prior to rendering form (which clears session messages)
        $form = $this->getImportForm();
        $hasMessage = $form && $form->getMessage();

        // Render modal
        $template = SSViewer::get_templates_by_class(static::class, '_Modal');
        $viewer = new ArrayData([
            'ImportModalTitle' => $this->getModalTitle(),
            'ImportModalID' => $modalID,
            'ImportIframe' => $this->getImportIframe(),
            'ImportForm' => $this->getImportForm(),
        ]);
        $modal = $viewer->renderWith($template)->forTemplate();

        // Build action button
        $button = new GridField_FormAction(
            $gridField,
            'import',
            _t('SilverStripe\\Forms\\GridField\\GridField.CSVIMPORT', 'Import CSV'),
            'import',
            null
        );
        $button
            ->addExtraClass('btn btn-secondary font-icon-upload btn--icon-large action_import')
            ->setForm($gridField->getForm())
            ->setAttribute('data-toggle', 'modal')
            ->setAttribute('aria-controls', $modalID)
            ->setAttribute('data-target', "#{$modalID}")
            ->setAttribute('data-modal', $modal);

        // If form has a message, trigger it to automatically open
        if ($hasMessage) {
            $button->setAttribute('data-state', 'open');
        }

        return [
            $this->targetFragment => $button->Field()
        ];
    }

    /**
     * export is an action button
     *
     * @param GridField $gridField
     * @return array
     */
    public function getActions($gridField)
    {
        return [];
    }

    /**
     * @return string
     */
    public function getModalTitle()
    {
        return $this->modalTitle;
    }

    /**
     * @param string $modalTitle
     * @return $this
     */
    public function setModalTitle($modalTitle)
    {
        $this->modalTitle = $modalTitle;
        return $this;
    }

    /**
     * @return Form
     */
    public function getImportForm()
    {
        return $this->importForm;
    }

    /**
     * @param Form $importForm
     * @return $this
     */
    public function setImportForm($importForm)
    {
        $this->importForm = $importForm;
        return $this;
    }

    /**
     * @return string
     */
    public function getImportIframe()
    {
        return $this->importIframe;
    }

    /**
     * @param string $importIframe
     * @return $this
     */
    public function setImportIframe($importIframe)
    {
        $this->importIframe = $importIframe;
        return $this;
    }
}
