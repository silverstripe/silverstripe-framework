<?php

namespace SilverStripe\Forms\Schema;

use InvalidArgumentException;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\Form;
use SilverStripe\Forms\FormField;
use SilverStripe\ORM\ValidationResult;
use LogicException;

/**
 * Represents a {@link Form} as structured data which allows a frontend library to render it.
 * Includes information about the form as well as its fields.
 * Can create a "schema" (structure only) as well as "state" (data only).
 */
class FormSchema
{
    use Injectable;

    /**
     * Request the schema part
     */
    const PART_SCHEMA = 'schema';

    /**
     * Request the state part
     */
    const PART_STATE = 'state';

    /**
     * Request the errors from a {@see ValidationResult}
     */
    const PART_ERRORS = 'errors';

    /**
     * Request errors if invalid, or state if valid
     */
    const PART_AUTO = 'auto';

    /**
     * Returns a representation of the provided {@link Form} as structured data,
     * based on the request data.
     *
     * @param array|string $schemaParts Array or list of requested parts.
     * @param string $schemaID ID for this schema. Required.
     * @param Form $form Required for 'state' or 'schema' response
     * @param ValidationResult $result Required for 'error' response
     * @return array
     */
    public function getMultipartSchema($schemaParts, $schemaID, Form $form = null, ValidationResult $result = null)
    {
        if (!is_array($schemaParts)) {
            $schemaParts = preg_split('#\s*,\s*#', $schemaParts ?? '') ?: [];
        }
        $wantSchema = in_array('schema', $schemaParts ?? []);
        $wantState = in_array('state', $schemaParts ?? []);
        $wantErrors = in_array('errors', $schemaParts ?? []);
        $auto = in_array('auto', $schemaParts ?? []);

        // Require ID
        if (empty($schemaID)) {
            throw new InvalidArgumentException("schemaID is required");
        }
        $return = ['id' => $schemaID];

        // Default to schema if not set
        if ($form && ($wantSchema || empty($schemaParts) || $auto)) {
            $return['schema'] = $this->getSchema($form);
        }

        // Return 'state' if requested, or if there are errors and 'auto'
        if ($form && ($wantState || ($auto && !$result))) {
            $return['state'] = $this->getState($form);
        }

        // Return errors if 'errors' or 'auto'
        if ($result && ($wantErrors || $auto)) {
            $return['errors'] = $this->getErrors($result);
        }

        return $return;
    }

    /**
     * Gets the schema for this form as a nested array.
     *
     * @param Form $form
     * @return array
     */
    public function getSchema(Form $form)
    {
        $schema = [
            'name' => $form->getName(),
            'id' => $form->FormName(),
            'action' => $form->FormAction(),
            'method' => $form->FormMethod(),
            'attributes' => $form->getAttributes(),
            'data' => [],
            'fields' => [],
            'actions' => []
        ];

        foreach ($form->Actions() as $action) {
            $schema['actions'][] = $action->getSchemaData();
        }

        foreach ($form->Fields() as $field) {
            $schema['fields'][] = $field->getSchemaData();
        }

        // Validate there are react components for all fields
        // Note 'actions' (FormActions) are always valid because FormAction.schemaComponent has a default value
        $this->recursivelyValidateSchemaData($schema['fields']);

        return $schema;
    }

    private function recursivelyValidateSchemaData(array $schemaData)
    {
        foreach ($schemaData as $data) {
            if (!$data['schemaType'] && !$data['component']) {
                $name = $data['name'];
                $message = "Could not find a react component for field \"$name\"."
                    . "Replace or remove the field instance from the field list,"
                    . ' or update the field class and set the schemaDataType or schemaComponent property.';
                throw new LogicException($message);
            }
            if (array_key_exists('children', $data)) {
                $this->recursivelyValidateSchemaData($data['children']);
            }
        }
    }

    /**
     * Gets the current state of this form as a nested array.
     *
     * @param Form $form
     * @return array
     */
    public function getState(Form $form)
    {
        $state = [
            'id' => $form->FormName(),
            'fields' => [],
            'messages' => [],
            'notifyUnsavedChanges' => $form->getNotifyUnsavedChanges(),
        ];

        // flattened nested fields are returned, rather than only top level fields.
        $state['fields'] = array_merge(
            $this->getFieldStates($form->Fields()),
            $this->getFieldStates($form->Actions())
        );

        if ($message = $form->getSchemaMessage()) {
            $state['messages'][] = $message;
        }

        return $state;
    }

    /**
     * @param ValidationResult $result
     * @return array List of errors
     */
    public function getErrors(ValidationResult $result)
    {
        $messages = [];
        foreach ($result->getMessages() as $message) {
            $messages[] = $this->getSchemaForMessage($message);
        }
        return $messages;
    }

    /**
     * Return form schema for encoded validation message
     *
     * @param array $message Internal ValidationResult format for this message
     * @return array Form schema format for this message
     */
    protected function getSchemaForMessage($message)
    {
        // Form schema messages treat simple strings as plain text, so nest for html messages
        $value = $message['message'];
        if ($message['messageCast'] === ValidationResult::CAST_HTML) {
            $value = ['html' => $message];
        }
        return [
            'value' => $value,
            'type' => $message['messageType'],
            'field' => empty($message['fieldName']) ? null : $message['fieldName'],
        ];
    }

    /**
     * @param iterable<FormField> $fields
     */
    protected function getFieldStates($fields)
    {
        $states = [];
        foreach ($fields as $field) {
            $states[] = $field->getSchemaState();

            if ($field instanceof CompositeField) {
                $subFields = $field->FieldList();
                $states = array_merge($states, $this->getFieldStates($subFields));
            }
        }
        return $states;
    }
}
