<?php

namespace SilverStripe\Forms\DefaultCmsFields;

use SilverStripe\Forms\FieldList;
use SilverStripe\ORM\DataExtension;
use SilverStripe\ORM\DataObject;

/**
 * Class Extension
 *
 * Removal of scaffolded fields
 * this is useful for project setup where scaffolded fields are not needed
 * there are various options on how to remove scaffolded fields
 *
 * Note that this extension needs to be applied manually and order matters
 * you want to apply this extension after all scaffolded fields are present nut before your model level
 * fields customisation
 *
 * example config
 *
 *  DNADesign\Elemental\Models\BaseElement:
 *    extensions:
 *      defaultFields: SilverStripe\Forms\DefaultCmsFields\Extension
 *      field_removal:
 *        db-keep: # remove all db fields except Title and ShowTitle
 *          property: db
 *          type: keep
 *          fields:
 *            Title: true
 *            ShowTitle: true
 *        has-one-keep: # remove all has_one fields
 *          property: has_one
 *          type: keep
 *        many-many-remove: # remove LinkTracking, FileTracking and BackLinkTracking fields from many_many
 *          property: many_many
 *          type: remove
 *          fields:
 *            LinkTracking: true
 *            FileTracking: true
 *            BackLinkTracking: true
 *        extra-remove: # remove Settings field (not part of any static property)
 *          property: extra
 *          fields:
 *            Settings: true
 *
 * @property DataObject $owner
 * @package SilverStripe\Forms\DefaultCmsFields
 */
class Extension extends DataExtension
{
    const TYPE_KEEP = 'keep';
    const TYPE_REMOVE = 'remove';

    /**
     * Specify which default fields need to be kept and which should be removed
     * configuration is a collection of rules
     * each rules has consist of the following
     * - property (db, has_one, has_many, many_many or extra)
     * - type (keep or remove), this is not required when extra property is used
     * - fields (FieldName => Active) list of fields that the configuration applies to
     *
     * @config
     * @var array
     */
    private static $field_removal = [];

    /**
     * Extension point in @see DataObject::getCMSFields()
     *
     * @param FieldList $fields
     */
    public function updateCMSFields(FieldList $fields)
    {
        $this->removeFields($fields);
    }

    /**
     * @param FieldList $fields
     */
    private function removeFields(FieldList $fields)
    {
        $rules = $this->owner->config()->get('field_removal');

        if (!is_array($rules)) {
            return;
        }

        foreach ($rules as $rule) {
            if (!$this->validateRule($rule)) {
                continue;
            }

            $property = $rule['property'];
            $list = array_key_exists('fields', $rule) ? $rule['fields'] : [];
            $list = is_array($list) ? $list : [];

            if ($property === 'extra') {
                $this->removeExtraFields($list, $fields);

                continue;
            }

            $type = $rule['type'];
            $this->removeDbFields($property, $type, $list, $fields);
        }
    }

    /**
     * @param $property
     * @param $type
     * @param array $list
     * @param FieldList $fields
     */
    private function removeDbFields($property, $type, array $list, FieldList $fields)
    {
        $config = $this->owner->config()->get($property);

        if (!is_array($config)) {
            return;
        }

        $fieldsToRemove = array_keys($config);

        foreach ($fieldsToRemove as $fieldName) {
            $active = array_key_exists($fieldName, $list) && $list[$fieldName];
            $keep = $type === self::TYPE_KEEP && $active || $type === self::TYPE_REMOVE && !$active;

            if ($keep) {
                continue;
            }

            if ($property === 'has_one') {
                $fields->removeByName($fieldName . 'ID');
            }

            $fields->removeByName($fieldName);
        }
    }

    /**
     * @param array $list
     * @param FieldList $fields
     */
    private function removeExtraFields(array $list, FieldList $fields)
    {
        foreach ($list as $fieldName => $active) {
            if (!$active) {
                continue;
            }

            $fields->removeByName($fieldName);
        }
    }

    /**
     * @param mixed $rule
     * @return bool
     */
    private function validateRule($rule)
    {
        if (!is_array($rule)) {
            return false;
        }

        if (!array_key_exists('property', $rule)) {
            return false;
        }

        $property = $rule['property'];

        if ($property === 'extra') {
            return true;
        }

        if (!in_array($property, ['db', 'has_one', 'has_many', 'many_many'])) {
            return false;
        }

        if (!in_array($rule['type'], [self::TYPE_KEEP, self::TYPE_REMOVE])) {
            return false;
        }

        return true;
    }
}
