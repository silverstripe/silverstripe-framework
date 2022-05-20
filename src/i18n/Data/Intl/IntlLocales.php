<?php

namespace SilverStripe\i18n\Data\Intl;

use Collator;
use Exception;
use Locale;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Resettable;
use SilverStripe\i18n\Data\Locales;
use SilverStripe\i18n\i18n;

/**
 * Locale metadata
 *
 * Language codes follow ISO 639-1 (2 letter), unless not present, in which case a locale will be
 * encoded in ISO 639-3 (3 letter). See https://en.wikipedia.org/wiki/ISO_639
 *
 * Country codes follow ISO 3166-1 alpha-2 (2 letter), unless not present, in which case a country
 * code will be encoded in ISO 3166-1 alpha-3 (3 letter). See https://en.wikipedia.org/wiki/ISO_3166-1
 */
class IntlLocales implements Locales, Resettable
{
    use Injectable;
    use Configurable;

    public function __construct()
    {
        if (!class_exists(Locale::class)) {
            throw new Exception("This backend requires the php-intl extension");
        }
    }

    /**
     * An exhaustive list of possible locales (code => language and country)
     *
     * @config
     * @var array
     */
    private static $locales =  [
        'aa_DJ' => 'Afar (Djibouti)',
        'ab_GE' => 'Abkhazian (Georgia)',
        'abr_GH' => 'Abron (Ghana)',
        'ace_ID' => 'Achinese (Indonesia)',
        'ady_RU' => 'Adyghe (Russia)',
        'af_ZA' => 'Afrikaans (South Africa)',
        'ak_GH' => 'Akan (Ghana)',
        'am_ET' => 'Amharic (Ethiopia)',
        'ar_AE' => 'Arabic (United Arab Emirates)',
        'ar_BH' => 'Arabic (Bahrain)',
        'ar_DZ' => 'Arabic (Algeria)',
        'ar_EG' => 'Arabic (Egypt)',
        'ar_EH' => 'Arabic (Western Sahara)',
        'ar_IQ' => 'Arabic (Iraq)',
        'ar_JO' => 'Arabic (Jordan)',
        'ar_KW' => 'Arabic (Kuwait)',
        'ar_LB' => 'Arabic (Lebanon)',
        'ar_LY' => 'Arabic (Libya)',
        'ar_MA' => 'Arabic (Morocco)',
        'ar_MR' => 'Arabic (Mauritania)',
        'ar_OM' => 'Arabic (Oman)',
        'ar_PS' => 'Arabic (Palestinian Territory)',
        'ar_QA' => 'Arabic (Qatar)',
        'ar_SA' => 'Arabic (Saudi Arabia)',
        'ar_SD' => 'Arabic (Sudan)',
        'ar_SY' => 'Arabic (Syria)',
        'ar_TD' => 'Arabic (Chad)',
        'ar_TN' => 'Arabic (Tunisia)',
        'ar_YE' => 'Arabic (Yemen)',
        'as_IN' => 'Assamese (India)',
        'ast_ES' => 'Asturian (Spain)',
        'auv_FR' => 'Auvergnat (France)',
        'av_RU' => 'Avaric (Russia)',
        'awa_IN' => 'Awadhi (India)',
        'ay_BO' => 'Aymara (Bolivia)',
        'ay_PE' => 'Aymara (Peru)',
        'az_AZ' => 'Azerbaijani (Azerbaijan)',
        'az_IR' => 'Azerbaijani (Iran)',
        'ba_RU' => 'Bashkir (Russia)',
        'ban_ID' => 'Balinese (Indonesia)',
        'bcc_PK' => 'Balochi, Southern (Pakistan)',
        'bcl_PH' => 'Bicolano, Central (Philippines)',
        'be_BY' => 'Belarusian (Belarus)',
        'bew_ID' => 'Betawi (Indonesia)',
        'bg_BG' => 'Bulgarian (Bulgaria)',
        'bgc_IN' => 'Haryanvi (India)',
        'bgn_PK' => 'Balochi, Western (Pakistan)',
        'bgp_PK' => 'Balochi, Easter (Pakistan)',
        'bhb_IN' => 'Bhili (India)',
        'bhi_IN' => 'Bhilali (India)',
        'bhk_PH' => 'Bicolano, Albay (Philippines)',
        'bho_IN' => 'Bhojpuri (India)',
        'bho_MU' => 'Bhojpuri (Mauritius)',
        'bho_NP' => 'Bhojpuri (Nepal)',
        'bi_VU' => 'Bislama (Vanuatu)',
        'bjj_IN' => 'Kanauji (India)',
        'bjn_ID' => 'Banjar (Indonesia)',
        'bm_ML' => 'Bambara (Mali)',
        'bn_BD' => 'Bengali (Bangladesh)',
        'bn_IN' => 'Bengali (India)',
        'bo_CN' => 'Tibetan (China)',
        'bqi_IR' => 'Bakhtiari (Iran)',
        'brh_PK' => 'Brahui (Pakistan)',
        'bs_BA' => 'Bosnian (Bosnia and Herzegovina)',
        'btk_ID' => 'Batak (Indonesia)',
        'buc_YT' => 'Bushi (Mayotte)',
        'bug_ID' => 'Buginese (Indonesia)',
        'ca_AD' => 'Catalan (Andorra)',
        'ca_ES' => 'Catalan (Spain)',
        'ce_RU' => 'Chechen (Russia)',
        'ceb_PH' => 'Cebuano (Philippines)',
        'cgg_UG' => 'Chiga (Uganda)',
        'ch_GU' => 'Chamorro (Guam)',
        'chk_FM' => 'Chuukese (Micronesia)',
        'crk_CA' => 'Cree, Plains (Canada)',
        'cs_CZ' => 'Czech (Czech Republic)',
        'cwd_CA' => 'Cree, Woods (Canada)',
        'cy_GB' => 'Welsh (United Kingdom)',
        'da_DK' => 'Danish (Denmark)',
        'da_GL' => 'Danish (Greenland)',
        'dcc_IN' => 'Deccan (India)',
        'de_AT' => 'German (Austria)',
        'de_BE' => 'German (Belgium)',
        'de_CH' => 'German (Switzerland)',
        'de_DE' => 'German (Germany)',
        'de_LI' => 'German (Liechtenstein)',
        'de_LU' => 'German (Luxembourg)',
        'dgo_IN' => 'Dogri (India)',
        'dhd_IN' => 'Dhundari (India)',
        'diq_TR' => 'Dimli (Turkey)',
        'dje_NE' => 'Zarma (Niger)',
        'dv_MV' => 'Divehi (Maldives)',
        'dz_BT' => 'Dzongkha (Bhutan)',
        'ee_GH' => 'Ewe (Ghana)',
        'el_CY' => 'Greek (Cyprus)',
        'el_GR' => 'Greek (Greece)',
        'en_AS' => 'English (American Samoa)',
        'en_AU' => 'English (Australia)',
        'en_BM' => 'English (Bermuda)',
        'en_BS' => 'English (Bahamas)',
        'en_CA' => 'English (Canada)',
        'en_DE' => 'English (Germany)',
        'en_ES' => 'English (Spain)',
        'en_FR' => 'English (France)',
        'en_GB' => 'English (United Kingdom)',
        'en_HK' => 'English (Hong Kong SAR China)',
        'en_IE' => 'English (Ireland)',
        'en_IN' => 'English (India)',
        'en_IT' => 'English (Italy)',
        'en_JM' => 'English (Jamaica)',
        'en_KE' => 'English (Kenya)',
        'en_LR' => 'English (Liberia)',
        'en_MM' => 'English (Myanmar)',
        'en_MW' => 'English (Malawi)',
        'en_MY' => 'English (Malaysia)',
        'en_NL' => 'English (Netherlands)',
        'en_NZ' => 'English (New Zealand)',
        'en_PH' => 'English (Philippines)',
        'en_SG' => 'English (Singapore)',
        'en_TT' => 'English (Trinidad and Tobago)',
        'en_US' => 'English (United States)',
        'en_ZA' => 'English (South Africa)',
        'eo_XX' => 'Esperanto',
        'es_419' => 'Spanish (Latin America)',
        'es_AR' => 'Spanish (Argentina)',
        'es_BO' => 'Spanish (Bolivia)',
        'es_CL' => 'Spanish (Chile)',
        'es_CO' => 'Spanish (Colombia)',
        'es_CR' => 'Spanish (Costa Rica)',
        'es_CU' => 'Spanish (Cuba)',
        'es_DO' => 'Spanish (Dominican Republic)',
        'es_EC' => 'Spanish (Ecuador)',
        'es_ES' => 'Spanish (Spain)',
        'es_GQ' => 'Spanish (Equatorial Guinea)',
        'es_GT' => 'Spanish (Guatemala)',
        'es_HN' => 'Spanish (Honduras)',
        'es_MX' => 'Spanish (Mexico)',
        'es_NI' => 'Spanish (Nicaragua)',
        'es_PA' => 'Spanish (Panama)',
        'es_PE' => 'Spanish (Peru)',
        'es_PH' => 'Spanish (Philippines)',
        'es_PR' => 'Spanish (Puerto Rico)',
        'es_PY' => 'Spanish (Paraguay)',
        'es_SV' => 'Spanish (El Salvador)',
        'es_US' => 'Spanish (United States)',
        'es_UY' => 'Spanish (Uruguay)',
        'es_VE' => 'Spanish (Venezuela)',
        'et_EE' => 'Estonian (Estonia)',
        'eu_ES' => 'Basque (Spain)',
        'fa_AF' => 'Persian (Afghanistan)',
        'fa_IR' => 'Persian (Iran)',
        'fa_PK' => 'Persian (Pakistan)',
        'fan_GQ' => 'Fang (Equatorial Guinea)',
        'fi_FI' => 'Finnish (Finland)',
        'fi_SE' => 'Finnish (Sweden)',
        'fil_PH' => 'Filipino (Philippines)',
        'fj_FJ' => 'Fijian (Fiji)',
        'fo_FO' => 'Faroese (Faroe Islands)',
        'fon_BJ' => 'Fon (Benin)',
        'fr_002' => 'French (Africa)',
        'fr_BE' => 'French (Belgium)',
        'fr_CA' => 'French (Canada)',
        'fr_CH' => 'French (Switzerland)',
        'fr_DZ' => 'French (Algeria)',
        'fr_FR' => 'French (France)',
        'fr_GF' => 'French (French Guiana)',
        'fr_GP' => 'French (Guadeloupe)',
        'fr_HT' => 'French (Haiti)',
        'fr_KM' => 'French (Comoros)',
        'fr_MA' => 'French (Morocco)',
        'fr_MQ' => 'French (Martinique)',
        'fr_MU' => 'French (Mauritius)',
        'fr_NC' => 'French (New Caledonia)',
        'fr_PF' => 'French (French Polynesia)',
        'fr_PM' => 'French (Saint Pierre and Miquelon)',
        'fr_RE' => 'French (Reunion)',
        'fr_SC' => 'French (Seychelles)',
        'fr_SN' => 'French (Senegal)',
        'fr_US' => 'French (United States)',
        'fuv_NG' => 'Fulfulde (Nigeria)',
        'ga_GB' => 'Irish (United Kingdom)',
        'ga_IE' => 'Irish (Ireland)',
        'gaa_GH' => 'Ga (Ghana)',
        'gbm_IN' => 'Garhwali (India)',
        'gcr_GF' => 'Guianese Creole French (French Guiana)',
        'gd_GB' => 'Scottish Gaelic (United Kingdom)',
        'gil_KI' => 'Gilbertese (Kiribati)',
        'gl_ES' => 'Galician (Spain)',
        'glk_IR' => 'Gilaki (Iran)',
        'gn_PY' => 'Guarani (Paraguay)',
        'gno_IN' => 'Gondi, Northern (India)',
        'gsw_CH' => 'Swiss German (Switzerland)',
        'gsw_LI' => 'Swiss German (Liechtenstein)',
        'gu_IN' => 'Gujarati (India)',
        'guz_KE' => 'Gusii (Kenya)',
        'ha_NE' => 'Hausa (Niger)',
        'ha_NG' => 'Hausa (Nigeria)',
        'haw_US' => 'Hawaiian (United States)',
        'haz_AF' => 'Hazaragi (Afghanistan)',
        'he_IL' => 'Hebrew (Israel)',
        'hi_IN' => 'Hindi (India)',
        'hil_PH' => 'Hiligaynon (Philippines)',
        'hne_IN' => 'Chhattisgarhi (India)',
        'hno_PK' => 'Hindko, Northern (Pakistan)',
        'hoc_IN' => 'Ho (India)',
        'hr_AT' => 'Croatian (Austria)',
        'hr_BA' => 'Croatian (Bosnia and Herzegovina)',
        'hr_HR' => 'Croatian (Croatia)',
        'ht_HT' => 'Haitian (Haiti)',
        'hu_AT' => 'Hungarian (Austria)',
        'hu_HU' => 'Hungarian (Hungary)',
        'hu_RO' => 'Hungarian (Romania)',
        'hu_RS' => 'Hungarian (Serbia)',
        'hy_AM' => 'Armenian (Armenia)',
        'id_ID' => 'Indonesian (Indonesia)',
        'ig_NG' => 'Igbo (Nigeria)',
        'ilo_PH' => 'Iloko (Philippines)',
        'inh_RU' => 'Ingush (Russia)',
        'is_IS' => 'Icelandic (Iceland)',
        'it_CH' => 'Italian (Switzerland)',
        'it_FR' => 'Italian (France)',
        'it_HR' => 'Italian (Croatia)',
        'it_IT' => 'Italian (Italy)',
        'it_SM' => 'Italian (San Marino)',
        'it_US' => 'Italian (United States)',
        'iu_CA' => 'Inuktitut (Canada)',
        'ja_JP' => 'Japanese (Japan)',
        'jv_ID' => 'Javanese (Indonesia)',
        'ka_GE' => 'Georgian (Georgia)',
        'kam_KE' => 'Kamba (Kenya)',
        'kbd_RU' => 'Kabardian (Russia)',
        'kfy_IN' => 'Kumauni (India)',
        'kha_IN' => 'Khasi (India)',
        'khn_IN' => 'Khandesi (India)',
        'ki_KE' => 'Kikuyu (Kenya)',
        'kj_NA' => 'Kuanyama (Namibia)',
        'kk_CN' => 'Kazakh (China)',
        'kk_KZ' => 'Kazakh (Kazakhstan)',
        'kl_DK' => 'Kalaallisut (Denmark)',
        'kl_GL' => 'Kalaallisut (Greenland)',
        'kln_KE' => 'Kalenjin (Kenya)',
        'km_KH' => 'Khmer (Cambodia)',
        'kn_IN' => 'Kannada (India)',
        'ko_KR' => 'Korean (Korea)',
        'koi_RU' => 'Komi-Permyak (Russia)',
        'kok_IN' => 'Konkani (India)',
        'kos_FM' => 'Kosraean (Micronesia)',
        'kpv_RU' => 'Komi-Zyrian (Russia)',
        'krc_RU' => 'Karachay-Balkar (Russia)',
        'kru_IN' => 'Kurukh (India)',
        'ks_IN' => 'Kashmiri (India)',
        'ku_IQ' => 'Kurdish (Iraq)',
        'ku_IR' => 'Kurdish (Iran)',
        'ku_SY' => 'Kurdish (Syria)',
        'ku_TR' => 'Kurdish (Turkey)',
        'kum_RU' => 'Kumyk (Russia)',
        'kxm_TH' => 'Khmer, Northern (Thailand)',
        'ky_KG' => 'Kirghiz (Kyrgyzstan)',
        'la_VA' => 'Latin (Vatican)',
        'lah_PK' => 'Lahnda (Pakistan)',
        'lb_LU' => 'Luxembourgish (Luxembourg)',
        'lbe_RU' => 'Lak (Russia)',
        'lc_XX' => 'LOLCAT',
        'lez_RU' => 'Lezghian (Russia)',
        'lg_UG' => 'Ganda (Uganda)',
        'lij_IT' => 'Ligurian (Italy)',
        'lij_MC' => 'Ligurian (Monaco)',
        'ljp_ID' => 'Lampung (Indonesia)',
        'lmn_IN' => 'Lambadi (India)',
        'ln_CD' => 'Lingala (Congo - Kinshasa)',
        'ln_CG' => 'Lingala (Congo - Brazzaville)',
        'lo_LA' => 'Lao (Laos)',
        'lrc_IR' => 'Luri, Northern (Iran)',
        'lt_LT' => 'Lithuanian (Lithuania)',
        'luo_KE' => 'Luo (Kenya)',
        'luy_KE' => 'Luyia (Kenya)',
        'lv_LV' => 'Latvian (Latvia)',
        'mad_ID' => 'Madurese (Indonesia)',
        'mai_IN' => 'Maithili (India)',
        'mai_NP' => 'Maithili (Nepal)',
        'mak_ID' => 'Makasar (Indonesia)',
        'mdf_RU' => 'Moksha (Russia)',
        'mdh_PH' => 'Maguindanao (Philippines)',
        'mer_KE' => 'Meru (Kenya)',
        'mfa_TH' => 'Malay, Pattani (Thailand)',
        'mfe_MU' => 'Morisyen (Mauritius)',
        'mg_MG' => 'Malagasy (Madagascar)',
        'mh_MH' => 'Marshallese (Marshall Islands)',
        'mi_NZ' => 'te reo Māori (New Zealand)',
        'min_ID' => 'Minangkabau (Indonesia)',
        'mk_MK' => 'Macedonian (Macedonia)',
        'ml_IN' => 'Malayalam (India)',
        'mn_CN' => 'Mongolian (China)',
        'mn_MN' => 'Mongolian (Mongolia)',
        'mni_IN' => 'Manipuri (India)',
        'mr_IN' => 'Marathi (India)',
        'ms_BN' => 'Malay (Brunei)',
        'ms_CC' => 'Malay (Cocos Islands)',
        'ms_ID' => 'Malay (Indonesia)',
        'ms_MY' => 'Malay (Malaysia)',
        'ms_SG' => 'Malay (Singapore)',
        'mt_MT' => 'Maltese (Malta)',
        'mtr_IN' => 'Mewari (India)',
        'mup_IN' => 'Malvi (India)',
        'muw_IN' => 'Mundari (India)',
        'my_MM' => 'Burmese (Myanmar)',
        'myv_RU' => 'Erzya (Russia)',
        'na_NR' => 'Nauru (Nauru)',
        'nb_NO' => 'Norwegian Bokmal (Norway)',
        'nb_SJ' => 'Norwegian Bokmal (Svalbard and Jan Mayen)',
        'nd_ZW' => 'North Ndebele (Zimbabwe)',
        'ndc_MZ' => 'Ndau (Mozambique)',
        'ne_IN' => 'Nepali (India)',
        'ne_NP' => 'Nepali (Nepal)',
        'ng_NA' => 'Ndonga (Namibia)',
        'ngl_MZ' => 'Lomwe (Mozambique)',
        'niu_NU' => 'Niuean (Niue)',
        'nl_AN' => 'Dutch (Netherlands Antilles)',
        'nl_AW' => 'Dutch (Aruba)',
        'nl_BE' => 'Dutch (Belgium)',
        'nl_NL' => 'Dutch (Netherlands)',
        'nl_SR' => 'Dutch (Suriname)',
        'nn_NO' => 'Norwegian Nynorsk (Norway)',
        'nod_TH' => 'Thai, Northern (Thailand)',
        'noe_IN' => 'Nimadi (India)',
        'nso_ZA' => 'Northern Sotho (South Africa)',
        'ny_MW' => 'Nyanja (Malawi)',
        'ny_ZM' => 'Nyanja (Zambia)',
        'nyn_UG' => 'Nyankole (Uganda)',
        'om_ET' => 'Oromo (Ethiopia)',
        'or_IN' => 'Oriya (India)',
        'pa_IN' => 'Punjabi (India)',
        'pag_PH' => 'Pangasinan (Philippines)',
        'pap_AN' => 'Papiamento (Netherlands Antilles)',
        'pap_AW' => 'Papiamento (Aruba)',
        'pau_PW' => 'Palauan (Palau)',
        'pl_PL' => 'Polish (Poland)',
        'pl_UA' => 'Polish (Ukraine)',
        'pon_FM' => 'Pohnpeian (Micronesia)',
        'ps_AF' => 'Pashto (Afghanistan)',
        'ps_PK' => 'Pashto (Pakistan)',
        'pt_AO' => 'Portuguese (Angola)',
        'pt_BR' => 'Portuguese (Brazil)',
        'pt_CV' => 'Portuguese (Cape Verde)',
        'pt_GW' => 'Portuguese (Guinea-Bissau)',
        'pt_MZ' => 'Portuguese (Mozambique)',
        'pt_PT' => 'Portuguese (Portugal)',
        'pt_ST' => 'Portuguese (Sao Tome and Principe)',
        'pt_TL' => 'Portuguese (East Timor)',
        'qu_BO' => 'Quechua (Bolivia)',
        'qu_PE' => 'Quechua (Peru)',
        'rcf_RE' => 'R�union Creole French (Reunion)',
        'rej_ID' => 'Rejang (Indonesia)',
        'rif_MA' => 'Tarifit (Morocco)',
        'rjb_IN' => 'Rajbanshi (India)',
        'rm_CH' => 'Rhaeto-Romance (Switzerland)',
        'rmt_IR' => 'Domari (Iran)',
        'rn_BI' => 'Rundi (Burundi)',
        'ro_MD' => 'Romanian (Moldova)',
        'ro_RO' => 'Romanian (Romania)',
        'ro_RS' => 'Romanian (Serbia)',
        'ru_BY' => 'Russian (Belarus)',
        'ru_KG' => 'Russian (Kyrgyzstan)',
        'ru_KZ' => 'Russian (Kazakhstan)',
        'ru_RU' => 'Russian (Russia)',
        'ru_SJ' => 'Russian (Svalbard and Jan Mayen)',
        'ru_UA' => 'Russian (Ukraine)',
        'rw_RW' => 'Kinyarwanda (Rwanda)',
        'sa_IN' => 'Sanskrit (India)',
        'sah_RU' => 'Yakut (Russia)',
        'sas_ID' => 'Sasak (Indonesia)',
        'sat_IN' => 'Santali (India)',
        'sck_IN' => 'Sadri (India)',
        'sco_GB' => 'Scots (United Kingdom)',
        'sco_SCO' => 'Scots',
        'sd_IN' => 'Sindhi (India)',
        'sd_PK' => 'Sindhi (Pakistan)',
        'se_NO' => 'Northern Sami (Norway)',
        'sg_CF' => 'Sango (Central African Republic)',
        'si_LK' => 'Sinhalese (Sri Lanka)',
        'sid_ET' => 'Sidamo (Ethiopia)',
        'sk_RS' => 'Slovak (Serbia)',
        'sk_SK' => 'Slovak (Slovakia)',
        'sl_AT' => 'Slovenian (Austria)',
        'sl_SI' => 'Slovenian (Slovenia)',
        'sm_AS' => 'Samoan (American Samoa)',
        'sm_WS' => 'Samoan (Samoa)',
        'sn_ZW' => 'Shona (Zimbabwe)',
        'so_DJ' => 'Somali (Djibouti)',
        'so_ET' => 'Somali (Ethiopia)',
        'so_SO' => 'Somali (Somalia)',
        'sou_TH' => 'Thai, Southern (Thailand)',
        'sq_AL' => 'Albanian (Albania)',
        'sr_BA' => 'Serbian (Bosnia and Herzegovina)',
        'sr_ME' => 'Serbian (Montenegro)',
        'sr_RS' => 'Serbian (Serbia)',
        'ss_SZ' => 'Swati (Swaziland)',
        'ss_ZA' => 'Swati (South Africa)',
        'st_LS' => 'Southern Sotho (Lesotho)',
        'st_ZA' => 'Southern Sotho (South Africa)',
        'su_ID' => 'Sundanese (Indonesia)',
        'sv_AX' => 'Swedish (Aland Islands)',
        'sv_FI' => 'Swedish (Finland)',
        'sv_SE' => 'Swedish (Sweden)',
        'sw_KE' => 'Swahili (Kenya)',
        'sw_SO' => 'Swahili (Somalia)',
        'sw_TZ' => 'Swahili (Tanzania)',
        'sw_UG' => 'Swahili (Uganda)',
        'swb_KM' => 'Comorian (Comoros)',
        'swb_YT' => 'Comorian (Mayotte)',
        'swv_IN' => 'Shekhawati (India)',
        'ta_IN' => 'Tamil (India)',
        'ta_LK' => 'Tamil (Sri Lanka)',
        'ta_MY' => 'Tamil (Malaysia)',
        'ta_SG' => 'Tamil (Singapore)',
        'tcy_IN' => 'Tulu (India)',
        'te_IN' => 'Telugu (India)',
        'tet_TL' => 'Tetum (East Timor)',
        'tg_TJ' => 'Tajik (Tajikistan)',
        'th_TH' => 'Thai (Thailand)',
        'ti_ER' => 'Tigrinya (Eritrea)',
        'ti_ET' => 'Tigrinya (Ethiopia)',
        'tk_IR' => 'Turkmen (Iran)',
        'tk_TM' => 'Turkmen (Turkmenistan)',
        'tkl_TK' => 'Tokelau (Tokelau)',
        'tl_PH' => 'Tagalog (Philippines)',
        'tl_US' => 'Tagalog (United States)',
        'tn_BW' => 'Tswana (Botswana)',
        'tn_ZA' => 'Tswana (South Africa)',
        'to_TO' => 'Tonga (Tonga)',
        'tr_CY' => 'Turkish (Cyprus)',
        'tr_DE' => 'Turkish (Germany)',
        'tr_MK' => 'Turkish (Macedonia)',
        'tr_TR' => 'Turkish (Turkey)',
        'ts_MZ' => 'Tsonga (Mozambique)',
        'ts_ZA' => 'Tsonga (South Africa)',
        'tsg_PH' => 'Tausug (Philippines)',
        'tt_RU' => 'Tatar (Russia)',
        'tts_TH' => 'Thai, Northeastern (Thailand)',
        'tvl_TV' => 'Tuvalu (Tuvalu)',
        'tw_GH' => 'Twi (Ghana)',
        'ty_PF' => 'Tahitian (French Polynesia)',
        'tyv_RU' => 'Tuvinian (Russia)',
        'tzm_MA' => 'Tamazight, Central Atlas (Morocco)',
        'udm_RU' => 'Udmurt (Russia)',
        'ug_CN' => 'Uighur (China)',
        'uk_UA' => 'Ukrainian (Ukraine)',
        'uli_FM' => 'Ulithian (Micronesia)',
        'ur_IN' => 'Urdu (India)',
        'ur_PK' => 'Urdu (Pakistan)',
        'uz_AF' => 'Uzbek (Afghanistan)',
        'uz_UZ' => 'Uzbek (Uzbekistan)',
        've_ZA' => 'Venda (South Africa)',
        'vi_US' => 'Vietnamese (United States)',
        'vi_VN' => 'Vietnamese (Vietnam)',
        'vmw_MZ' => 'Waddar (Mozambique)',
        'wal_ET' => 'Walamo (Ethiopia)',
        'war_PH' => 'Waray (Philippines)',
        'wbq_IN' => 'Waddar (India)',
        'wbr_IN' => 'Wagdi (India)',
        'wo_MR' => 'Wolof (Mauritania)',
        'wo_SN' => 'Wolof (Senegal)',
        'wtm_IN' => 'Mewati (India)',
        'xh_ZA' => 'Xhosa (South Africa)',
        'xnr_IN' => 'Kangri (India)',
        'xog_UG' => 'Soga (Uganda)',
        'yap_FM' => 'Yapese (Micronesia)',
        'yo_NG' => 'Yoruba (Nigeria)',
        'za_CN' => 'Zhuang (China)',
        'zh_CN' => 'Chinese (China)',
        'zh_HK' => 'Chinese (Hong Kong SAR China)',
        'zh_MO' => 'Chinese (Macao SAR China)',
        'zh_SG' => 'Chinese (Singapore)',
        'zh_TW' => 'Chinese (Taiwan)',
        'zh_US' => 'Chinese (United States)',
        'zh_cmn' => 'Chinese (Mandarin)',
        'zh_yue' => 'Chinese (Cantonese)',
        'zu_ZA' => 'Zulu (South Africa)'
    ];

    /**
     * List of language names
     *
     * Language codes follow ISO 639-1 (2 letter), unless not present, in which case a locale will be
     * encoded in ISO 639-3 (3 letter). See https://en.wikipedia.org/wiki/ISO_639
     *
     * @config
     * @var array
     */
    private static $languages = [
        'af' => 'Afrikaans',
        'sq' => 'Albanian',
        'ar' => 'Arabic',
        'eu' => 'Basque',
        'be' => 'Belarusian',
        'bn' => 'Bengali',
        'bg' => 'Bulgarian',
        'ca' => 'Catalan',
        'zh' => 'Chinese',
        'hr' => 'Croatian',
        'cs' => 'Czech',
        'cy' => 'Welsh',
        'da' => 'Danish',
        'nl' => 'Dutch',
        'en' => 'English',
        'eo' => 'Esperanto',
        'et' => 'Estonian',
        'fo' => 'Faroese',
        'fi' => 'Finnish',
        'fr' => 'French',
        'gd' => 'Gaelic',
        'gl' => 'Galician',
        'de' => 'German',
        'el' => 'Greek',
        'gu' => 'Gujarati',
        'ha' => 'Hausa',
        'he' => 'Hebrew',
        'hi' => 'Hindi',
        'hu' => 'Hungarian',
        'is' => 'Icelandic',
        'io' => 'Ido',
        'id' => 'Indonesian',
        'ga' => 'Irish',
        'it' => 'Italian',
        'ja' => 'Japanese',
        'jv' => 'Javanese',
        'ko' => 'Korean',
        'ku' => 'Kurdish',
        'lv' => 'Latvian',
        'lt' => 'Lithuanian',
        'lmo' => 'Lombard',
        'mk' => 'Macedonian',
        'mi' => 'te reo Māori',
        'ms' => 'Malay',
        'mt' => 'Maltese',
        'mr' => 'Marathi',
        'ne' => 'Nepali',
        'nb' => 'Norwegian',
        'om' => 'Oromo',
        'fa' => 'Persian',
        'pl' => 'Polish',
        'pt' => 'Portuguese',
        'pa' => 'Punjabi',
        'qu' => 'Quechua',
        'rm' => 'Romansh',
        'ro' => 'Romanian',
        'ru' => 'Russian',
        'sco' => 'Scots',
        'sr' => 'Serbian',
        'sk' => 'Slovak',
        'sl' => 'Slovenian',
        'es' => 'Spanish',
        'sv' => 'Swedish',
        'tl' => 'Tagalog',
        'ta' => 'Tamil',
        'te' => 'Telugu',
        'to' => 'Tonga',
        'ts' => 'Tsonga',
        'tn' => 'Tswana',
        'tr' => 'Turkish',
        'tk' => 'Turkmen',
        'tw' => 'Twi',
        'uk' => 'Ukrainian',
        'ur' => 'Urdu',
        'uz' => 'Uzbek',
        've' => 'Venda',
        'vi' => 'Vietnamese',
        'wa' => 'Walloon',
        'wo' => 'Wolof',
        'xh' => 'Xhosa',
        'yi' => 'Yiddish',
        'zu' => 'Zulu',
    ];

    /**
     * Config for ltr/rtr of specific locales.
     * Will default to ltr.
     *
     * @config
     * @var array
     */
    private static $text_direction = [
        'ar' => 'rtl',
        'dv' => 'rtl',
        'fa' => 'rtl',
        'ha_Arab' => 'rtl',
        'he' => 'rtl',
        'ku' => 'rtl',
        'pa_Arab' => 'rtl',
        'ps' => 'rtl',
        'syr' => 'rtl',
        'ug' => 'rtl',
        'ur' => 'rtl',
        'uz_Arab' => 'rtl',
    ];

    /**
     * @config
     * @var array $likely_subtags Provides you "likely locales"
     * for a given "short" language code. This is a guess,
     * as we can't disambiguate from e.g. "en" to "en_US" - it
     * could also mean "en_UK".
     * @see http://www.unicode.org/cldr/data/charts/supplemental/likely_subtags.html
     */
    private static $likely_subtags = [
        'aa' => 'aa_ET',
        'ab' => 'ab_GE',
        'ady' => 'ady_RU',
        'af' => 'af_ZA',
        'ak' => 'ak_GH',
        'am' => 'am_ET',
        'ar' => 'ar_EG',
        'as' => 'as_IN',
        'ast' => 'ast_ES',
        'av' => 'av_RU',
        'ay' => 'ay_BO',
        'az' => 'az_AZ',
        'az_Cyrl' => 'az_AZ',
        'az_Arab' => 'az_IR',
        'az_IR' => 'az_IR',
        'ba' => 'ba_RU',
        'be' => 'be_BY',
        'bg' => 'bg_BG',
        'bi' => 'bi_VU',
        'bn' => 'bn_BD',
        'bo' => 'bo_CN',
        'bs' => 'bs_BA',
        'ca' => 'ca_ES',
        'ce' => 'ce_RU',
        'ceb' => 'ceb_PH',
        'ch' => 'ch_GU',
        'chk' => 'chk_FM',
        'crk' => 'crk_CA',
        'cs' => 'cs_CZ',
        'cwd' => 'cwd_CA',
        'cy' => 'cy_GB',
        'da' => 'da_DK',
        'de' => 'de_DE',
        'dv' => 'dv_MV',
        'dz' => 'dz_BT',
        'ee' => 'ee_GH',
        'efi' => 'efi_NG',
        'el' => 'el_GR',
        'en' => 'en_US',
        'es' => 'es_ES',
        'et' => 'et_EE',
        'eu' => 'eu_ES',
        'eo' => 'eo_XX',
        'fa' => 'fa_IR',
        'fi' => 'fi_FI',
        'fil' => 'fil_PH',
        'fj' => 'fj_FJ',
        'fo' => 'fo_FO',
        'fr' => 'fr_FR',
        'fur' => 'fur_IT',
        'fy' => 'fy_NL',
        'ga' => 'ga_IE',
        'gaa' => 'gaa_GH',
        'gd' => 'gd_GB',
        'gil' => 'gil_KI',
        'gl' => 'gl_ES',
        'gn' => 'gn_PY',
        'gu' => 'gu_IN',
        'ha' => 'ha_NG',
        'ha_Arab' => 'ha_SD',
        'ha_SD' => 'ha_SD',
        'haw' => 'haw_US',
        'he' => 'he_IL',
        'hi' => 'hi_IN',
        'hil' => 'hil_PH',
        'ho' => 'ho_PG',
        'hr' => 'hr_HR',
        'ht' => 'ht_HT',
        'hu' => 'hu_HU',
        'hy' => 'hy_AM',
        'id' => 'id_ID',
        'ig' => 'ig_NG',
        'ii' => 'ii_CN',
        'ilo' => 'ilo_PH',
        'inh' => 'inh_RU',
        'is' => 'is_IS',
        'it' => 'it_IT',
        'iu' => 'iu_CA',
        'ja' => 'ja_JP',
        'jv' => 'jv_ID',
        'ka' => 'ka_GE',
        'kaj' => 'kaj_NG',
        'kam' => 'kam_KE',
        'kbd' => 'kbd_RU',
        'kha' => 'kha_IN',
        'kk' => 'kk_KZ',
        'kl' => 'kl_GL',
        'km' => 'km_KH',
        'kn' => 'kn_IN',
        'ko' => 'ko_KR',
        'koi' => 'koi_RU',
        'kok' => 'kok_IN',
        'kos' => 'kos_FM',
        'kpe' => 'kpe_LR',
        'kpv' => 'kpv_RU',
        'krc' => 'krc_RU',
        'ks' => 'ks_IN',
        'ku' => 'ku_IQ',
        'ku_Latn' => 'ku_TR',
        'ku_TR' => 'ku_TR',
        'kum' => 'kum_RU',
        'kxm' => 'kxm_TH',
        'ky' => 'ky_KG',
        'la' => 'la_VA',
        'lah' => 'lah_PK',
        'lb' => 'lb_LU',
        'lbe' => 'lbe_RU',
        'lez' => 'lez_RU',
        'ln' => 'ln_CD',
        'lo' => 'lo_LA',
        'lt' => 'lt_LT',
        'lv' => 'lv_LV',
        'mai' => 'mai_IN',
        'mdf' => 'mdf_RU',
        'mdh' => 'mdh_PH',
        'mg' => 'mg_MG',
        'mh' => 'mh_MH',
        'mi' => 'mi_NZ',
        'mk' => 'mk_MK',
        'ml' => 'ml_IN',
        'mn' => 'mn_MN',
        'mn_CN' => 'mn_CN',
        'mn_Mong' => 'mn_CN',
        'mr' => 'mr_IN',
        'ms' => 'ms_MY',
        'mt' => 'mt_MT',
        'my' => 'my_MM',
        'myv' => 'myv_RU',
        'na' => 'na_NR',
        'nb' => 'nb_NO',
        'ne' => 'ne_NP',
        'niu' => 'niu_NU',
        'nl' => 'nl_NL',
        'nn' => 'nn_NO',
        'nr' => 'nr_ZA',
        'nso' => 'nso_ZA',
        'ny' => 'ny_MW',
        'om' => 'om_ET',
        'or' => 'or_IN',
        'os' => 'os_GE',
        'pa' => 'pa_IN',
        'pa_Arab' => 'pa_PK',
        'pa_PK' => 'pa_PK',
        'pag' => 'pag_PH',
        'pap' => 'pap_AN',
        'pau' => 'pau_PW',
        'pl' => 'pl_PL',
        'pon' => 'pon_FM',
        'ps' => 'ps_AF',
        'pt' => 'pt_PT',
        'qu' => 'qu_PE',
        'rm' => 'rm_CH',
        'rn' => 'rn_BI',
        'ro' => 'ro_RO',
        'ru' => 'ru_RU',
        'rw' => 'rw_RW',
        'sa' => 'sa_IN',
        'sah' => 'sah_RU',
        'sat' => 'sat_IN',
        'sd' => 'sd_IN',
        'se' => 'se_NO',
        'sg' => 'sg_CF',
        'si' => 'si_LK',
        'sid' => 'sid_ET',
        'sk' => 'sk_SK',
        'sl' => 'sl_SI',
        'sm' => 'sm_WS',
        'sn' => 'sn_ZW',
        'so' => 'so_SO',
        'sq' => 'sq_AL',
        'sr' => 'sr_RS',
        'ss' => 'ss_ZA',
        'st' => 'st_ZA',
        'su' => 'su_ID',
        'sv' => 'sv_SE',
        'sw' => 'sw_TZ',
        'swb' => 'swb_KM',
        'ta' => 'ta_IN',
        'te' => 'te_IN',
        'tet' => 'tet_TL',
        'tg' => 'tg_TJ',
        'th' => 'th_TH',
        'ti' => 'ti_ET',
        'tig' => 'tig_ER',
        'tk' => 'tk_TM',
        'tkl' => 'tkl_TK',
        'tl' => 'tl_PH',
        'tn' => 'tn_ZA',
        'to' => 'to_TO',
        'tpi' => 'tpi_PG',
        'tr' => 'tr_TR',
        'trv' => 'trv_TW',
        'ts' => 'ts_ZA',
        'tsg' => 'tsg_PH',
        'tt' => 'tt_RU',
        'tts' => 'tts_TH',
        'tvl' => 'tvl_TV',
        'tw' => 'tw_GH',
        'ty' => 'ty_PF',
        'tyv' => 'tyv_RU',
        'udm' => 'udm_RU',
        'ug' => 'ug_CN',
        'uk' => 'uk_UA',
        'uli' => 'uli_FM',
        'und' => 'en_US',
        'und_AD' => 'ca_AD',
        'und_AE' => 'ar_AE',
        'und_AF' => 'fa_AF',
        'und_AL' => 'sq_AL',
        'und_AM' => 'hy_AM',
        'und_AN' => 'pap_AN',
        'und_AO' => 'pt_AO',
        'und_AR' => 'es_AR',
        'und_AS' => 'sm_AS',
        'und_AT' => 'de_AT',
        'und_AW' => 'nl_AW',
        'und_AX' => 'sv_AX',
        'und_AZ' => 'az_AZ',
        'und_Arab' => 'ar_EG',
        'und_Arab_CN' => 'ug_CN',
        'und_Arab_DJ' => 'ar_DJ',
        'und_Arab_ER' => 'ar_ER',
        'und_Arab_IL' => 'ar_IL',
        'und_Arab_IN' => 'ur_IN',
        'und_Arab_PK' => 'ur_PK',
        'und_Armn' => 'hy_AM',
        'und_BA' => 'bs_BA',
        'und_BD' => 'bn_BD',
        'und_BE' => 'nl_BE',
        'und_BF' => 'fr_BF',
        'und_BG' => 'bg_BG',
        'und_BH' => 'ar_BH',
        'und_BI' => 'rn_BI',
        'und_BJ' => 'fr_BJ',
        'und_BL' => 'fr_BL',
        'und_BN' => 'ms_BN',
        'und_BO' => 'es_BO',
        'und_BR' => 'pt_BR',
        'und_BT' => 'dz_BT',
        'und_BY' => 'be_BY',
        'und_Beng' => 'bn_BD',
        'und_CD' => 'fr_CD',
        'und_CF' => 'sg_CF',
        'und_CG' => 'ln_CG',
        'und_CH' => 'de_CH',
        'und_CI' => 'fr_CI',
        'und_CL' => 'es_CL',
        'und_CM' => 'fr_CM',
        'und_CN' => 'zh_CN',
        'und_CO' => 'es_CO',
        'und_CR' => 'es_CR',
        'und_CU' => 'es_CU',
        'und_CV' => 'pt_CV',
        'und_CY' => 'el_CY',
        'und_CZ' => 'cs_CZ',
        'und_Cans' => 'cwd_CA',
        'und_Cyrl' => 'ru_RU',
        'und_Cyrl_BA' => 'sr_BA',
        'und_Cyrl_GE' => 'ab_GE',
        'und_DE' => 'de_DE',
        'und_DJ' => 'aa_DJ',
        'und_DK' => 'da_DK',
        'und_DO' => 'es_DO',
        'und_DZ' => 'ar_DZ',
        'und_Deva' => 'hi_IN',
        'und_EC' => 'es_EC',
        'und_EE' => 'et_EE',
        'und_EG' => 'ar_EG',
        'und_EH' => 'ar_EH',
        'und_ER' => 'ti_ER',
        'und_ES' => 'es_ES',
        'und_ET' => 'am_ET',
        'und_Ethi' => 'am_ET',
        'und_FI' => 'fi_FI',
        'und_FJ' => 'fj_FJ',
        'und_FM' => 'chk_FM',
        'und_FO' => 'fo_FO',
        'und_FR' => 'fr_FR',
        'und_GA' => 'fr_GA',
        'und_GE' => 'ka_GE',
        'und_GF' => 'fr_GF',
        'und_GH' => 'ak_GH',
        'und_GL' => 'kl_GL',
        'und_GN' => 'fr_GN',
        'und_GP' => 'fr_GP',
        'und_GQ' => 'fr_GQ',
        'und_GR' => 'el_GR',
        'und_GT' => 'es_GT',
        'und_GU' => 'ch_GU',
        'und_GW' => 'pt_GW',
        'und_Geor' => 'ka_GE',
        'und_Grek' => 'el_GR',
        'und_Gujr' => 'gu_IN',
        'und_Guru' => 'pa_IN',
        'und_HK' => 'zh_HK',
        'und_HN' => 'es_HN',
        'und_HR' => 'hr_HR',
        'und_HT' => 'ht_HT',
        'und_HU' => 'hu_HU',
        'und_Hani' => 'zh_CN',
        'und_Hans' => 'zh_CN',
        'und_Hant' => 'zh_TW',
        'und_Hebr' => 'he_IL',
        'und_ID' => 'id_ID',
        'und_IL' => 'he_IL',
        'und_IN' => 'hi_IN',
        'und_IQ' => 'ar_IQ',
        'und_IR' => 'fa_IR',
        'und_IS' => 'is_IS',
        'und_IT' => 'it_IT',
        'und_JO' => 'ar_JO',
        'und_JP' => 'ja_JP',
        'und_Jpan' => 'ja_JP',
        'und_KG' => 'ky_KG',
        'und_KH' => 'km_KH',
        'und_KM' => 'ar_KM',
        'und_KP' => 'ko_KP',
        'und_KR' => 'ko_KR',
        'und_KW' => 'ar_KW',
        'und_KZ' => 'ru_KZ',
        'und_Khmr' => 'km_KH',
        'und_Knda' => 'kn_IN',
        'und_Kore' => 'ko_KR',
        'und_LA' => 'lo_LA',
        'und_LB' => 'ar_LB',
        'und_LI' => 'de_LI',
        'und_LK' => 'si_LK',
        'und_LS' => 'st_LS',
        'und_LT' => 'lt_LT',
        'und_LU' => 'fr_LU',
        'und_LV' => 'lv_LV',
        'und_LY' => 'ar_LY',
        'und_Laoo' => 'lo_LA',
        'und_Latn_CN' => 'ii_CN',
        'und_Latn_CY' => 'tr_CY',
        'und_Latn_DZ' => 'fr_DZ',
        'und_Latn_ET' => 'om_ET',
        'und_Latn_KM' => 'fr_KM',
        'und_Latn_MA' => 'fr_MA',
        'und_Latn_MK' => 'sq_MK',
        'und_Latn_SY' => 'fr_SY',
        'und_Latn_TD' => 'fr_TD',
        'und_Latn_TN' => 'fr_TN',
        'und_MA' => 'ar_MA',
        'und_MC' => 'fr_MC',
        'und_MD' => 'ro_MD',
        'und_ME' => 'sr_ME',
        'und_MF' => 'fr_MF',
        'und_MG' => 'mg_MG',
        'und_MH' => 'mh_MH',
        'und_MK' => 'mk_MK',
        'und_ML' => 'fr_ML',
        'und_MM' => 'my_MM',
        'und_MN' => 'mn_MN',
        'und_MO' => 'zh_MO',
        'und_MQ' => 'fr_MQ',
        'und_MR' => 'ar_MR',
        'und_MT' => 'mt_MT',
        'und_MV' => 'dv_MV',
        'und_MW' => 'ny_MW',
        'und_MX' => 'es_MX',
        'und_MY' => 'ms_MY',
        'und_MZ' => 'pt_MZ',
        'und_Mlym' => 'ml_IN',
        'und_Mong' => 'mn_CN',
        'und_Mymr' => 'my_MM',
        'und_NC' => 'fr_NC',
        'und_NE' => 'ha_NE',
        'und_NG' => 'ha_NG',
        'und_NI' => 'es_NI',
        'und_NL' => 'nl_NL',
        'und_NO' => 'nb_NO',
        'und_NP' => 'ne_NP',
        'und_NR' => 'na_NR',
        'und_NU' => 'niu_NU',
        'und_OM' => 'ar_OM',
        'und_Orya' => 'or_IN',
        'und_PA' => 'es_PA',
        'und_PE' => 'es_PE',
        'und_PF' => 'ty_PF',
        'und_PG' => 'tpi_PG',
        'und_PH' => 'fil_PH',
        'und_PK' => 'ur_PK',
        'und_PL' => 'pl_PL',
        'und_PM' => 'fr_PM',
        'und_PR' => 'es_PR',
        'und_PS' => 'ar_PS',
        'und_PT' => 'pt_PT',
        'und_PW' => 'pau_PW',
        'und_PY' => 'gn_PY',
        'und_QA' => 'ar_QA',
        'und_RE' => 'fr_RE',
        'und_RO' => 'ro_RO',
        'und_RS' => 'sr_RS',
        'und_RU' => 'ru_RU',
        'und_RW' => 'rw_RW',
        'und_SA' => 'ar_SA',
        'und_SD' => 'ar_SD',
        'und_SE' => 'sv_SE',
        'und_SI' => 'sl_SI',
        'und_SJ' => 'nb_SJ',
        'und_SK' => 'sk_SK',
        'und_SM' => 'it_SM',
        'und_SN' => 'fr_SN',
        'und_SO' => 'so_SO',
        'und_SR' => 'nl_SR',
        'und_ST' => 'pt_ST',
        'und_SV' => 'es_SV',
        'und_SY' => 'ar_SY',
        'und_Sinh' => 'si_LK',
        'und_TD' => 'ar_TD',
        'und_TG' => 'ee_TG',
        'und_TH' => 'th_TH',
        'und_TJ' => 'tg_TJ',
        'und_TK' => 'tkl_TK',
        'und_TL' => 'tet_TL',
        'und_TM' => 'tk_TM',
        'und_TN' => 'ar_TN',
        'und_TO' => 'to_TO',
        'und_TR' => 'tr_TR',
        'und_TV' => 'tvl_TV',
        'und_TW' => 'zh_TW',
        'und_Taml' => 'ta_IN',
        'und_Telu' => 'te_IN',
        'und_Thaa' => 'dv_MV',
        'und_Thai' => 'th_TH',
        'und_Tibt' => 'bo_CN',
        'und_UA' => 'uk_UA',
        'und_UY' => 'es_UY',
        'und_UZ' => 'uz_UZ',
        'und_VA' => 'la_VA',
        'und_VE' => 'es_VE',
        'und_VN' => 'vi_VN',
        'und_VU' => 'fr_VU',
        'und_WF' => 'fr_WF',
        'und_WS' => 'sm_WS',
        'und_YE' => 'ar_YE',
        'und_YT' => 'fr_YT',
        'und_ZW' => 'sn_ZW',
        'ur' => 'ur_PK',
        'uz' => 'uz_UZ',
        'uz_AF' => 'uz_AF',
        'uz_Arab' => 'uz_AF',
        've' => 've_ZA',
        'vi' => 'vi_VN',
        'wal' => 'wal_ET',
        'war' => 'war_PH',
        'wo' => 'wo_SN',
        'xh' => 'xh_ZA',
        'yap' => 'yap_FM',
        'yo' => 'yo_NG',
        'za' => 'za_CN',
        'zh' => 'zh_CN',
        'zh_HK' => 'zh_HK',
        'zh_Hani' => 'zh_CN',
        'zh_Hant' => 'zh_TW',
        'zh_MO' => 'zh_MO',
        'zh_TW' => 'zh_TW',
        'zu' => 'zu_ZA',
    ];



    /**
     * Standard list of countries
     *
     * @var array
     */
    private static $countries = [
        'ad' => 'Andorra',
        'ae' => 'United Arab Emirates',
        'af' => 'Afghanistan',
        'ag' => 'Antigua and Barbuda',
        'ai' => 'Anguilla',
        'al' => 'Albania',
        'am' => 'Armenia',
        'an' => 'Netherlands Antilles',
        'ao' => 'Angola',
        'aq' => 'Antarctica',
        'ar' => 'Argentina',
        'as' => 'American Samoa',
        'at' => 'Austria',
        'au' => 'Australia',
        'aw' => 'Aruba',
        'ax' => 'Åland Islands',
        'az' => 'Azerbaijan',
        'ba' => 'Bosnia and Herzegovina',
        'bb' => 'Barbados',
        'bd' => 'Bangladesh',
        'be' => 'Belgium',
        'bf' => 'Burkina Faso',
        'bg' => 'Bulgaria',
        'bh' => 'Bahrain',
        'bi' => 'Burundi',
        'bj' => 'Benin',
        'bl' => 'Saint Barthélemy',
        'bm' => 'Bermuda',
        'bn' => 'Brunei',
        'bo' => 'Bolivia',
        'bq' => 'Bonaire, Sint Eustatius and Saba',
        'br' => 'Brazil',
        'bs' => 'Bahamas',
        'bt' => 'Bhutan',
        'bv' => 'Bouvet Island',
        'bw' => 'Botswana',
        'by' => 'Belarus',
        'bz' => 'Belize',
        'ca' => 'Canada',
        'cc' => 'Cocos [Keeling] Islands',
        'cd' => 'Congo - Kinshasa',
        'cf' => 'Central African Republic',
        'cg' => 'Congo - Brazzaville',
        'ch' => 'Switzerland',
        'ci' => 'Côte d’Ivoire',
        'ck' => 'Cook Islands',
        'cl' => 'Chile',
        'cm' => 'Cameroon',
        'cn' => 'China',
        'co' => 'Colombia',
        'cr' => 'Costa Rica',
        'cu' => 'Cuba',
        'cv' => 'Cape Verde',
        'cx' => 'Christmas Island',
        'cy' => 'Cyprus',
        'cz' => 'Czech Republic',
        'de' => 'Germany',
        'dj' => 'Djibouti',
        'dk' => 'Denmark',
        'dm' => 'Dominica',
        'do' => 'Dominican Republic',
        'dz' => 'Algeria',
        'ec' => 'Ecuador',
        'ee' => 'Estonia',
        'eg' => 'Egypt',
        'eh' => 'Western Sahara',
        'er' => 'Eritrea',
        'es' => 'Spain',
        'et' => 'Ethiopia',
        'fi' => 'Finland',
        'fj' => 'Fiji',
        'fk' => 'Falkland Islands',
        'fm' => 'Micronesia',
        'fo' => 'Faroe Islands',
        'fr' => 'France',
        'ga' => 'Gabon',
        'gb' => 'United Kingdom',
        'gd' => 'Grenada',
        'ge' => 'Georgia',
        'gf' => 'French Guiana',
        'gg' => 'Guernsey',
        'gh' => 'Ghana',
        'gi' => 'Gibraltar',
        'gl' => 'Greenland',
        'gm' => 'Gambia',
        'gn' => 'Guinea',
        'gp' => 'Guadeloupe',
        'gq' => 'Equatorial Guinea',
        'gr' => 'Greece',
        'gs' => 'South Georgia and the South Sandwich Islands',
        'gt' => 'Guatemala',
        'gu' => 'Guam',
        'gw' => 'Guinea-Bissau',
        'gy' => 'Guyana',
        'hk' => 'Hong Kong SAR China',
        'hm' => 'Heard Island and McDonald Islands',
        'hn' => 'Honduras',
        'hr' => 'Croatia',
        'ht' => 'Haiti',
        'hu' => 'Hungary',
        'id' => 'Indonesia',
        'ie' => 'Ireland',
        'il' => 'Israel',
        'im' => 'Isle of Man',
        'in' => 'India',
        'io' => 'British Indian Ocean Territory',
        'iq' => 'Iraq',
        'ir' => 'Iran',
        'is' => 'Iceland',
        'it' => 'Italy',
        'je' => 'Jersey',
        'jm' => 'Jamaica',
        'jo' => 'Jordan',
        'jp' => 'Japan',
        'ke' => 'Kenya',
        'kg' => 'Kyrgyzstan',
        'kh' => 'Cambodia',
        'ki' => 'Kiribati',
        'km' => 'Comoros',
        'kn' => 'Saint Kitts and Nevis',
        'kp' => 'North Korea',
        'kr' => 'South Korea',
        'kw' => 'Kuwait',
        'ky' => 'Cayman Islands',
        'kz' => 'Kazakhstan',
        'la' => 'Laos',
        'lb' => 'Lebanon',
        'lc' => 'Saint Lucia',
        'li' => 'Liechtenstein',
        'lk' => 'Sri Lanka',
        'lr' => 'Liberia',
        'ls' => 'Lesotho',
        'lt' => 'Lithuania',
        'lu' => 'Luxembourg',
        'lv' => 'Latvia',
        'ly' => 'Libya',
        'ma' => 'Morocco',
        'mc' => 'Monaco',
        'md' => 'Moldova',
        'me' => 'Montenegro',
        'mf' => 'Saint Martin',
        'mg' => 'Madagascar',
        'mh' => 'Marshall Islands',
        'mk' => 'Macedonia',
        'ml' => 'Mali',
        'mm' => 'Myanmar [Burma]',
        'mn' => 'Mongolia',
        'mo' => 'Macau SAR China',
        'mp' => 'Northern Mariana Islands',
        'mq' => 'Martinique',
        'mr' => 'Mauritania',
        'ms' => 'Montserrat',
        'mt' => 'Malta',
        'mu' => 'Mauritius',
        'mv' => 'Maldives',
        'mw' => 'Malawi',
        'mx' => 'Mexico',
        'my' => 'Malaysia',
        'mz' => 'Mozambique',
        'na' => 'Namibia',
        'nc' => 'New Caledonia',
        'ne' => 'Niger',
        'nf' => 'Norfolk Island',
        'ng' => 'Nigeria',
        'ni' => 'Nicaragua',
        'nl' => 'Netherlands',
        'no' => 'Norway',
        'np' => 'Nepal',
        'nr' => 'Nauru',
        'nu' => 'Niue',
        'nz' => 'New Zealand',
        'om' => 'Oman',
        'pa' => 'Panama',
        'pe' => 'Peru',
        'pf' => 'French Polynesia',
        'pg' => 'Papua New Guinea',
        'ph' => 'Philippines',
        'pk' => 'Pakistan',
        'pl' => 'Poland',
        'pm' => 'Saint Pierre and Miquelon',
        'pn' => 'Pitcairn Islands',
        'pr' => 'Puerto Rico',
        'ps' => 'Palestinian Territories',
        'pt' => 'Portugal',
        'pw' => 'Palau',
        'py' => 'Paraguay',
        'qa' => 'Qatar',
        'qo' => 'Outlying Oceania',
        're' => 'Réunion',
        'ro' => 'Romania',
        'rs' => 'Serbia',
        'ru' => 'Russia',
        'rw' => 'Rwanda',
        'sa' => 'Saudi Arabia',
        'sb' => 'Solomon Islands',
        'sc' => 'Seychelles',
        'sd' => 'Sudan',
        'se' => 'Sweden',
        'sg' => 'Singapore',
        'sh' => 'Saint Helena',
        'si' => 'Slovenia',
        'sj' => 'Svalbard and Jan Mayen',
        'sk' => 'Slovakia',
        'sl' => 'Sierra Leone',
        'sm' => 'San Marino',
        'sn' => 'Senegal',
        'so' => 'Somalia',
        'sr' => 'Suriname',
        'st' => 'São Tomé and Príncipe',
        'sv' => 'El Salvador',
        'sy' => 'Syria',
        'sz' => 'Swaziland',
        'tc' => 'Turks and Caicos Islands',
        'td' => 'Chad',
        'tf' => 'French Southern Territories',
        'tg' => 'Togo',
        'th' => 'Thailand',
        'tj' => 'Tajikistan',
        'tk' => 'Tokelau',
        'tl' => 'Timor-Leste',
        'tm' => 'Turkmenistan',
        'tn' => 'Tunisia',
        'to' => 'Tonga',
        'tr' => 'Turkey',
        'tt' => 'Trinidad and Tobago',
        'tv' => 'Tuvalu',
        'tw' => 'Taiwan',
        'tz' => 'Tanzania',
        'ua' => 'Ukraine',
        'ug' => 'Uganda',
        'um' => 'U.S. Minor Outlying Islands',
        'us' => 'United States',
        'uy' => 'Uruguay',
        'uz' => 'Uzbekistan',
        'va' => 'Vatican City',
        'vc' => 'Saint Vincent and the Grenadines',
        've' => 'Venezuela',
        'vg' => 'British Virgin Islands',
        'vi' => 'U.S. Virgin Islands',
        'vn' => 'Vietnam',
        'vu' => 'Vanuatu',
        'wf' => 'Wallis and Futuna',
        'ws' => 'Samoa',
        'ye' => 'Yemen',
        'yt' => 'Mayotte',
        'za' => 'South Africa',
        'zm' => 'Zambia',
        'zw' => 'Zimbabwe',
    ];

    /**
     * Returns the script direction in format compatible with the HTML "dir" attribute.
     *
     * @see http://www.w3.org/International/tutorials/bidi-xhtml/
     * @param string $locale Optional locale incl. region (underscored)
     * @return string "rtl" or "ltr"
     */
    public function scriptDirection($locale = null)
    {
        $dirs = static::config()->get('text_direction');
        if (!$locale) {
            $locale = i18n::get_locale();
        }
        if (isset($dirs[$locale])) {
            return $dirs[$locale];
        }
        $lang = $this->langFromLocale($locale);
        if (isset($dirs[$lang])) {
            return $dirs[$lang];
        }
        return 'ltr';
    }

    /**
     * Provides you "likely locales"
     * for a given "short" language code. This is a guess,
     * as we can't disambiguate from e.g. "en" to "en_US" - it
     * could also mean "en_UK". Based on the Unicode CLDR
     * project.
     * @see http://www.unicode.org/cldr/data/charts/supplemental/likely_subtags.html
     *
     * @param string $lang Short language code, e.g. "en"
     * @return string Long locale, e.g. "en_US"
     */
    public function localeFromLang($lang)
    {
        $lang = Locale::canonicalize($lang);

        // Check subtags
        $subtags = $this->config()->get('likely_subtags');
        if (isset($subtags[$lang])) {
            return $subtags[$lang];
        }

        // Search locales
        $locales = $this->config()->get('locales');
        foreach ($locales as $locale => $name) {
            if (Locale::filterMatches($lang, $locale)) {
                return $locale;
            }
        }

        // Default to lang_LANG
        return strtolower($lang ?? '') . '_' . strtoupper($lang ?? '');
    }

    /**
     * Returns the "short" language name from a locale,
     * e.g. "en_US" would return "en".
     *
     * @param string $locale E.g. "en_US"
     * @return string Short language code, e.g. "en"
     */
    public function langFromLocale($locale)
    {
        return Locale::getPrimaryLanguage($locale);
    }

    /**
     * Cache of localised locales, keyed by locale localised in
     *
     * @var array
     */
    private static $cache_locales = [];

    /**
     * Get all locale codes and names
     *
     * @return array Map of locale code => name
     */
    public function getLocales()
    {
        // Cache by locale
        $locale = i18n::get_locale();
        if (!empty(static::$cache_locales[$locale])) {
            return static::$cache_locales[$locale];
        }

        // Localise all locales
        $locales = $this->config()->get('locales');
        $localised = [];
        foreach ($locales as $code => $default) {
            $localised[$code] = $this->localeName($code);
        }

        // Save cache
        static::$cache_locales[$locale] = $localised;
        return $localised;
    }

    /**
     * Cache of localised languages, keyed by locale localised in
     *
     * @var array
     */
    private static $cache_languages = [];

    /**
     * Get all language codes and names
     *
     * @return array Map of language code => name
     */
    public function getLanguages()
    {
        // Cache by locale
        $locale = i18n::get_locale();
        if (!empty(static::$cache_languages[$locale])) {
            return static::$cache_languages[$locale];
        }

        // Localise all languages
        $languages = $this->config()->get('languages');
        $localised = [];
        foreach ($languages as $code => $default) {
            $localised[$code] = $this->languageName($code);
        }

        // Save cache
        static::$cache_languages[$locale] = $localised;
        return $localised;
    }

    /**
     * Get name of locale
     *
     * @param string $locale
     * @return string
     */
    public function localeName($locale)
    {
        return Locale::getDisplayName($locale, i18n::get_locale());
    }

    /**
     * Get language name for this language or locale code
     *
     * @param string $code
     * @return string
     */
    public function languageName($code)
    {
        return Locale::getDisplayLanguage($code, i18n::get_locale());
    }

    /**
     * Cache of localised countries, keyed by locale localised in
     *
     * @var array
     */
    private static $cache_countries = [];

    /**
     * Get all country codes and names
     *
     * @return array Map of country code => name
     */
    public function getCountries()
    {
        // Cache by locale
        $locale = i18n::get_locale();
        if (!empty(static::$cache_countries[$locale])) {
            return static::$cache_countries[$locale];
        }

        // Localise all countries
        $countries = $this->config()->get('countries');
        $localised = [];
        foreach ($countries as $code => $default) {
            $localised[$code] = $this->countryName($code);
        }

        // Always sort by localised name, not key
        $collator = new Collator(i18n::get_locale());
        $collator->asort($localised);
        static::$cache_countries[$locale] = $localised;
        return $localised;
    }

    /**
     * Get name of country
     *
     * @param string $code ISO 3166-1 country code
     * @return string
     */
    public function countryName($code)
    {
        return Locale::getDisplayRegion('-' . $code, i18n::get_locale());
    }

    /**
     * Returns the country code / suffix on any locale
     *
     * @param string $locale E.g. "en_US"
     * @return string Country code, e.g. "us"
     */
    public function countryFromLocale($locale)
    {
        return strtolower(Locale::getRegion($locale) ?? '') ?: null;
    }

    /**
     * Validates a "long" locale format (e.g. "en_US") by checking it against {@link $locales}.
     *
     * @param string $locale
     * @return bool
     */
    public function validate($locale)
    {
        if (!$locale) {
            return false;
        }
        $lang = $this->langFromLocale($locale);
        $region = $this->countryFromLocale($locale);
        if (!$lang || !$region) {
            return false;
        }

        // Check the configurable whitelist
        $localeCode = strtolower($lang ?? '') . '_' . strtoupper($region ?? '');
        $locales = $this->getLocales();

        if (array_key_exists($localeCode, $locales ?? [])
            || array_key_exists(strtolower($localeCode ?? ''), $locales ?? [])
        ) {
            return true;
        }

        // Fallback
        return strcasecmp($lang ?? '', $region ?? '')
            && strcasecmp($lang ?? '', $locale ?? '')
            && strcasecmp($region ?? '', $locale ?? '');
    }

    /**
     * Reset the local cache of this object
     */
    public static function reset()
    {
        static::$cache_countries = [];
        static::$cache_languages = [];
        static::$cache_locales = [];
    }
}
