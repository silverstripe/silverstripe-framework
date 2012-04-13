<?php
/**
 * @package framework
 * @subpackage security
 */
class MemberDatetimeOptionsetField extends OptionsetField {

	function Field($properties = array()) {
		$options = '';
		$odd = 0;
		$source = $this->getSource();

		foreach($source as $key => $value) {
			// convert the ID to an HTML safe value (dots are not replaced, as they are valid in an ID attribute)
			$itemID = $this->id() . '_' . preg_replace('/[^\.a-zA-Z0-9\-\_]/', '_', $key);
			if($key == $this->value) {
				$useValue = false;
				$checked = " checked=\"checked\"";
			} else {
				$checked = "";
			}

			$odd = ($odd + 1) % 2;
			$extraClass = $odd ? "odd" : "even";
			$extraClass .= " val" . preg_replace('/[^a-zA-Z0-9\-\_]/', '_', $key);
			$disabled = ($this->disabled || in_array($key, $this->disabledItems)) ? "disabled=\"disabled\"" : "";
			$ATT_key = Convert::raw2att($key);

			$options .= "<li class=\"".$extraClass."\"><input id=\"$itemID\" name=\"$this->name\" type=\"radio\" value=\"$key\"$checked $disabled class=\"radio\" /> <label title=\"$ATT_key\" for=\"$itemID\">$value</label></li>\n"; 
		}

		// Add "custom" input field
		$value = ($this->value && !array_key_exists($this->value, $this->source)) ? $this->value : null;
		$checked = ($value) ? " checked=\"checked\"" : '';
		$options .= "<li class=\"valCustom\">"
			. sprintf("<input id=\"%s_custom\" name=\"%s\" type=\"radio\" value=\"__custom__\" class=\"radio\" %s />", $itemID, $this->name, $checked)
			. sprintf('<label for="%s_custom">%s:</label>', $itemID, _t('MemberDatetimeOptionsetField.Custom', 'Custom'))
			. sprintf("<input class=\"customFormat\" name=\"%s_custom\" value=\"%s\" />\n", $this->name, $value)
			. sprintf("<input type=\"hidden\" class=\"formatValidationURL\" value=\"%s\" />", $this->Link() . '/validate');
		$options .= ($value) ? sprintf(
			'<span class="preview">(%s: "%s")</span>',
			_t('MemberDatetimeOptionsetField.Preview', 'Preview'),
			Zend_Date::now()->toString($value)
		) : '';
		$options .= sprintf(
			'<a class="cms-help-toggle" href="#%s">%s</a>',
			$this->id() . '_Help',
			_t('MemberDatetimeOptionsetField.TOGGLEHELP', 'Toggle formatting help')
		);
		$options .= "<div id=\"" . $this->id() . "_Help\">";
		$options .= $this->getFormattingHelpText();
		$options .= "</div>";
		$options .= "</li>\n";

		$id = $this->id();
		return "<ul id=\"$id\" class=\"optionset {$this->extraClass()}\">\n$options</ul>\n";
	}

	/**
	 * @todo Put this text into a template?
	 */
	function getFormattingHelpText() {
		$output = '<ul>';
		$output .= '<li>YYYY = ' . _t('MemberDatetimeOptionsetField.FOURDIGITYEAR', 'Four-digit year', 40, 'Help text describing what "YYYY" means in ISO date formatting') . '</li>';
		$output .= '<li>YY = ' . _t('MemberDatetimeOptionsetField.TWODIGITYEAR', 'Two-digit year', 40, 'Help text describing what "YY" means in ISO date formatting') . '</li>';
		$output .= '<li>MMMM = ' . _t('MemberDatetimeOptionsetField.FULLNAMEMONTH', 'Full name of month (e.g. June)', 40, 'Help text describing what "MMMM" means in ISO date formatting') . '</li>';
		$output .= '<li>MMM = ' . _t('MemberDatetimeOptionsetField.SHORTMONTH', 'Short name of month (e.g. Jun)', 40, 'Help text letting describing what "MMM" means in ISO date formatting') . '</li>';
		$output .= '<li>MM = ' . _t('MemberDatetimeOptionsetField.TWODIGITMONTH', 'Two-digit month (01=January, etc.)', 40, 'Help text describing what "MM" means in ISO date formatting') . '</li>';
		$output .= '<li>M = ' . _t('MemberDatetimeOptionsetField.MONTHNOLEADING', 'Month digit without leading zero', 40, 'Help text describing what "M" means in ISO date formatting') . '</li>';
		$output .= '<li>dd = ' . _t('MemberDatetimeOptionsetField.TWODIGITDAY', 'Two-digit day of month', 40, 'Help text describing what "dd" means in ISO date formatting') . '</li>';
		$output .= '<li>d = ' . _t('MemberDatetimeOptionsetField.DAYNOLEADING', 'Day of month without leading zero', 40, 'Help text describing what "d" means in ISO date formatting') . '</li>';
		$output .= '<li>hh = ' . _t('MemberDatetimeOptionsetField.TWODIGITHOUR', 'Two digits of hour (00 through 23)', 40, 'Help text describing what "hh" means in ISO date formatting') . '</li>';
		$output .= '<li>h = ' . _t('MemberDatetimeOptionsetField.HOURNOLEADING', 'Hour without leading zero', 40, 'Help text describing what "h" means in ISO date formatting') . '</li>';
		$output .= '<li>mm = ' . _t('MemberDatetimeOptionsetField.TWODIGITMINUTE', 'Two digits of minute (00 through 59)', 40, 'Help text describing what "mm" means in ISO date formatting') . '</li>';
		$output .= '<li>m = ' . _t('MemberDatetimeOptionsetField.MINUTENOLEADING', 'Minute without leading zero', 40, 'Help text describing what "m" means in ISO date formatting') . '</li>';
		$output .= '<li>ss = ' . _t('MemberDatetimeOptionsetField.TWODIGITSECOND', 'Two digits of second (00 through 59)', 40, 'Help text describing what "ss" means in ISO date formatting') . '</li>';
		$output .= '<li>s = ' . _t('MemberDatetimeOptionsetField.DIGITSDECFRACTIONSECOND', 'One or more digits representing a decimal fraction of a second', 40, 'Help text describing what "s" means in ISO date formatting') . '</li>';
		$output .= '<li>a = ' . _t('MemberDatetimeOptionsetField.AMORPM', 'AM (Ante meridiem) or PM (Post meridiem)', 40, 'Help text describing what "a" means in ISO date formatting') . '</li>';
		$output .= '</ul>';
		return $output;
	}

	function setValue($value) {
		if($value == '__custom__') {
			$value = isset($_REQUEST[$this->name . '_custom']) ? $_REQUEST[$this->name . '_custom'] : null;
		}
		if($value) {
			parent::setValue($value);
		}
	}

	function validate($validator) {
		$value = isset($_POST[$this->name . '_custom']) ? $_POST[$this->name . '_custom'] : null;
		if(!$value) return true; // no custom value, don't validate

		// Check that the current date with the date format is valid or not
		require_once 'Zend/Date.php';
		$date = Zend_Date::now()->toString($value);
		$valid = Zend_Date::isDate($date, $value);
		if($valid) {
			return true;
		} else {
			if($validator) {
				$validator->validationError($this->name, _t('MemberDatetimeOptionsetField.DATEFORMATBAD',"Date format is invalid"), "validation", false);
			}
			return false;
		}
	}
}
