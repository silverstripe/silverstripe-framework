<?php

/**
 * English (Spain) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('en_ES', $lang) && is_array($lang['en_ES'])) {
	$lang['en_ES'] = array_merge($lang['en_US'], $lang['en_ES']);
} else {
	$lang['en_ES'] = $lang['en_US'];
}


?>