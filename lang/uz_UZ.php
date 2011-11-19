<?php

/**
 * Uzbek (Uzbekistan) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('uz_UZ', $lang) && is_array($lang['uz_UZ'])) {
	$lang['uz_UZ'] = array_merge($lang['en_US'], $lang['uz_UZ']);
} else {
	$lang['uz_UZ'] = $lang['en_US'];
}

$lang['uz_UZ']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT1'] = 'Yangi parol';
$lang['uz_UZ']['ComplexTableField.ss']['ADDITEM'] = 'Qo\'shish';
$lang['uz_UZ']['DropdownField']['CHOOSE'] = '(Tanlang)';
$lang['uz_UZ']['HtmlEditorField']['FORMATADDR'] = 'Manzil';
$lang['uz_UZ']['HtmlEditorField']['FORMATH1'] = 'Sarlavha 1';
$lang['uz_UZ']['HtmlEditorField']['FORMATH2'] = 'Sarlavha 2';
$lang['uz_UZ']['HtmlEditorField']['FORMATH3'] = 'Sarlavha 3';
$lang['uz_UZ']['HtmlEditorField']['FORMATH4'] = 'Sarlavha 4';
$lang['uz_UZ']['HtmlEditorField']['FORMATH5'] = 'Sarlavha 5';
$lang['uz_UZ']['HtmlEditorField']['FORMATH6'] = 'Sarlavha 6';
$lang['uz_UZ']['HtmlEditorField']['FORMATP'] = 'Paragraf';
$lang['uz_UZ']['Member']['EMAIL'] = 'Email';
$lang['uz_UZ']['Member']['INTERFACELANG'] = 'Ko\'rinish tili';
$lang['uz_UZ']['Member']['PERSONALDETAILS'] = 'Shahsiy ma\'lumotlar';
$lang['uz_UZ']['Member']['SUBJECTPASSWORDCHANGED'] = 'Parol o\'zgardi';
$lang['uz_UZ']['Member']['SUBJECTPASSWORDRESET'] = 'Parol o\'zgartirish uchun adres';
$lang['uz_UZ']['Member']['USERDETAILS'] = 'A\'zo ma\'lumotlari';
$lang['uz_UZ']['SiteTree']['HOMEPAGEFORDOMAIN'] = 'Domainlar';
$lang['uz_UZ']['SiteTree']['HTMLEDITORTITLE'] = 'Material';
$lang['uz_UZ']['SiteTree']['PAGETYPE'] = 'Sahifa turi';

?>