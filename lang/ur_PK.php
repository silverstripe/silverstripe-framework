<?php

/**
 * Urdu (Pakistan) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('ur_PK', $lang) && is_array($lang['ur_PK'])) {
	$lang['ur_PK'] = array_merge($lang['en_US'], $lang['ur_PK']);
} else {
	$lang['ur_PK'] = $lang['en_US'];
}

$lang['ur_PK']['Date']['DAYS'] = 'دن';
$lang['ur_PK']['Date']['YEAR'] = 'سال';
$lang['ur_PK']['Date']['YEARS'] = 'سال';
$lang['ur_PK']['Member']['GREETING'] = 'خوش آئندہ';

?>