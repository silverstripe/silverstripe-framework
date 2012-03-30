<?php
/**
 * Routines for IP to country resolution.
 * 
 * @package sapphire
 * @subpackage misc
 */
class Geoip {
	
	public static $default_country_code = false;

	/** 
	 * ISO 3166 Country Codes
	 * 
	 * Includes additional codes for Europe,
	 * Asia Pacific Region,Anonymous Proxies
	 * & Satellite Provider.
	 */
	protected static $iso_3166_countryCodes = array(
		'A1' => "Anonymous Proxy",
		'A2' => "Satellite Provider",
		'A3' => "Internal Netwrok",
		'AD' => "Andorra",
		'AE' => "United Arab Emirates",
		'AF' => "Afghanistan",
		'AG' => "Antigua and Barbuda",
		'AI' => "Anguilla",
		'AL' => "Albania",
		'AM' => "Armenia",
		'AN' => "Netherlands Antilles",
		'AO' => "Angola",
		'AP' => "Asia/Pacific Region",
		'AQ' => "Antarctica",
		'AR' => "Argentina",
		'AS' => "American Samoa",
		'AT' => "Austria",
		'AU' => "Australia",
		'AW' => "Aruba",
		'AZ' => "Azerbaijan",
		'BA' => "Bosnia and Herzegovina",
		'BB' => "Barbados",
		'BD' => "Bangladesh",
		'BE' => "Belgium",
		'BF' => "Burkina Faso",
		'BG' => "Bulgaria",
		'BH' => "Bahrain",
		'BI' => "Burundi",
		'BJ' => "Benin",
		'BM' => "Bermuda",
		'BN' => "Brunei Darussalam",
		'BO' => "Bolivia",
		'BR' => "Brazil",
		'BS' => "Bahamas",
		'BT' => "Bhutan",
		'BV' => "Bouvet Island",
		'BW' => "Botswana",
		'BY' => "Belarus",
		'BZ' => "Belize",
		'CA' => "Canada",
		'CC' => "Cocos (Keeling) Islands",
		'CF' => "Central African Republic",
		'CG' => "Congo",
		'CH' => "Switzerland",
		'CI' => "Cote D'Ivoire",
		'CK' => "Cook Islands",
		'CL' => "Chile",
		'CM' => "Cameroon",
		'CN' => "China",
		'CO' => "Colombia",
		'CR' => "Costa Rica",
		'CU' => "Cuba",
		'CV' => "Cape Verde",
		'CX' => "Christmas Island",
		'CY' => "Cyprus",
		'CZ' => "Czech Republic",
		'DE' => "Germany",
		'DJ' => "Djibouti",
		'DK' => "Denmark",
		'DM' => "Dominica",
		'DO' => "Dominican Republic",
		'DZ' => "Algeria",
		'EC' => "Ecuador",
		'EE' => "Estonia",
		'EG' => "Egypt",
		'EH' => "Western Sahara",
		'ER' => "Eritrea",
		'ES' => "Spain",
		'ET' => "Ethiopia",
		'EU' => "Europe",
		'FI' => "Finland",
		'FJ' => "Fiji",
		'FK' => "Falkland Islands (Malvinas)",
		'FM' => "Micronesia - Federated States of",
		'FO' => "Faroe Islands",
		'FR' => "France",
		'FX' => "France (Metropolitan)",
		'GA' => "Gabon",
		'GB' => "United Kingdom",
		'GD' => "Grenada",
		'GE' => "Georgia",
		'GF' => "French Guiana",
		'GH' => "Ghana",
		'GI' => "Gibraltar",
		'GL' => "Greenland",
		'GM' => "Gambia",
		'GN' => "Guinea",
		'GP' => "Guadeloupe",
		'GQ' => "Equatorial Guinea",
		'GR' => "Greece",
		'GS' => "South Georgia and the South Sandwich Islands",
		'GT' => "Guatemala",
		'GU' => "Guam",
		'GW' => "Guinea-Bissau",
		'GY' => "Guyana",
		'HK' => "Hong Kong",
		'HM' => "Heard Island and McDonald Islands",
		'HN' => "Honduras",
		'HR' => "Croatia",
		'HT' => "Haiti",
		'HU' => "Hungary",
		'ID' => "Indonesia",
		'IE' => "Ireland",
		'IL' => "Israel",
		'IN' => "India",
		'IO' => "British Indian Ocean Territory",
		'IQ' => "Iraq",
		'IR' => "Iran - Islamic Republic of",
		'IS' => "Iceland",
		'IT' => "Italy",
		'JM' => "Jamaica",
		'JO' => "Jordan",
		'JP' => "Japan",
		'KE' => "Kenya",
		'KG' => "Kyrgyzstan",
		'KH' => "Cambodia",
		'KI' => "Kiribati",
		'KM' => "Comoros",
		'KN' => "Saint Kitts and Nevis",
		'KP' => "Korea - Democratic People's Republic of",
		'KR' => "Korea - Republic of",
		'KW' => "Kuwait",
		'KY' => "Cayman Islands",
		'KZ' => "Kazakhstan",
		'LA' => "Lao People's Democratic Republic",
		'LB' => "Lebanon",
		'LC' => "Saint Lucia",
		'LI' => "Liechtenstein",
		'LK' => "Sri Lanka",
		'LR' => "Liberia",
		'LS' => "Lesotho",
		'LT' => "Lithuania",
		'LU' => "Luxembourg",
		'LV' => "Latvia",
		'LY' => "Libyan Arab Jamahiriya",
		'MA' => "Morocco",
		'MC' => "Monaco",
		'MD' => "Moldova - Republic of",
		'ME' => "Montenegro",
		'MG' => "Madagascar",
		'MH' => "Marshall Islands",
		'MK' => "Macedonia - the Former Yugoslav Republic of",
		'ML' => "Mali",
		'MM' => "Myanmar",
		'MN' => "Mongolia",
		'MO' => "Macao",
		'MP' => "Northern Mariana Islands",
		'MQ' => "Martinique",
		'MR' => "Mauritania",
		'MS' => "Montserrat",
		'MT' => "Malta",
		'MU' => "Mauritius",
		'MV' => "Maldives",
		'MW' => "Malawi",
		'MX' => "Mexico",
		'MY' => "Malaysia",
		'MZ' => "Mozambique",
		'NA' => "Namibia",
		'NC' => "New Caledonia",
		'NE' => "Niger",
		'NF' => "Norfolk Island",
		'NG' => "Nigeria",
		'NI' => "Nicaragua",
		'NL' => "Netherlands",
		'NO' => "Norway",
		'NP' => "Nepal",
		'NR' => "Nauru",
		'NU' => "Niue",
		'NZ' => "New Zealand",
		'OM' => "Oman",
		'PA' => "Panama",
		'PE' => "Peru",
		'PF' => "French Polynesia",
		'PG' => "Papua New Guinea",
		'PH' => "Philippines",
		'PK' => "Pakistan",
		'PL' => "Poland",
		'PM' => "Saint Pierre and Miquelon",
		'PN' => "Pitcairn",
		'PR' => "Puerto Rico",
		'PS' => "Palestinian Territory - Occupied",
		'PT' => "Portugal",
		'PW' => "Palau",
		'PY' => "Paraguay",
		'QA' => "Qatar",
		'RE' => "Reunion",
		'RO' => "Romania",
		'RS' => "Serbia",
		'RU' => "Russian Federation",
		'RW' => "Rwanda",
		'SA' => "Saudi Arabia",
		'SB' => "Solomon Islands",
		'SC' => "Seychelles",
		'SD' => "Sudan",
		'SE' => "Sweden",
		'SG' => "Singapore",
		'SH' => "Saint Helena",
		'SI' => "Slovenia",
		'SJ' => "Svalbard and Jan Mayen",
		'SK' => "Slovakia",
		'SL' => "Sierra Leone",
		'SM' => "San Marino",
		'SN' => "Senegal",
		'SO' => "Somalia",
		'SR' => "Suriname",
		'ST' => "Sao Tome and Principe",
		'SV' => "El Salvador",
		'SY' => "Syrian Arab Republic",
		'SZ' => "Swaziland",
		'TC' => "Turks and Caicos Islands",
		'TD' => "Chad",
		'TF' => "French Southern Territories",
		'TG' => "Togo",
		'TH' => "Thailand",
		'TJ' => "Tajikistan",
		'TK' => "Tokelau",
		'TL' => "East Timor",
		'TM' => "Turkmenistan",
		'TN' => "Tunisia",
		'TO' => "Tonga",
		'TR' => "Turkey",
		'TT' => "Trinidad and Tobago",
		'TV' => "Tuvalu",
		'TW' => "Taiwan",
		'TZ' => "Tanzania (United Republic of)",
		'UA' => "Ukraine",
		'UG' => "Uganda",
		'UM' => "United States Minor Outlying Islands",
		'US' => "United States",
		'UY' => "Uruguay",
		'UZ' => "Uzbekistan",
		'VA' => "Holy See (Vatican City State)",
		'VC' => "Saint Vincent and the Grenadines",
		'VE' => "Venezuela",
		'VG' => "Virgin Islands - British",
		'VI' => "Virgin Islands - U.S.",
		'VN' => "Vietnam",
		'VU' => "Vanuatu",
		'WF' => "Wallis and Futuna",
		'WS' => "Samoa",
		'YE' => "Yemen",
		'YT' => "Mayotte",
		'ZA' => "South Africa",
		'ZM' => "Zambia",
		'ZR' => "Zaire",
		'ZW' => "Zimbabwe"
	);
	
	/** 
	 * Find the country for an IP address.
	 * 
	 * By default, it will return an array, keyed by
	 * the country code with a value of the country
	 * name.
	 * 
	 * To return the code only, pass in true for the
	 * $codeOnly parameter.
	 * 
	 * @param string $address The IP address to get the country of
	 * @param boolean $codeOnly Returns just the country code
	 */
	static function ip2country($address, $codeOnly = false) {

		// Return if in CLI, or you'll get this error: "sh: geoiplookup: command not found"
		if(Director::is_cli() || !function_exists('exec')) return false;
		
		$cmd = 'geoiplookup ' . escapeshellarg($address);
		exec($cmd, $result, $code);
		// Note: At time of writing, $result is always zero for this program

		if($code == 127) return false;
		if($result == false) return false;
		
		// Always returns one line of code, e.g. :
		// Geoip Country Edition: GB, United Kingdom
		// NZ
		$country = $result[0];

		$start = strpos($country, ':');
		if($start) $start += 2;
		$code = substr($country, $start, 2); // skip space

		if($code == 'IP' || $code == '--') {
			 if(self::$default_country_code) {
			 	$code = self::$default_country_code;
			 } else {
			 	return false;
			 }
		}
		
		if(!$codeOnly) {
			$name = substr($country, $start + 4);
			if(!$name) $name = $this->countryCode2name($code);
			
			return array('code' => $code, 'name' => $name);
		} else {
			return $code;
		}
	}

	/**
	 * Returns the country code, for the current visitor
	 */
	static function visitor_country() {
		if( ereg('^dev(\\.|$)', $_SERVER['HTTP_HOST']) && isset($_GET['country'])) return $_GET['country'];
		else if(isset($_SERVER['REMOTE_ADDR'])) return Geoip::ip2country($_SERVER['REMOTE_ADDR'], true);
	}
	
	/** 
	 * Sanity Checker for this class, which helps us debug,
	 * or ensure that its working as expected 
	 */
	static function ip2country_check() {
		global $ss_disableFeatures;
		if($ss_disableFeatures['geoip']) return;
		// sanity check for ip2country, to ensure that it is working as expected.
		
		$checks = array(
			'www.paradise.net.nz' => array('code'=>'NZ','name'=>'New Zealand'),
			'news.com.au'  			 => array('code'=>'AU','name'=>'Australia'),
			'www.google.com' 		 => array('code'=>'US','name'=>'United States'),
			'a.b.c.d.e.f.g' 		 => false, // test failure :)
		);
			
		$status = true;
		
		foreach ($checks as $url => $expectedResponse) {
			$response = self::ip2country($url);
			
			if(!$response && $expectedResponse) {
				user_error("ip2country_check failed sanity check: ip2country($url) returned false. Expected code: '$expectedResponse'", E_USER_WARNING);
				$status = false;
			} elseif ($response != $expectedResponse) {
				user_error("ip2country_check failed sanity check: ip2country($url) returned code: '$response[code]/$response[name]'. Expected code: '$expectedResponse[code]/$expectedResponse[name]'", E_USER_WARNING);
				$status = false;
			}
		}
		
		return $status;
	}

	/** 
	 * Returns the country name from the appropriate code.
	 * @return null|string String if country found, null if none found
	 */
	static function countryCode2name($code) {
		$name = isset(Geoip::$iso_3166_countryCodes[$code]) ? Geoip::$iso_3166_countryCodes[$code] : null;
		return $name;
	}

	/** 
	 * Returns an array of ISO Country Codes -> Country Names
	 */
	static function getCountryDropDown() {
		$dropdown = Geoip::$iso_3166_countryCodes;
		unset($dropdown['A1']);
		unset($dropdown['A2']);
		unset($dropdown['A3']);	
		asort($dropdown);
		return $dropdown;
	}
}
?>
