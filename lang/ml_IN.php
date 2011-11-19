<?php

/**
 * Malayalam (India) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('ml_IN', $lang) && is_array($lang['ml_IN'])) {
	$lang['ml_IN'] = array_merge($lang['en_US'], $lang['ml_IN']);
} else {
	$lang['ml_IN'] = $lang['en_US'];
}

$lang['ml_IN']['Date']['DAYS'] = 'ദിവസങള്‍';
$lang['ml_IN']['Date']['HOUR'] = 'മണിക്കൂര്‍';
$lang['ml_IN']['Date']['HOURS'] = 'മണിക്കൂറുകള്‍';
$lang['ml_IN']['Date']['YEAR'] = 'വര്‍ഷം';
$lang['ml_IN']['Date']['YEARS'] = 'വര്‍ഷങള്‍';
$lang['ml_IN']['HtmlEditorField']['BUTTONINSERTIMAGE'] = 'ചിത്രം ചേര്‍ക്കുക';
$lang['ml_IN']['HtmlEditorField']['OK'] = 'ശരി';

?>