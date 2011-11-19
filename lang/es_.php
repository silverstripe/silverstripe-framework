<?php

/**
 *  language pack
 * @package sapphire
 * @subpackage i18n
 */

i18n::include_locale_file('sapphire', 'en_US');

global $lang;

if(array_key_exists('es_', $lang) && is_array($lang['es_'])) {
	$lang['es_'] = array_merge($lang['en_US'], $lang['es_']);
} else {
	$lang['es_'] = $lang['en_US'];
}


?>