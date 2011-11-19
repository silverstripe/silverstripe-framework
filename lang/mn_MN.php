<?php

/**
 * Mongolian (Mongolia) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('mn_MN', $lang) && is_array($lang['mn_MN'])) {
	$lang['mn_MN'] = array_merge($lang['en_US'], $lang['mn_MN']);
} else {
	$lang['mn_MN'] = $lang['en_US'];
}

$lang['mn_MN']['ComplexTableField.ss']['ADDITEM'] = 'Нэмэх';
$lang['mn_MN']['DropdownField']['CHOOSE'] = '(Сонго)';
$lang['mn_MN']['HtmlEditorField']['FORMATADDR'] = 'Хаяг';
$lang['mn_MN']['Member']['EMAIL'] = 'Имэйл';
$lang['mn_MN']['Member']['PERSONALDETAILS'] = 'Хувийн мэдээлэл';
$lang['mn_MN']['Member']['SUBJECTPASSWORDCHANGED'] = 'Таны нууц үг өөрчлөгдлөө.';
$lang['mn_MN']['Member']['USERDETAILS'] = 'Гишүүний мэдээлэл';
$lang['mn_MN']['SiteTree']['HOMEPAGEFORDOMAIN'] = 'Домэйн(ууд)';
$lang['mn_MN']['SiteTree']['HTMLEDITORTITLE'] = 'Агуулга';
$lang['mn_MN']['SiteTree']['PAGETYPE'] = 'Хуудасны төрөл';

?>