<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\DataObjectInterface;

/**
 * Field for displaying phone numbers. It separates the number, the area code and optionally the country code
 * and extension.
 */
class PhoneNumberField extends FieldGroup
{

    /**
     * Default area code
     *
     * @var string
     */
    protected $areaCode;

    /**
     * Default country code
     * @var string
     */
    protected $countryCode;

    /**
     * Default extension
     *
     * @var string
     */
    protected $ext;

    /**
     * @return NumericField
     */
    public function getCountryField()
    {
        return $this->getChildField('Country');
    }

    /**
     * @return NumericField
     */
    public function getAreaField()
    {
        return $this->getChildField('Area');
    }

    /**
     * @return NumericField
     */
    public function getNumberField()
    {
        return $this->getChildField('Number');
    }

    /**
     * @return NumericField
     */
    public function getExtensionField()
    {
        /** @skipUpgrade */
        return $this->getChildField('Extension');
    }

    protected function getChildField($name)
    {
        $endsWith = "[{$name}]";
        foreach ($this->getChildren() as $child) {

            /** @var Formfield $child */
            if (substr($child->getName(), 0 - strlen($endsWith)) === $endsWith) {
                return $child;
            }
        }
        return null;
    }

    public function __construct(
        $name,
        $title = null,
        $value = '',
        $extension = null,
        $areaCode = null,
        $countryCode = null
    ) {
        $this->areaCode = $areaCode;
        $this->ext = $extension;
        $this->countryCode = $countryCode;

        // Build fields
        $fields = new FieldList();
        if ($this->countryCode !== null) {
            $countryField = NumericField::create($name.'[Country]', false, $countryCode, 4)
                ->addExtraClass('phonenumber-field__country');
            $fields->push($countryField);
        }

        if ($this->areaCode !== null) {
            $areaField = NumericField::create($name.'[Area]', false, $areaCode, 4)
                ->addExtraClass('phonenumber-field__area');
            $fields->push($areaField);
        }
        $numberField = NumericField::create($name.'[Number]', false, null, 10)
            ->addExtraClass('phonenumber-field__number');
        $fields->push($numberField);

        if ($this->ext !== null) {
            $extensionField = NumericField::create($name.'[Extension]', false, $extension, 6)
                ->addExtraClass('phonenumber-field__extension');
            $fields->push($extensionField);
        }

        parent::__construct($title, $fields);

        $this->setName($name);
        if (isset($value)) {
            $this->setValue($value);
        }
    }

    public function setName($name)
    {
        parent::setName($name);
        foreach ($this->getChildren() as $child) {
            /** @var FormField $child */
            $thisName = $child->getName();
            $thisName = preg_replace('/^.*(\[\\w+\\])$/', $name . '\\1', $thisName);
            $child->setName($thisName);
        }
    }

    public function hasData()
    {
        return true;
    }

    /**
     * @param array $properties
     * @return string
     */
    public function Field($properties = array())
    {
        foreach ($this->getChildren() as $field) {
            /** @var FormField $field */
            $field->setDisabled($this->isDisabled());
            $field->setReadonly($this->IsReadonly());
            $field->setForm($this->getForm());
        }
        return parent::Field($properties);
    }

    public function setValue($value, $data = null)
    {
        $this->value = self::joinPhoneNumber($value);
        $parts = $this->parseValue();
        if ($countryField = $this->getCountryField()) {
            $countryField->setValue($parts['Country']);
        }
        if ($areaField = $this->getAreaField()) {
            $areaField->setValue($parts['Area']);
        }
        $this->getNumberField()->setValue($parts['Number']);
        if ($extensionField = $this->getExtensionField()) {
            /** @skipUpgrade */
            $extensionField->setValue($parts['Extension']);
        }
        return $this;
    }

    /**
     * Join phone number into a string
     *
     * @param array|string $value Input
     * @return string
     */
    public static function joinPhoneNumber($value)
    {
        if (is_array($value)) {
            $completeNumber = '';
            if (!empty($value['Country'])) {
                $completeNumber .= '+' . $value['Country'];
            }

            if (!empty($value['Area'])) {
                $completeNumber .= '(' . $value['Area'] . ')';
            }

            $completeNumber .= $value['Number'];

            /** @skipUpgrade */
            if (!empty($value['Extension'])) {
                $completeNumber .= '#' . $value['Extension'];
            }

            return $completeNumber;
        } else {
            return $value;
        }
    }

    /**
     * Returns array with parsed phone format
     *
     * @return array Array with Country, Area, Number, and Extension keys (in order)
     */
    protected function parseValue()
    {
        if (is_array($this->value)) {
            return $this->value;
        }
        // Parse value in form "+ countrycode (areacode) phone # extension"
        $valid = preg_match(
            '/^(?:(?:\+(?<Country>\d+))?\s*\((?<Area>\d+)\))?\s*(?<Number>[0-9A-Za-z]*)\s*(?:[#]\s*(?<Extension>\d+))?$/',
            $this->value,
            $parts
        );
        if (!$valid) {
            $parts = [];
        }
        /** @skipUpgrade */
        return array(
            'Country' => isset($parts['Country']) ? $parts['Country'] : '',
            'Area' => isset($parts['Area']) ? $parts['Area'] : '',
            'Number' => isset($parts['Number']) ? $parts['Number'] : '',
            'Extension' => isset($parts['Extension']) ? $parts['Extension'] : '',
        );
    }

    public function saveInto(DataObjectInterface $record)
    {
        $completeNumber = static::joinPhoneNumber($this->parseValue());
        $record->setCastedField($this->getName(), $completeNumber);
    }

    /**
     * Validate this field
     *
     * @todo Very basic validation at the moment
     *
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        $valid = preg_match(
            '/^[0-9\+\-\(\)\s\#]*$/',
            $this->joinPhoneNumber($this->value)
        );

        if (!$valid) {
            $validator->validationError(
                $this->name,
                _t('PhoneNumberField.VALIDATION', "Please enter a valid phone number"),
                "validation"
            );
            return false;
        }

        return true;
    }

    public function performReadonlyTransformation()
    {
        // Just setReadonly without casting to NumericField_Readonly
        $clone = clone $this;
        $clone->setReadonly(true);
        return $clone;
    }

    public function performDisabledTransformation()
    {
        // Just setDisabled without casting to NumericField_Disabled
        $clone = clone $this;
        $clone->setDisabled(true);
        return $clone;
    }
}
