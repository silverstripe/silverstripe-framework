<?php

/**
 * Readonly field equivalent for literal HTML
 *
 * Unlike HTMLEditorField_Readonly, does not processs shortcodes
 */
class HTMLReadonlyField extends ReadonlyField {
	private static $casting = [
		'Value' => 'HTMLFragment'
	];
}
