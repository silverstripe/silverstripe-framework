<?php

/**
 * Greek (Greece) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('el_GR', $lang) && is_array($lang['el_GR'])) {
	$lang['el_GR'] = array_merge($lang['en_US'], $lang['el_GR']);
} else {
	$lang['el_GR'] = $lang['en_US'];
}

$lang['el_GR']['ChangePasswordEmail.ss']['CHANGEPASSWORDTEXT1'] = 'Αλλάξατε το κωδικό ασφαλείας σας για το';
$lang['el_GR']['ComplexTableField.ss']['ADDITEM'] = 'Προσθήκη';
$lang['el_GR']['DropdownField']['CHOOSE'] = '(Επιλέξτε)';
$lang['el_GR']['HtmlEditorField']['FORMATADDR'] = 'Διεύθυνση';
$lang['el_GR']['HtmlEditorField']['FORMATH1'] = 'Κεφαλίδα 1';
$lang['el_GR']['HtmlEditorField']['FORMATH2'] = 'Κεφαλίδα 2';
$lang['el_GR']['HtmlEditorField']['FORMATH3'] = 'Κεφαλίδα 3';
$lang['el_GR']['HtmlEditorField']['FORMATH4'] = 'Κεφαλίδα 4';
$lang['el_GR']['HtmlEditorField']['FORMATH5'] = 'Κεφαλίδα 5';
$lang['el_GR']['HtmlEditorField']['FORMATH6'] = 'Κεφαλίδα 6';
$lang['el_GR']['HtmlEditorField']['FORMATP'] = 'Παράγραφος';
$lang['el_GR']['Member']['EMAIL'] = 'Email';
$lang['el_GR']['Member']['INTERFACELANG'] = 'Γλώσσα Εφαρμογής';
$lang['el_GR']['Member']['PERSONALDETAILS'] = 'Προσωπικές Πληροφορίες';
$lang['el_GR']['Member']['SUBJECTPASSWORDCHANGED'] = 'Ο κωδικός ασφαλείας σας έχει αλλάξει';
$lang['el_GR']['Member']['SUBJECTPASSWORDRESET'] = 'Σύνδεσμος επαναφοράς κωδικού ασφαλείας';
$lang['el_GR']['Member']['USERDETAILS'] = 'Πληροφορίες Χρήστη';
$lang['el_GR']['SiteTree']['HTMLEDITORTITLE'] = 'Περιεχόμενο';
$lang['el_GR']['SiteTree']['PAGETYPE'] = 'Τύπος Σελίδας';

?>