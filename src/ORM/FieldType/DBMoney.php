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
    protected ?string $locale = null;

    private static array $composite_db = [
        'Currency' => 'Varchar(3)',
        'Amount' => 'Decimal(19,4)'
    ];

    /**
     * Get currency formatter
     */
    public function getFormatter(): NumberFormatter
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
     */
    public function Nice(): string
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
     */
    public function getValue(): ?string
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

    public function getCurrency(): ?string
    {
        return $this->getField('Currency');
    }

    public function setCurrency(?string $currency, bool $markChanged = true): static
    {
        $this->setField('Currency', $currency, $markChanged);
        return $this;
    }

    public function getAmount(): ?float
    {
        return $this->getField('Amount');
    }

    public function setAmount(mixed $amount, bool $markChanged = true): static
    {
        // Retain nullability to mark this field as empty
        if (isset($amount)) {
            $amount = (float)$amount;
        }
        $this->setField('Amount', $amount, $markChanged);
        return $this;
    }

    public function exists(): bool
    {
        return is_numeric($this->getAmount());
    }

    /**
     * Determine if this has a non-zero amount
     */
    public function hasAmount(): bool
    {
        $a = $this->getAmount();
        return (!empty($a) && is_numeric($a));
    }

    public function setLocale(string $locale): static
    {
        $this->locale = $locale;
        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale ?: i18n::get_locale();
    }

    /**
     * Get currency symbol
     */
    public function getSymbol(): string
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
     */
    public function scaffoldFormField(?string $title = null, array $params = []): ?FormField
    {
        return MoneyField::create($this->getName(), $title)
            ->setLocale($this->getLocale());
    }
}
