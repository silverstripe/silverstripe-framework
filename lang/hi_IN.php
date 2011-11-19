<?php

/**
 * Hindi (India) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('hi_IN', $lang) && is_array($lang['hi_IN'])) {
	$lang['hi_IN'] = array_merge($lang['en_US'], $lang['hi_IN']);
} else {
	$lang['hi_IN'] = $lang['en_US'];
}


?>