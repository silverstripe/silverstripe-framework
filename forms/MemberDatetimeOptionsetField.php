<?php
/**
 * @package framework
 * @subpackage security
 */

use SilverStripe\Model\ArrayList;
class MemberDatetimeOptionsetField extends OptionsetField {

	const CUSTOM_OPTION = '__custom__';

	/**
	 * Non-ambiguous date to use for the preview.
	 * Must be in 'y-MM-dd HH:mm:ss' format
	 *
	 * @var string
	 */
	private static $preview_date = '25-12-2011 17:30:00';

	public function Field($properties = array()) {
		Requirements::javascript(FRAMEWORK_ADMIN_DIR . '/client/dist/js/MemberDatetimeOptionsetField.js');
		$options = array();
		$odd = false;

		// Add all options striped
		$anySelected = false;
		foreach($this->getSourceEmpty() as $value => $title) {
			$odd = !$odd;
			if(!$anySelected) {
				$anySelected = $this->isSelectedValue($value, $this->Value());
			}
			$options[] = $this->getFieldOption($value, $title, $odd);
		}

		// Add "custom" input field option
		$options[] = $this->getCustomFieldOption(!$anySelected, !$odd);

		// Build fieldset
		$properties = array_merge($properties, array(
			'Options' => new ArrayList($options)
		));

		return $this->customise($properties)->renderWith(
			$this->getTemplates()
		);
	}

	/**
	 * Create the "custom" selection field option
	 *
	 * @param bool $isChecked True if this is checked
	 * @param bool $odd Is odd striped
	 * @return ArrayData
	 */
	protected function getCustomFieldOption($isChecked, $odd) {
		// Add "custom" input field
		$option = $this->getFieldOption(
			self::CUSTOM_OPTION,
			_t('MemberDatetimeOptionsetField.Custom', 'Custom'),
			$odd
		);
		$option->setField('isChecked', $isChecked);
		$option->setField('CustomName', $this->getName().'[Custom]');
		$option->setField('CustomValue', $this->Value());
		if($this->Value()) {
			$preview = Convert::raw2xml($this->previewFormat($this->Value()));
			$option->setField('CustomPreview', $preview);
			$option->setField('CustomPreviewLabel', _t('MemberDatetimeOptionsetField.Preview', 'Preview'));
		}
		return $option;
	}

	/**
	 * For a given format, generate a preview for the date
	 *
	 * @param string $format Date format
	 * @return string
	 */
	protected function previewFormat($format) {
		$date = $this->config()->preview_date;
		$zendDate = new Zend_Date($date, 'y-MM-dd HH:mm:ss');
		return $zendDate->toString($format);
	}

	public function getOptionName() {
		return parent::getOptionName() . '[Options]';
	}

	public function Type() {
		return 'optionset memberdatetimeoptionset';
	}

	/**
	 * @todo Put this text into a template?
	 */
	public function getDescription() {
		$output =
			'<a href="#" class="toggle">'
			. _t('MemberDatetimeOptionsetField.Toggle', 'Show formatting help')
			. '</a>'
			. '<ul class="toggle-content">'
			. '<li>YYYY = ' . _t('MemberDatetimeOptionsetField.FOURDIGITYEAR', 'Four-digit year',
				40, 'Help text describing what "YYYY" means in ISO date formatting') . '</li>'
			. '<li>YY = ' . _t('MemberDatetimeOptionsetField.TWODIGITYEAR', 'Two-digit year',
				40, 'Help text describing what "YY" means in ISO date formatting') . '</li>'
			. '<li>MMMM = ' . _t('MemberDatetimeOptionsetField.FULLNAMEMONTH', 'Full name of month (e.g. June)',
				40, 'Help text describing what "MMMM" means in ISO date formatting') . '</li>'
			. '<li>MMM = ' . _t('MemberDatetimeOptionsetField.SHORTMONTH', 'Short name of month (e.g. Jun)',
				40, 'Help text letting describing what "MMM" means in ISO date formatting') . '</li>'
			. '<li>MM = ' . _t('MemberDatetimeOptionsetField.TWODIGITMONTH', 'Two-digit month (01=January, etc.)',
				40, 'Help text describing what "MM" means in ISO date formatting') . '</li>'
			. '<li>M = ' . _t('MemberDatetimeOptionsetField.MONTHNOLEADING', 'Month digit without leading zero',
				40, 'Help text describing what "M" means in ISO date formatting') . '</li>'
			. '<li>dd = ' . _t('MemberDatetimeOptionsetField.TWODIGITDAY', 'Two-digit day of month',
				40, 'Help text describing what "dd" means in ISO date formatting') . '</li>'
			. '<li>d = ' . _t('MemberDatetimeOptionsetField.DAYNOLEADING', 'Day of month without leading zero',
				40, 'Help text describing what "d" means in ISO date formatting') . '</li>'
			. '<li>hh = ' . _t('MemberDatetimeOptionsetField.TWODIGITHOUR', 'Two digits of hour (00 through 23)',
				40, 'Help text describing what "hh" means in ISO date formatting') . '</li>'
			. '<li>h = ' . _t('MemberDatetimeOptionsetField.HOURNOLEADING', 'Hour without leading zero',
				40, 'Help text describing what "h" means in ISO date formatting') . '</li>'
			. '<li>mm = ' . _t('MemberDatetimeOptionsetField.TWODIGITMINUTE',
			'Two digits of minute (00 through 59)',
				40, 'Help text describing what "mm" means in ISO date formatting') . '</li>'
			. '<li>m = ' . _t('MemberDatetimeOptionsetField.MINUTENOLEADING', 'Minute without leading zero',
				40, 'Help text describing what "m" means in ISO date formatting') . '</li>'
			. '<li>ss = ' . _t('MemberDatetimeOptionsetField.TWODIGITSECOND',
			'Two digits of second (00 through 59)',
				40, 'Help text describing what "ss" means in ISO date formatting') . '</li>'
			. '<li>s = ' . _t('MemberDatetimeOptionsetField.DIGITSDECFRACTIONSECOND',
			'One or more digits representing a decimal fraction of a second',
				40, 'Help text describing what "s" means in ISO date formatting') . '</li>'
			. '<li>a = ' . _t('MemberDatetimeOptionsetField.AMORPM', 'AM (Ante meridiem) or PM (Post meridiem)',
				40, 'Help text describing what "a" means in ISO date formatting') . '</li>'
			. '</ul>';
		return $output;
	}


	public function setValue($value) {
		// Extract custom option from postback
		if(is_array($value)) {
			if(empty($value['Options'])) {
				$value = '';
			} elseif($value['Options'] === self::CUSTOM_OPTION) {
				$value = $value['Custom'];
			} else {
				$value = $value['Options'];
			}
		}

		return parent::setValue($value);
	}

	/**
	 * Validate this field
	 *
	 * @param Validator $validator
	 * @return bool
	 */
	public function validate($validator) {
		$value = $this->Value();
		if(!$value) {
			return true; // no custom value, don't validate
		}

		// Check that the current date with the date format is valid or not
		require_once 'Zend/Date.php';
		$date = Zend_Date::now()->toString($value);
		$valid = Zend_Date::isDate($date, $value);
		if($valid) {
			return true;
		}

		// Fail
		$validator->validationError(
			$this->getName(),
			_t(
				'MemberDatetimeOptionsetField.DATEFORMATBAD',
				"Date format is invalid"
			),
			"validation"
		);
		return false;
	}
}
