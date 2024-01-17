<?php

namespace SilverStripe\ORM;

use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Extension;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\CompositeValidator;
use SilverStripe\ORM\Queries\SQLSelect;
use SilverStripe\Security\Member;
use Exception;

/**
 * An extension that adds additional functionality to a {@link DataObject}.
 *
 * @template T of DataObject
 * @extends Extension<T>
 */
abstract class DataExtension extends Extension
{

    /**
     * Hook for extension-specific validation.
     *
     * @param ValidationResult $validationResult Local validation result
     * @throws ValidationException
     */
    public function validate(ValidationResult $validationResult)
    {
    }

    /**
     * Edit the given query object to support queries for this extension
     *
     * @param SQLSelect $query Query to augment.
     * @param DataQuery $dataQuery Container DataQuery for this SQLSelect
     */
    public function augmentSQL(SQLSelect $query, DataQuery $dataQuery = null)
    {
    }

    /**
     * Update the database schema as required by this extension.
     *
     * When duplicating a table's structure, remember to duplicate the create options
     * as well. See {@link Versioned->augmentDatabase} for an example.
     */
    public function augmentDatabase()
    {
    }

    /**
     * Augment a write-record request.
     *
     * @param array $manipulation Array of operations to augment.
     */
    public function augmentWrite(&$manipulation)
    {
    }

    /**
     * Extend the owner's onBeforeWrite() logic
     *
     * See {@link DataObject::onBeforeWrite()} for context.
     */
    public function onBeforeWrite()
    {
    }

    /**
     * Extend the owner's onAfterWrite() logic
     *
     * See {@link DataObject::onAfterWrite()} for context.
     */
    public function onAfterWrite()
    {
    }

    /**
     * Extend the owner's onBeforeDelete() logic
     *
     * See {@link DataObject::onBeforeDelete()} for context.
     */
    public function onBeforeDelete()
    {
    }

    /**
     * Extend the owner's onAfterDelete() logic
     *
     * See {@link DataObject::onAfterDelete()} for context.
     */
    public function onAfterDelete()
    {
    }

    /**
     * Extend the owner's requireDefaultRecords() logic
     *
     * See {@link DataObject::requireDefaultRecords()} for context.
     */
    public function requireDefaultRecords()
    {
    }

    /**
     * Extend the owner's populateDefaults() logic
     *
     * See {@link DataObject::populateDefaults()} for context.
     */
    public function populateDefaults()
    {
    }

    /**
     * Extend the owner's onAfterBuild() logic
     *
     * See {@link DataObject::onAfterBuild()} for context.
     */
    public function onAfterBuild()
    {
    }

    /**
     * Influence the owner's can() permission check value to be disallowed (false),
     * allowed (true) if no other processed results are to disallow, or open (null) to not
     * affect the outcome.
     *
     * See {@link DataObject::can()} and {@link DataObject::extendedCan()} for context.
     *
     * @param Member $member
     * @return bool|null
     */
    public function can($member)
    {
    }

    /**
     * Influence the owner's canEdit() permission check value to be disallowed (false),
     * allowed (true) if no other processed results are to disallow, or open (null) to not
     * affect the outcome.
     *
     * See {@link DataObject::canEdit()} and {@link DataObject::extendedCan()} for context.
     *
     * @param Member $member
     * @return bool|null
     */
    public function canEdit($member)
    {
    }

    /**
     * Influence the owner's canDelete() permission check value to be disallowed (false),
     * allowed (true) if no other processed results are to disallow, or open (null) to not
     * affect the outcome.
     *
     * See {@link DataObject::canDelete()} and {@link DataObject::extendedCan()} for context.
     *
     * @param Member $member
     * @return bool|null
     */
    public function canDelete($member)
    {
    }

    /**
     * Influence the owner's canCreate() permission check value to be disallowed (false),
     * allowed (true) if no other processed results are to disallow, or open (null) to not
     * affect the outcome.
     *
     * See {@link DataObject::canCreate()} and {@link DataObject::extendedCan()} for context.
     *
     * @param Member $member
     * @return bool|null
     */
    public function canCreate($member)
    {
    }

    /**
     * Define extra database fields
     *
     * Return a map where the keys are db, has_one, etc, and the values are
     * additional fields/relations to be defined.
     *
     * @param string $class since this method might be called on the class directly
     * @param string $extension since this can help to extract parameters to help set indexes
     * @return array Returns a map where the keys are db, has_one, etc, and
     *               the values are additional fields/relations to be defined.
     */
    public function extraStatics($class = null, $extension = null)
    {
        return [];
    }

    /**
     * This function is used to provide modifications to the form in the CMS
     * by the extension. By default, no changes are made. {@link DataObject->getCMSFields()}.
     *
     * Please consider using {@link updateFormFields()} to globally add
     * formfields to the record. The method {@link updateCMSFields()}
     * should just be used to add or modify tabs, or fields which
     * are specific to the CMS-context.
     *
     * Caution: Use {@link FieldList->addFieldToTab()} to add fields.
     *
     * @param FieldList $fields FieldList with a contained TabSet
     */
    public function updateCMSFields(FieldList $fields)
    {
    }

    /**
     * This function is used to provide modifications to the Validators used on a DataObject.
     *
     * Caution: Use {@link CompositeValidator->addValidator()} to add Validators.
     *
     * @param CompositeValidator $compositeValidator
     */
    public function updateCMSCompositeValidator(CompositeValidator $compositeValidator): void
    {
    }

    /**
     * This function is used to provide modifications to the form used
     * for front end forms. {@link DataObject->getFrontEndFields()}
     *
     * Caution: Use {@link FieldList->push()} to add fields.
     *
     * @param FieldList $fields FieldList without TabSet nesting
     */
    public function updateFrontEndFields(FieldList $fields)
    {
    }

    /**
     * This is used to provide modifications to the form actions
     * used in the CMS. {@link DataObject->getCMSActions()}.
     *
     * @param FieldList $actions FieldList
     */
    public function updateCMSActions(FieldList $actions)
    {
    }

    /**
     * this function is used to provide modifications to the summary fields in CMS
     * by the extension
     * By default, the summaryField() of its owner will merge more fields defined in the extension's
     * $extra_fields['summary_fields']
     *
     * @param array $fields Array of field names
     */
    public function updateSummaryFields(&$fields)
    {
        $summary_fields = Config::inst()->get(static::class, 'summary_fields');
        if ($summary_fields) {
            // if summary_fields were passed in numeric array,
            // convert to an associative array
            if ($summary_fields && array_key_exists(0, $summary_fields ?? [])) {
                $summary_fields = array_combine(array_values($summary_fields ?? []), array_values($summary_fields ?? []));
            }
            if ($summary_fields) {
                $fields = array_merge($fields, $summary_fields);
            }
        }
    }

    /**
     * this function is used to provide modifications to the fields labels in CMS
     * by the extension
     * By default, the fieldLabels() of its owner will merge more fields defined in the extension's
     * $extra_fields['field_labels']
     *
     * @param array $labels Array of field labels
     */
    public function updateFieldLabels(&$labels)
    {
        $field_labels = Config::inst()->get(static::class, 'field_labels');
        if ($field_labels) {
            $labels = array_merge($labels, $field_labels);
        }
    }
}
