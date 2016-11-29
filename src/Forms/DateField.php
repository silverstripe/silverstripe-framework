<?php

namespace SilverStripe\Forms;

use SilverStripe\Core\Convert;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\i18n\i18n;
use InvalidArgumentException;
use Zend_Locale;
use Zend_Date;

require_once 'Zend/Date.php';

/**
 * Form field to display an editable date string,
 * either in a single `<input type="text">` field,
 * or in three separate fields for day, month and year.
 *
 * # Configuration
 *
 * - 'showcalendar' (boolean): Determines if a calendar picker is shown.
 *    By default, jQuery UI datepicker is used (see {@link DateField_View_JQuery}).
 * - 'jslocale' (string): Overwrites the "Locale" value set in this class.
 *    Only useful in combination with {@link DateField_View_JQuery}.
 * - 'dmyfields' (boolean): Show three input fields for day, month and year separately.
 *    CAUTION: Might not be useable in combination with 'showcalendar', depending on the used javascript library
 * - 'dmyseparator' (string): HTML markup to separate day, month and year fields.
 *    Only applicable with 'dmyfields'=TRUE. Use 'dateformat' to influence date representation with 'dmyfields'=FALSE.
 * - 'dmyplaceholders': Show HTML5 placehoder text to allow identification of the three separate input fields
 * - 'dateformat' (string): Date format compatible with Zend_Date.
 *    Usually set to default format for {@link locale} through {@link Zend_Locale_Format::getDateFormat()}.
 * - 'datavalueformat' (string): Internal ISO format string used by {@link dataValue()} to save the
 *    date to a database.
 * - 'min' (string): Minimum allowed date value (in ISO format, or strtotime() compatible).
 *    Example: '2010-03-31', or '-7 days'
 * - 'max' (string): Maximum allowed date value (in ISO format, or strtotime() compatible).
 *    Example: '2010-03-31', or '1 year'
 *
 * Depending which UI helper is used, further namespaced configuration options are available.
 * For the default jQuery UI, all options prefixed/namespaced with "jQueryUI." will be respected as well.
 * Example: <code>$myDateField->setConfig('jQueryUI.showWeek', true);</code>
 * See http://docs.jquery.com/UI/Datepicker for details.
 *
 * Caution: The form field does not include any JavaScript or CSS when used outside of the CMS context,
 * since the required frontend dependencies are included through CMS bundling.
 *
 * # Localization
 *
 * The field will get its default locale from {@link i18n::get_locale()}, and set the `dateformat`
 * configuration accordingly. Changing the locale through {@link setLocale()} will not update the
 * `dateformat` configuration automatically.
 *
 * See http://doc.silverstripe.org/framework/en/topics/i18n for more information about localizing form fields.
 *
 * # Usage
 *
 * ## Example: German dates with separate fields for day, month, year
 *
 *   $f = new DateField('MyDate');
 *   $f->setLocale('de_DE');
 *   $f->setConfig('dmyfields', true);
 *
 * # Validation
 *
 * Caution: JavaScript validation is only supported for the 'en_NZ' locale at the moment,
 * it will be disabled automatically for all other locales.
 */
class DateField extends TextField
{

    protected $schemaDataType = FormField::SCHEMA_DATA_TYPE_DATE;

    /**
     * @config
     * @var array
     */
    private static $default_config = array(
        'showcalendar' => false,
        'jslocale' => null,
        'dmyfields' => false,
        'dmyseparator' => '&nbsp;<span class="separator">/</span>&nbsp;',
        'dmyplaceholders' => true,
        'dateformat' => null,
        'datavalueformat' => 'yyyy-MM-dd',
        'min' => null,
        'max' => null,
    );

    /**
     * @var array
     */
    protected $config;

    /**
     * @var String
     */
    protected $locale = null;

    /**
     * @var Zend_Date Just set if the date is valid.
     * {@link $value} will always be set to aid validation,
     * and might contain invalid values.
     */
    protected $valueObj = null;

    public function __construct($name, $title = null, $value = null)
    {
        if (!$this->locale) {
            $this->locale = i18n::get_locale();
        }

        $this->config = $this->config()->default_config;
        if (!$this->getConfig('dateformat')) {
            $this->setConfig('dateformat', i18n::config()->get('date_format'));
        }

        foreach ($this->config()->default_config as $defaultK => $defaultV) {
            if ($defaultV) {
                if ($defaultK=='locale') {
                    $this->locale = $defaultV;
                } else {
                    $this->setConfig($defaultK, $defaultV);
                }
            }
        }

        parent::__construct($name, $title, $value);
    }

    public function FieldHolder($properties = array())
    {
        if ($this->getConfig('showcalendar')) {
            // TODO Replace with properly extensible view helper system
            $d = DateField_View_JQuery::create($this);
            if (!$d->regionalSettingsExist()) {
                $dateformat = $this->getConfig('dateformat');

                // if no localefile is present, the jQuery DatePicker
                // month- and daynames will default to English, so the date
                // will not pass Zend validatiobn. We provide a fallback
                if (preg_match('/(MMM+)|(EEE+)/', $dateformat)) {
                    $this->setConfig('dateformat', $this->getConfig('datavalueformat'));
                }
            }
            $d->onBeforeRender();
        }
        $html = parent::FieldHolder();

        if (!empty($d)) {
            $html = $d->onAfterRender($html);
        }
        return $html;
    }

    function SmallFieldHolder($properties = array())
    {
        $d = DateField_View_JQuery::create($this);
        $d->onBeforeRender();
        $html = parent::SmallFieldHolder($properties);
        $html = $d->onAfterRender($html);
        return $html;
    }

    public function Field($properties = array())
    {
        $config = array(
            'showcalendar' => $this->getConfig('showcalendar'),
            'isoDateformat' => $this->getConfig('dateformat'),
            'jquerydateformat' => DateField_View_JQuery::convert_iso_to_jquery_format($this->getConfig('dateformat')),
            'min' => $this->getConfig('min'),
            'max' => $this->getConfig('max')
        );

        // Add other jQuery UI specific, namespaced options (only serializable, no callbacks etc.)
        // TODO Move to DateField_View_jQuery once we have a properly extensible HTML5 attribute system for FormField
        $jqueryUIConfig = array();
        foreach ($this->getConfig() as $k => $v) {
            if (preg_match('/^jQueryUI\.(.*)/', $k, $matches)) {
                $jqueryUIConfig[$matches[1]] = $v;
            }
        }
        if ($jqueryUIConfig) {
            $config['jqueryuiconfig'] =  Convert::array2json(array_filter($jqueryUIConfig));
        }
        $config = array_filter($config);
        foreach ($config as $k => $v) {
            $this->setAttribute('data-' . $k, $v);
        }

        // Three separate fields for day, month and year
        if ($this->getConfig('dmyfields')) {
            // values
            $valArr = ($this->valueObj) ? $this->valueObj->toArray() : null;

            // fields
            $fieldNames = Zend_Locale::getTranslationList('Field', $this->locale);
            $fieldDay = NumericField::create($this->name . '[day]', false, ($valArr) ? $valArr['day'] : null)
                ->addExtraClass('day')
                ->setAttribute('placeholder', $this->getConfig('dmyplaceholders') ? $fieldNames['day'] : null)
                ->setMaxLength(2);

            $fieldMonth = NumericField::create($this->name . '[month]', false, ($valArr) ? $valArr['month'] : null)
                ->addExtraClass('month')
                ->setAttribute('placeholder', $this->getConfig('dmyplaceholders') ? $fieldNames['month'] : null)
                ->setMaxLength(2);

            $fieldYear = NumericField::create($this->name . '[year]', false, ($valArr) ? $valArr['year'] : null)
                ->addExtraClass('year')
                ->setAttribute('placeholder', $this->getConfig('dmyplaceholders') ? $fieldNames['year'] : null)
                ->setMaxLength(4);

            // order fields depending on format
            $sep = $this->getConfig('dmyseparator');
            $format = $this->getConfig('dateformat');
            $fields = array();
            $fields[stripos($format, 'd')] = $fieldDay->Field();
            $fields[stripos($format, 'm')] = $fieldMonth->Field();
            $fields[stripos($format, 'y')] = $fieldYear->Field();
            ksort($fields);
            $html = implode($sep, $fields);

            // dmyfields doesn't work with showcalendar
            $this->setConfig('showcalendar', false);
        } // Default text input field
        else {
            $html = parent::Field();
        }

        return $html;
    }

    public function Type()
    {
        return 'date text';
    }

    /**
     * Sets the internal value to ISO date format.
     *
     * @param mixed $val
     * @return $this
     */
    public function setValue($val)
    {
        $locale = new Zend_Locale($this->locale);

        if (empty($val)) {
            $this->value = null;
            $this->valueObj = null;
        } else {
            if ($this->getConfig('dmyfields')) {
                // Setting in correct locale
                if (is_array($val) && $this->validateArrayValue($val)) {
                    // set() gets confused with custom date formats when using array notation
                    if (!(empty($val['day']) || empty($val['month']) || empty($val['year']))) {
                        $this->valueObj = new Zend_Date($val, null, $locale);
                        $this->value = $this->valueObj->toArray();
                    } else {
                        $this->value = $val;
                        $this->valueObj = null;
                    }
                } // load ISO date from database (usually through Form->loadDataForm())
                elseif (!empty($val) && Zend_Date::isDate($val, $this->getConfig('datavalueformat'), $locale)) {
                    $this->valueObj = new Zend_Date($val, $this->getConfig('datavalueformat'), $locale);
                    $this->value = $this->valueObj->toArray();
                } else {
                    $this->value = $val;
                    $this->valueObj = null;
                }
            } else {
                // Setting in correct locale.
                // Caution: Its important to have this check *before* the ISO date fallback,
                // as some dates are falsely detected as ISO by isDate(), e.g. '03/04/03'
                // (en_NZ for 3rd of April, definetly not yyyy-MM-dd)
                if (!empty($val) && Zend_Date::isDate($val, $this->getConfig('dateformat'), $locale)) {
                    $this->valueObj = new Zend_Date($val, $this->getConfig('dateformat'), $locale);
                    $this->value = $this->valueObj->get($this->getConfig('dateformat'), $locale);
                } // load ISO date from database (usually through Form->loadDataForm())
                elseif (!empty($val) && Zend_Date::isDate($val, $this->getConfig('datavalueformat'))) {
                    $this->valueObj = new Zend_Date($val, $this->getConfig('datavalueformat'));
                    $this->value = $this->valueObj->get($this->getConfig('dateformat'), $locale);
                } else {
                    $this->value = $val;
                    $this->valueObj = null;
                }
            }
        }

        return $this;
    }

    /**
     * @return String ISO 8601 date, suitable for insertion into database
     */
    public function dataValue()
    {
        if ($this->valueObj) {
            return $this->valueObj->toString($this->getConfig('datavalueformat'));
        } else {
            return null;
        }
    }

    public function performReadonlyTransformation()
    {
        $field = $this->castedCopy('SilverStripe\\Forms\\DateField_Disabled');
        $field->setValue($this->dataValue());
        $field->readonly = true;

        return $field;
    }

    /**
     * @param mixed $class
     * @return FormField
     */
    public function castedCopy($class)
    {
        /** @var FormField $copy */
        $copy = Injector::inst()->create($class, $this->name);
        if ($copy->hasMethod('setConfig')) {
            /** @var DateField $copy */
            $config = $this->getConfig();
            foreach ($config as $k => $v) {
                $copy->setConfig($k, $v);
            }
        }

        return parent::castedCopy($copy);
    }

    /**
     * Validate an array with expected keys 'day', 'month' and 'year.
     * Used because Zend_Date::isDate() doesn't provide this.
     *
     * @param array $val
     * @return bool
     */
    public function validateArrayValue($val)
    {
        if (!is_array($val)) {
            return false;
        }

        // Validate against Zend_Date,
        // but check for empty array keys (they're included in standard form submissions)
        return (
            array_key_exists('year', $val)
            && (!$val['year'] || Zend_Date::isDate($val['year'], 'yyyy', $this->locale))
            && array_key_exists('month', $val)
            && (!$val['month'] || Zend_Date::isDate($val['month'], 'MM', $this->locale))
            && array_key_exists('day', $val)
            && (!$val['day'] || Zend_Date::isDate($val['day'], 'dd', $this->locale))
        );
    }

    /**
     * @param Validator $validator
     * @return bool
     */
    public function validate($validator)
    {
        // Don't validate empty fields
        if (empty($this->value)) {
            return true;
        }

        // date format
        if ($this->getConfig('dmyfields')) {
            $valid = (!$this->value || $this->validateArrayValue($this->value));
        } else {
            $valid = (Zend_Date::isDate($this->value, $this->getConfig('dateformat'), $this->locale));
        }
        if (!$valid) {
            $validator->validationError(
                $this->name,
                _t(
                    'DateField.VALIDDATEFORMAT2',
                    "Please enter a valid date format ({format})",
                    array('format' => $this->getConfig('dateformat'))
                ),
                "validation"
            );
            return false;
        }

        // min/max - Assumes that the date value was valid in the first place
        if ($min = $this->getConfig('min')) {
            // ISO or strtotime()
            if (Zend_Date::isDate($min, $this->getConfig('datavalueformat'))) {
                $minDate = new Zend_Date($min, $this->getConfig('datavalueformat'));
            } else {
                $minDate = new Zend_Date(strftime('%Y-%m-%d', strtotime($min)), $this->getConfig('datavalueformat'));
            }
            if (!$this->valueObj || (!$this->valueObj->isLater($minDate) && !$this->valueObj->equals($minDate))) {
                $validator->validationError(
                    $this->name,
                    _t(
                        'DateField.VALIDDATEMINDATE',
                        "Your date has to be newer or matching the minimum allowed date ({date})",
                        array('date' => $minDate->toString($this->getConfig('dateformat')))
                    ),
                    "validation"
                );
                return false;
            }
        }
        if ($max = $this->getConfig('max')) {
            // ISO or strtotime()
            if (Zend_Date::isDate($min, $this->getConfig('datavalueformat'))) {
                $maxDate = new Zend_Date($max, $this->getConfig('datavalueformat'));
            } else {
                $maxDate = new Zend_Date(strftime('%Y-%m-%d', strtotime($max)), $this->getConfig('datavalueformat'));
            }
            if (!$this->valueObj || (!$this->valueObj->isEarlier($maxDate) && !$this->valueObj->equals($maxDate))) {
                $validator->validationError(
                    $this->name,
                    _t(
                        'DateField.VALIDDATEMAXDATE',
                        "Your date has to be older or matching the maximum allowed date ({date})",
                        array('date' => $maxDate->toString($this->getConfig('dateformat')))
                    ),
                    "validation"
                );
                return false;
            }
        }

        return true;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale;
    }

    /**
     * Caution: Will not update the 'dateformat' config value.
     *
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @param string $name
     * @param mixed $val
     * @return $this
     */
    public function setConfig($name, $val)
    {
        switch ($name) {
            case 'min':
                $format = $this->getConfig('datavalueformat');
                if ($val && !Zend_Date::isDate($val, $format) && !strtotime($val)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Date "%s" is not a valid minimum date format (%s) or strtotime() argument',
                            $val,
                            $format
                        )
                    );
                }
                break;
            case 'max':
                $format = $this->getConfig('datavalueformat');
                if ($val && !Zend_Date::isDate($val, $format) && !strtotime($val)) {
                    throw new InvalidArgumentException(
                        sprintf(
                            'Date "%s" is not a valid maximum date format (%s) or strtotime() argument',
                            $val,
                            $format
                        )
                    );
                }
                break;
        }

        $this->config[$name] = $val;
        return $this;
    }

    /**
     * @param String $name Optional, returns the whole configuration array if empty
     * @return mixed|array
     */
    public function getConfig($name = null)
    {
        if ($name) {
            return isset($this->config[$name]) ? $this->config[$name] : null;
        } else {
            return $this->config;
        }
    }

    public function getSchemaValidation()
    {
        $rules = parent::getSchemaValidation();
        $rules['date'] = true;
        return $rules;
    }
}
