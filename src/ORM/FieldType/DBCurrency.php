<?php

namespace SilverStripe\ORM\FieldType;

use SilverStripe\Forms\CurrencyField;

/**
 * Represents a decimal field containing a currency amount.
 * The currency class only supports single currencies.  For multi-currency support, use {@link Money}
 *
 *
 * Example definition via {@link DataObject::$db}:
 * <code>
 * static $db = array(
 *  "Price" => "Currency",
 *  "Tax" => "Currency(5)",
 * );
 * </code>
 */
class DBCurrency extends DBDecimal
{
    /**
     * @config
     * @var string
     */
    private static $currency_symbol = '$';

    public function __construct(string $name = null, $wholeSize = 9, $decimalSize = 2, $defaultValue = 0): void
    {
        parent::__construct($name, $wholeSize, $decimalSize, $defaultValue);
    }

    /**
     * Returns the number as a currency, eg “$1,000.00”.
     */
    public function Nice(): string
    {
        $val = $this->config()->currency_symbol . number_format(abs($this->value ?? 0.0) ?? 0.0, 2);
        if ($this->value < 0) {
            return "($val)";
        }

        return $val;
    }

    /**
     * Returns the number as a whole-number currency, eg “$1,000”.
     */
    public function Whole(): string
    {
        $val = $this->config()->currency_symbol . number_format(abs($this->value ?? 0.0) ?? 0.0, 0);
        if ($this->value < 0) {
            return "($val)";
        }
        return $val;
    }

    public function setValue(string|int|float $value, SilverStripe\Dev\Tests\CsvBulkLoaderTest\PlayerContract $record = null, bool $markChanged = true): SilverStripe\ORM\FieldType\DBCurrency
    {
        $matches = null;
        if (is_numeric($value)) {
            $this->value = $value;
        } elseif (preg_match('/-?\$?[0-9,]+(.[0-9]+)?([Ee][0-9]+)?/', $value ?? '', $matches)) {
            $this->value = str_replace(['$', ',', $this->config()->currency_symbol], '', $matches[0] ?? '');
        } else {
            $this->value = 0;
        }

        return $this;
    }

    /**
     * @param string $title
     * @param array $params
     *
     * @return CurrencyField
     */
    public function scaffoldFormField($title = null, $params = null): SilverStripe\Forms\CurrencyField
    {
        return CurrencyField::create($this->getName(), $title);
    }
}
