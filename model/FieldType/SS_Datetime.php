<?php

/**
 * Backwards-compatibility class to preserve the functioning of references to SS_Datetime::now()
 * @deprecated 4.0.0:5.0.0 Use SilverStripe\Model\FieldType\DBDatetime instead.
 */
class SS_Datetime extends SilverStripe\Model\FieldType\DBDatetime
{

	public function __construct($name = null) {
		self::deprecation_notice();
		parent::__construct($name);
	}

	public static function now() {
		self::deprecation_notice();
		return parent::now();
	}

	/**
	 * Mock the system date temporarily, which is useful for time-based unit testing.
	 * Use {@link clear_mock_now()} to revert to the current system date.
	 * Caution: This sets a fixed date that doesn't increment with time.
	 *
	 * @param SS_Datetime|string $datetime Either in object format, or as a SS_Datetime compatible string.
	 */
	public static function set_mock_now($datetime) {
		self::deprecation_notice();
		return parent::set_mock_now($datetime);
	}

	/**
	 * Clear any mocked date, which causes
	 * {@link Now()} to return the current system date.
	 */
	public static function clear_mock_now() {
		self::deprecation_notice();
		return parent::clear_mock_now();
	}

	public static function get_template_global_variables() {
		self::deprecation_notice();
		return parent::get_template_global_variables();
	}

	protected static function deprecation_notice() {
		Deprecation::notice('4.0', 'SS_Datetime is deprecated. Please use SilverStripe\Model\FieldType\DBDatetime instead.');
	}
}
