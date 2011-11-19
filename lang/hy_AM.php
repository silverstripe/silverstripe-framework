<?php

/**
 * Armenian (Armenia) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('hy_AM', $lang) && is_array($lang['hy_AM'])) {
	$lang['hy_AM'] = array_merge($lang['en_US'], $lang['hy_AM']);
} else {
	$lang['hy_AM'] = $lang['en_US'];
}

$lang['hy_AM']['ConfirmedPasswordField']['SHOWONCLICKTITLE'] = 'Փոխել Գաղտնաբառ';
$lang['hy_AM']['SiteTree']['HTMLEDITORTITLE'] = 'Բովանդակություն';

?>