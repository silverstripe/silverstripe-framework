<?php

/**
 * Welsh (United Kingdom) language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('cy_GB', $lang) && is_array($lang['cy_GB'])) {
	$lang['cy_GB'] = array_merge($lang['en_US'], $lang['cy_GB']);
} else {
	$lang['cy_GB'] = $lang['en_US'];
}

$lang['cy_GB']['Member']['EMAIL'] = 'eBost';
$lang['cy_GB']['SiteTree']['HOMEPAGEFORDOMAIN'] = 'Domain(s)';

?>