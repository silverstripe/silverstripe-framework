<?php

/**
 * Kurdish (Turkey) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('ku_TR', $lang) && is_array($lang['ku_TR'])) {
	$lang['ku_TR'] = array_merge($lang['en_US'], $lang['ku_TR']);
} else {
	$lang['ku_TR'] = $lang['en_US'];
}

$lang['ku_TR']['Folder']['FILESTAB'] = 'Rûpelan';
$lang['ku_TR']['SiteTree']['Comments'] = 'Şîrovekirinan';

?>