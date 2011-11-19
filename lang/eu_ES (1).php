<?php

/**
 * Basque (Spain) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('eu_ES', $lang) && is_array($lang['eu_ES'])) {
	$lang['eu_ES'] = array_merge($lang['en_US'], $lang['eu_ES']);
} else {
	$lang['eu_ES'] = $lang['en_US'];
}


?>