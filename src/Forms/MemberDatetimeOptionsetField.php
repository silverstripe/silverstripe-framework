<?php

namespace SilverStripe\Forms;

use SilverStripe\ORM\ArrayList;
use SilverStripe\Core\Convert;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;
use Zend_Date;

class MemberDatetimeOptionsetField extends OptionsetField {

	const CUSTOM_OPTION = '__custom__';

	/**
	 * Non-ambiguous date to use for the preview.
	 * Must be in 'y-MM-dd HH:mm:ss' format
	 *
	 * @var string
	 */
	private static $preview_date = '25-12-2011 17:30:00';

	private static $casting = ['Description' => 'HTMLText'];

	private $descriptionTemplate = '';

	public function Field($properties = array()) {
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

	public function getDescription() {
		if ($template = $this->getDescriptionTemplate()) {
			return $this->renderWith($template);
		}
		return parent::getDescription();
	}

	public function getDescriptionTemplate() {
		return $this->descriptionTemplate;
	}

	public function setDescriptionTemplate($template) {
		$this->descriptionTemplate = $template;
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
