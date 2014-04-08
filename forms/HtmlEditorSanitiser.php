<?php

/**
 * Sanitises an HTMLValue so it's contents are the elements and attributes that are whitelisted
 * using the same configuration as TinyMCE
 *
 * See www.tinymce.com/wiki.php/configuration:valid_elements for details on the spec of TinyMCE's
 * whitelist configuration
 *
 * @package forms
 * @subpackage fields-formattedinput
 */
class HtmlEditorSanitiser {

	/** @var [stdClass] - $element => $rule hash for whitelist element rules where the element name isn't a pattern */
	protected $elements = array();
	/** @var [stdClass] - Sequential list of whitelist element rules where the element name is a pattern */
	protected $elementPatterns = array();

	/** @var [stdClass] - The list of attributes that apply to all further whitelisted elements added */
	protected $globalAttributes = array();

	/**
	 * Construct a sanitiser from a given HtmlEditorConfig
	 *
	 * Note that we build data structures from the current state of HtmlEditorConfig - later changes to
	 * the passed instance won't cause this instance to update it's whitelist
	 *
	 * @param HtmlEditorConfig $config
	 */
	public function __construct(HtmlEditorConfig $config) {
		$valid = $config->getOption('valid_elements');
		if ($valid) $this->addValidElements($valid);

		$valid = $config->getOption('extended_valid_elements');
		if ($valid) $this->addValidElements($valid);
	}

	/**
	 * Given a TinyMCE pattern (close to unix glob style), create a regex that does the match
	 *
	 * @param $str - The TinyMCE pattern
	 * @return string - The equivalent regex
	 */
	protected function patternToRegex($str) {
		return '/^' . preg_replace('/([?+*])/', '.$1', $str) . '$/';
	}

	/**
	 * Given a valid_elements string, parse out the actual element and attribute rules and add to the
	 * internal whitelist
	 *
	 * Logic based heavily on javascript version from tiny_mce_src.js
	 *
	 * @param string $validElements - The valid_elements or extended_valid_elements string to add to the whitelist
	 */
	protected function addValidElements($validElements) {
		$elementRuleRegExp = '/^([#+\-])?([^\[\/]+)(?:\/([^\[]+))?(?:\[([^\]]+)\])?$/';
		$attrRuleRegExp = '/^([!\-])?(\w+::\w+|[^=:<]+)?(?:([=:<])(.*))?$/';
		$hasPatternsRegExp = '/[*?+]/';

		foreach(explode(',', $validElements) as $validElement) {
			if(preg_match($elementRuleRegExp, $validElement, $matches)) {

				$prefix = isset($matches[1]) ? $matches[1] : null;
				$elementName = isset($matches[2]) ? $matches[2] : null;
				$outputName = isset($matches[3]) ? $matches[3] : null;
				$attrData = isset($matches[4]) ? $matches[4] : null;

				// Create the new element
				$element = new stdClass();
				$element->attributes = array();
				$element->attributePatterns = array();

				$element->attributesRequired = array();
				$element->attributesDefault = array();
				$element->attributesForced = array();

				foreach(array('#' => 'paddEmpty', '-' => 'removeEmpty') as $match => $means) {
					$element->$means = ($prefix === $match);
				}

				// Copy attributes from global rule into current rule
				if($this->globalAttributes) {
					$element->attributes = array_merge($element->attributes, $this->globalAttributes);
				}

				// Attributes defined
				if($attrData) {
					foreach(explode('|', $attrData) as $attr) {
						if(preg_match($attrRuleRegExp, $attr, $matches)) {
							$attr = new stdClass();

							$attrType = isset($matches[1]) ? $matches[1] : null;
							$attrName = isset($matches[2]) ? str_replace('::', ':', $matches[2]) : null;
							$prefix = isset($matches[3]) ? $matches[3] : null;
							$value = isset($matches[4]) ? $matches[4] : null;

							// Required
							if($attrType === '!') {
								$element->attributesRequired[] = $attrName;
								$attr->required = true;
							}

							// Denied from global
							else if($attrType === '-') {
								unset($element->attributes[$attrName]);
								continue;
							}

							// Default value
							if($prefix) {
								// Default value
								if($prefix === '=') {
									$element->attributesDefault[$attrName] = $value;
									$attr->defaultValue = $value;
								}

								// Forced value
								else if($prefix === ':') {
									$element->attributesForced[$attrName] = $value;
									$attr->forcedValue = $value;
								}

								// Required values
								else if($prefix === '<') {
									$attr->validValues = explode('?', $value);
								}
							}

							// Check for attribute patterns
							if(preg_match($hasPatternsRegExp, $attrName)) {
								$attr->pattern = $this->patternToRegex($attrName);
								$element->attributePatterns[] = $attr;
							}
							else {
								$element->attributes[$attrName] = $attr;
							}
						}
					}
				}

				// Global rule, store away these for later usage
				if(!$this->globalAttributes && $elementName == '@') {
					$this->globalAttributes = $element->attributes;
				}

				// Handle substitute elements such as b/strong
				if($outputName) {
					$element->outputName = $elementName;
					$this->elements[$outputName] = $element;
				}

				// Add pattern or exact element
				if(preg_match($hasPatternsRegExp, $elementName)) {
					$element->pattern = $this->patternToRegex($elementName);
					$this->elementPatterns[] = $element;
				}
				else {
					$this->elements[$elementName] = $element;
				}
			}
		}
	}

	/**
	 * Given an element tag, return the rule structure for that element
	 * @param string $tag - The element tag
	 * @return stdClass - The element rule
	 */
	protected function getRuleForElement($tag) {
		if(isset($this->elements[$tag])) {
			return $this->elements[$tag];
		}
		else foreach($this->elementPatterns as $element) {
			if(preg_match($element->pattern, $tag)) return $element;
		}
	}

	/**
	 * Given an attribute name, return the rule structure for that attribute
	 * @param string $name - The attribute name
	 * @return stdClass - The attribute rule
	 */
	protected function getRuleForAttribute($elementRule, $name) {
		if(isset($elementRule->attributes[$name])) {
			return $elementRule->attributes[$name];
		}
		else foreach($elementRule->attributePatterns as $attribute) {
			if(preg_match($attribute->pattern, $name)) return $attribute;
		}
	}

	/**
	 * Given a DOMElement and an element rule, check if that element passes the rule
	 * @param DOMElement $element - the element to check
	 * @param stdClass $rule - the rule to check against
	 * @return bool - true if the element passes (and so can be kept), false if it fails (and so needs stripping)
	 */
	protected function elementMatchesRule($element, $rule = null) {
		// If the rule doesn't exist at all, the element isn't allowed
		if(!$rule) return false;

		// If the rule has attributes required, check them to see if this element has at least one
		if($rule->attributesRequired) {
			$hasMatch = false;

			foreach($rule->attributesRequired as $attr) {
				if($element->getAttribute($attr)) {
					$hasMatch = true;
					break;
				}
			}

			if(!$hasMatch) return false;
		}

		// If the rule says to remove empty elements, and this element is empty, remove it
		if($rule->removeEmpty && !$element->firstChild) return false;

		// No further tests required, element passes
		return true;
	}

	/**
	 * Given a DOMAttr and an attribute rule, check if that attribute passes the rule
	 * @param DOMAttr $attr - the attribute to check
	 * @param stdClass $rule - the rule to check against
	 * @return bool - true if the attribute passes (and so can be kept), false if it fails (and so needs stripping)
	 */
	protected function attributeMatchesRule($attr, $rule = null) {
		// If the rule doesn't exist at all, the attribute isn't allowed
		if(!$rule) return false;

		// If the rule has a set of valid values, check them to see if this attribute is one
		if(isset($rule->validValues) && !in_array($attr->value, $rule->validValues)) return false;

		// No further tests required, attribute passes
		return true;
	}

	/**
	 * Given an SS_HTMLValue instance, will remove and elements and attributes that are
	 * not explicitly included in the whitelist passed to __construct on instance creation
	 *
	 * @param SS_HTMLValue $html - The HTMLValue to remove any non-whitelisted elements & attributes from
	 */
	public function sanitise (SS_HTMLValue $html) {
		if(!$this->elements && !$this->elementPatterns) return;

		$doc = $html->getDocument();

		foreach($html->query('//body//*') as $el) {
			$elementRule = $this->getRuleForElement($el->tagName);

			// If this element isn't allowed, strip it
			if(!$this->elementMatchesRule($el, $elementRule)) {
				// If it's a script or style, we don't keep contents
				if($el->tagName === 'script' || $el->tagName === 'style') {
					$el->parentNode->removeChild($el);
				}
				// Otherwise we replace this node with all it's children
				else {
					// First, create a new fragment with all of $el's children moved into it
					$frag = $doc->createDocumentFragment();
					while($el->firstChild) $frag->appendChild($el->firstChild);

					// Then replace $el with the frags contents (which used to be it's children)
					$el->parentNode->replaceChild($frag, $el);
				}
			}
			// Otherwise tidy the element
			else {
				// First, if we're supposed to pad & this element is empty, fix that
				if($elementRule->paddEmpty && !$el->firstChild) {
					$el->nodeValue = '&nbsp;';
				}

				// Then filter out any non-whitelisted attributes
				$children = $el->attributes;
				$i = $children->length;
				while($i--) {
					$attr = $children->item($i);
					$attributeRule = $this->getRuleForAttribute($elementRule, $attr->name);

					// If this attribute isn't allowed, strip it
					if(!$this->attributeMatchesRule($attr, $attributeRule)) {
						$el->removeAttributeNode($attr);
					}
				}

				// Then enforce any default attributes
				foreach($elementRule->attributesDefault as $attr => $default) {
					if(!$el->getAttribute($attr)) $el->setAttribute($attr, $default);
				}

				// And any forced attributes
				foreach($elementRule->attributesForced as $attr => $forced) {
					$el->setAttribute($attr, $forced);
				}
			}
		}
	}

}
