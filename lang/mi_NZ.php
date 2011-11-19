<?php

/**
 * Maori (New Zealand) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('mi_NZ', $lang) && is_array($lang['mi_NZ'])) {
	$lang['mi_NZ'] = array_merge($lang['en_US'], $lang['mi_NZ']);
} else {
	$lang['mi_NZ'] = $lang['en_US'];
}

$lang['mi_NZ']['ComplexTableField.ss']['ADDITEM'] = 'Whakaurunga';
$lang['mi_NZ']['Member']['USERDETAILS'] = 'Mokamoka kaiwhakamahi';
$lang['mi_NZ']['SiteTree']['HOMEPAGEFORDOMAIN'] = 'Rohe; Nga Rohe';
$lang['mi_NZ']['SiteTree']['HTMLEDITORTITLE'] = 'Kai';
$lang['mi_NZ']['SiteTree']['PAGETYPE'] = 'Tūmomo wharangi ';

?>