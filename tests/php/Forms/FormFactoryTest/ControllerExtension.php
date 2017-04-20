<?php

namespace SilverStripe\Forms\Tests\FormFactoryTest;

use SilverStripe\Control\Controller;
use SilverStripe\Core\Convert;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Versioned\Versioned;

/**
 * Provides versionable extensions to a controller / scaffolder
 */
class ControllerExtension extends Extension
{

    /**
     * Handlers for extra actions added by this extension
     *
     * @var array
     */
    private static $allowed_actions = [
        'publish',
        'preview',
    ];

    /**
     * Adds additional form actions
     *
     * @param FieldList  $actions
     * @param Controller $controller
     * @param string     $name
     * @param array      $context
     */
    public function updateFormActions(FieldList &$actions, Controller $controller, $name, $context = [])
    {
        // Add publish button if record is versioned
        if (empty($context['Record'])) {
            return;
        }
        $record = $context['Record'];
        if ($record->hasExtension(Versioned::class)) {
            $actions->push(new FormAction('publish', 'Publish'));
        }
    }

    /**
     * Adds extra fields to this form
     *
     * @param FieldList  $fields
     * @param Controller $controller
     * @param string     $name
     * @param array      $context
     */
    public function updateFormFields(FieldList &$fields, Controller $controller, $name, $context = [])
    {
        // Add preview link
        if (empty($context['Record'])) {
            return;
        }
        $record = $context['Record'];
        if ($record->hasExtension(Versioned::class)) {
            $link = $controller->Link('preview');
            $fields->unshift(
                new LiteralField(
                    "PreviewLink",
                    sprintf('<a href="%s" rel="external" target="_blank">Preview</a>', Convert::raw2att($link))
                )
            );
        }
    }

    public function publish($data, $form)
    {
        // noop
    }

    public function preview()
    {
        // noop
    }
}
