<?php

namespace SilverStripe\ORM\FieldType;

use NumberFormatter;
use SilverStripe\Forms\FormField;
use SilverStripe\Forms\MoneyField;
use SilverStripe\i18n\i18n;

/**
 * Provides storage of a localised money object in currency and amount components.
 * Currency codes should follow the ISO 4217 standard 3 digit code.
 */
class DBMoney extends DBComposite
{
    /**
     * @var string $locale
     */
    protected $locale = null;

    /**
     * @var array<string,string>
     */
    private static $composite_db = [
        'Currency' => 'Varchar(3)',
        'Amount' => 'Decimal(19,4)'
    ];

    /**
     * Get currency formatter
     *
     * @return NumberFormatter
     */
    public function getFormatter()
    {
        $locale = $this->getLocale();
        $currency = $this->getCurrency();
        if ($currency) {
            $locale .= '@currency=' . $currency;
        }
        return NumberFormatter::create($locale, NumberFormatter::CURRENCY);
    }

    /**
     * Get nicely formatted currency (based on current locale)
     *
     * @return string
     */
    public function Nice()
    {
        if (!$this->exists()) {
            return null;
        }
        $amount = $this->getAmount();
        $currency = $this->getCurrency();

        // Without currency, format as basic localised number
        $formatter = $this->getFormatter();
        if (!$currency) {
            return $formatter->format($amount);
        }

        // Localise currency
        return $formatter->formatCurrency($amount, $currency);
    }

    /**
     * Standard '0.00 CUR' format (non-localised)
     *
     * @return string
     */
    public function getValue()
    {
        if (!$this->exists()) {
            return null;
        }
        $amount = $this->getAmount();
        $currency = $this->getCurrency();
        if (empty($currency)) {
            return $amount;
        }
        return $amount . ' ' . $currency;
    }

    /**
     * @return string
     */
    public function getCurrency()
    {
        return $this->getField('Currency');
    }

    /**
     * @param string $currency
     * @param bool $markChanged
     * @return $this
     */
    public function setCurrency($currency, $markChanged = true)
    {
        $this->setField('Currency', $currency, $markChanged);
        return $this;
    }

    /**
     * @return float
     */
    public function getAmount()
    {
        return $this->getField('Amount');
    }

    /**
     * @param mixed $amount
     * @param bool $markChanged
     * @return $this
     */
    public function setAmount($amount, $markChanged = true)
    {
        // Retain nullability to mark this field as empty
        if (isset($amount)) {
            $amount = (float)$amount;
        }
        $this->setField('Amount', $amount, $markChanged);
        return $this;
    }

    /**
     * @return boolean
     */
    public function exists()
    {
        return is_numeric($this->getAmount());
    }

    /**
     * Determine if this has a non-zero amount
     *
     * @return bool
     */
    public function hasAmount()
    {
        $a = $this->getAmount();
        return (!empty($a) && is_numeric($a));
    }

    /**
     * @param string $locale
     * @return $this
     */
    public function setLocale($locale)
    {
        $this->locale = $locale;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocale()
    {
        return $this->locale ?: i18n::get_locale();
    }

    /**
     * Get currency symbol
     *
     * @return string
     */
    public function getSymbol()
    {
        return $this->getFormatter()->getSymbol(NumberFormatter::CURRENCY_SYMBOL);
    }

    /**
     * Returns a CompositeField instance used as a default
     * for form scaffolding.
     *
     * Used by {@link SearchContext}, {@link ModelAdmin}, {@link DataObject::scaffoldFormFields()}
     *
     * @param string $title Optional. Localized title of the generated instance
     * @param array $params
     * @return FormField
     */
    public function scaffoldFormField($title = null, $params = null)
    {
        return MoneyField::create($this->getName(), $title)
            ->setLocale($this->getLocale());
    }
}
