<?php
class _DevblocksTranslationManager {
	private $_locales = array();
	private $_locale = 'en_US';
	
	private function __construct() {}
	
	static function getInstance() {
		static $instance = null;
		
		if(null == $instance) {
			$instance = new _DevblocksTranslationManager();
		}
		
		return $instance;
	}
	
	public function addLocale($locale, $strings) {
		$this->_locales[$locale] = $strings;
	}
	
	public function setLocale($locale) {
		if(isset($this->_locales[$locale]))
			$this->_locale = $locale;
	}
	
	public function _($token) {
		if(isset($this->_locales[$this->_locale][$token]))
			return $this->_locales[$this->_locale][$token];
		
		// [JAS] Make it easy to find things that don't translate
		//return '$'.$token.'('.$this->_locale.')';
		
		return $token;
	}
	
	public function getLocaleCodes() {
		return array(
			'af_ZA',
			'am_ET',
			'be_BY',
			'bg_BG',
			'ca_ES',
			'cs_CZ',
			'da_DK',
			'de_AT',
			'de_CH',
			'de_DE',
			'el_GR',
			'en_AU',
			'en_CA',
			'en_GB',
			'en_IE',
			'en_NZ',
			'en_US',
			'es_ES',
			'es_MX',
			'et_EE',
			'eu_ES',
			'fi_FI',
			'fr_BE',
			'fr_CA',
			'fr_CH',
			'fr_FR',
			'he_IL',
			'hr_HR',
			'hu_HU',
			'hy_AM',
			'is_IS',
			'it_CH',
			'it_IT',
			'ja_JP',
			'kk_KZ',
			'ko_KR',
			'lt_LT',
			'nl_BE',
			'nl_NL',
			'no_NO',
			'pl_PL',
			'pt_BR',
			'pt_PT',
			'ro_RO',
			'ru_RU',
			'sk_SK',
			'sl_SI',
			'sr_RS',
			'sv_SE',
			'tr_TR',
			'uk_UA',
			'zh_CN',
			'zh_HK',
			'zh_TW',
		);
	}
	
	function getLocaleStrings() {
		$codes = $this->getLocaleCodes();
		$langs = $this->getLanguageCodes();
		$countries = $this->getCountryCodes();
		
		$lang_codes = array();
		
		if(is_array($codes))
		foreach($codes as $code) {
			$data = explode('_', $code);
			@$lang = $langs[strtolower($data[0])];
			@$terr = $countries[strtoupper($data[1])];

			$lang_codes[$code] = (!empty($lang) && !empty($terr))
				? ($lang . ' (' . $terr . ')')
				: $code;
		}
		
		asort($lang_codes);
		
		unset($codes);
		unset($langs);
		unset($countries);
		
		return $lang_codes;
	}
	
	function getLanguageCodes() {
		return array(
			'aa' => "Afar",
			'ab' => "Abkhazian",
			'ae' => "Avestan",
			'af' => "Afrikaans",
			'am' => "Amharic",
			'an' => "Aragonese",
			'ar' => "Arabic",
			'as' => "Assamese",
			'ay' => "Aymara",
			'az' => "Azerbaijani",
			'ba' => "Bashkir",
			'be' => "Belarusian",
			'bg' => "Bulgarian",
			'bh' => "Bihari",
			'bi' => "Bislama",
			'bn' => "Bengali",
			'bo' => "Tibetan",
			'br' => "Breton",
			'bs' => "Bosnian",
			'ca' => "Catalan",
			'ce' => "Chechen",
			'ch' => "Chamorro",
			'co' => "Corsican",
			'cs' => "Czech",
			'cu' => "Church Slavic; Slavonic; Old Bulgarian",
			'cv' => "Chuvash",
			'cy' => "Welsh",
			'da' => "Danish",
			'de' => "German",
			'dv' => "Divehi; Dhivehi; Maldivian",
			'dz' => "Dzongkha",
			'el' => "Greek, Modern",
			'en' => "English",
			'eo' => "Esperanto",
			'es' => "Spanish; Castilian",
			'et' => "Estonian",
			'eu' => "Basque",
			'fa' => "Persian",
			'fi' => "Finnish",
			'fj' => "Fijian",
			'fo' => "Faroese",
			'fr' => "French",
			'fy' => "Western Frisian",
			'ga' => "Irish",
			'gd' => "Gaelic; Scottish Gaelic",
			'gl' => "Galician",
			'gn' => "Guarani",
			'gu' => "Gujarati",
			'gv' => "Manx",
			'ha' => "Hausa",
			'he' => "Hebrew",
			'hi' => "Hindi",
			'ho' => "Hiri Motu",
			'hr' => "Croatian",
			'ht' => "Haitian; Haitian Creole ",
			'hu' => "Hungarian",
			'hy' => "Armenian",
			'hz' => "Herero",
			'ia' => "Interlingua",
			'id' => "Indonesian",
			'ie' => "Interlingue",
			'ii' => "Sichuan Yi",
			'ik' => "Inupiaq",
			'io' => "Ido",
			'is' => "Icelandic",
			'it' => "Italian",
			'iu' => "Inuktitut",
			'ja' => "Japanese",
			'jv' => "Javanese",
			'ka' => "Georgian",
			'ki' => "Kikuyu; Gikuyu",
			'kj' => "Kuanyama; Kwanyama",
			'kk' => "Kazakh",
			'kl' => "Kalaallisut",
			'km' => "Khmer",
			'kn' => "Kannada",
			'ko' => "Korean",
			'ks' => "Kashmiri",
			'ku' => "Kurdish",
			'kv' => "Komi",
			'kw' => "Cornish",
			'ky' => "Kirghiz",
			'la' => "Latin",
			'lb' => "Luxembourgish; Letzeburgesch",
			'li' => "Limburgan; Limburger; Limburgish",
			'ln' => "Lingala",
			'lo' => "Lao",
			'lt' => "Lithuanian",
			'lv' => "Latvian",
			'mg' => "Malagasy",
			'mh' => "Marshallese",
			'mi' => "Maori",
			'mk' => "Macedonian",
			'ml' => "Malayalam",
			'mn' => "Mongolian",
			'mo' => "Moldavian",
			'mr' => "Marathi",
			'ms' => "Malay",
			'mt' => "Maltese",
			'my' => "Burmese",
			'na' => "Nauru",
			'nb' => "Norwegian Bokmal",
			'nd' => "Ndebele, North",
			'ne' => "Nepali",
			'ng' => "Ndonga",
			'nl' => "Dutch",
			'nn' => "Norwegian Nynorsk",
			'no' => "Norwegian",
			'nr' => "Ndebele, South",
			'nv' => "Navaho, Navajo",
			'ny' => "Nyanja; Chichewa; Chewa",
			'oc' => "Occitan; Provencal",
			'om' => "Oromo",
			'or' => "Oriya",
			'os' => "Ossetian; Ossetic",
			'pa' => "Panjabi",
			'pi' => "Pali",
			'pl' => "Polish",
			'ps' => "Pushto",
			'pt' => "Portuguese",
			'qu' => "Quechua",
			'rm' => "Raeto-Romance",
			'rn' => "Rundi",
			'ro' => "Romanian",
			'ru' => "Russian",
			'rw' => "Kinyarwanda",
			'sa' => "Sanskrit",
			'sc' => "Sardinian",
			'sd' => "Sindhi",
			'se' => "Northern Sami",
			'sg' => "Sango",
			'si' => "Sinhala; Sinhalese",
			'sk' => "Slovak",
			'sl' => "Slovenian",
			'sm' => "Samoan",
			'sn' => "Shona",
			'so' => "Somali",
			'sq' => "Albanian",
			'sr' => "Serbian",
			'ss' => "Swati",
			'st' => "Sotho, Southern",
			'su' => "Sundanese",
			'sv' => "Swedish",
			'sw' => "Swahili",
			'ta' => "Tamil",
			'te' => "Telugu",
			'tg' => "Tajik",
			'th' => "Thai",
			'ti' => "Tigrinya",
			'tk' => "Turkmen",
			'tl' => "Tagalog",
			'tn' => "Tswana",
			'to' => "Tonga",
			'tr' => "Turkish",
			'ts' => "Tsonga",
			'tt' => "Tatar",
			'tw' => "Twi",
			'ty' => "Tahitian",
			'ug' => "Uighur",
			'uk' => "Ukrainian",
			'ur' => "Urdu",
			'uz' => "Uzbek",
			'vi' => "Vietnamese",
			'vo' => "Volapuk",
			'wa' => "Walloon",
			'wo' => "Wolof",
			'xh' => "Xhosa",
			'yi' => "Yiddish",
			'yo' => "Yoruba",
			'za' => "Zhuang; Chuang",
			'zh' => "Chinese",
			'zu' => "Zulu",
		);
	}
	
	function getCountryCodes() {
		return array(
			'AD' => "Andorra",
			'AE' => "United Arab Emirates",
			'AF' => "Afghanistan",
			'AG' => "Antigua and Barbuda",
			'AI' => "Anguilla",
			'AL' => "Albania",
			'AM' => "Armenia",
			'AN' => "Netherlands Antilles",
			'AO' => "Angola",
			'AQ' => "Antarctica",
			'AR' => "Argentina",
			'AS' => "American Samoa",
			'AT' => "Austria",
			'AU' => "Australia",
			'AW' => "Aruba",
			'AX' => "Aland Islands",
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
			'BL' => "Saint Barthélemy",
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
			'CD' => "Congo, the Democratic Republic of the",
			'CF' => "Central African Republic",
			'CG' => "Congo",
			'CH' => "Switzerland",
			'CI' => "Cote d'Ivoire Côte d'Ivoire",
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
			'FI' => "Finland",
			'FJ' => "Fiji",
			'FK' => "Falkland Islands (Malvinas)",
			'FM' => "Micronesia, Federated States of",
			'FO' => "Faroe Islands",
			'FR' => "France",
			'GA' => "Gabon",
			'GB' => "United Kingdom",
			'GD' => "Grenada",
			'GE' => "Georgia",
			'GF' => "French Guiana",
			'GG' => "Guernsey",
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
			'IM' => "Isle of Man",
			'IN' => "India",
			'IO' => "British Indian Ocean Territory",
			'IQ' => "Iraq",
			'IR' => "Iran, Islamic Republic of",
			'IS' => "Iceland",
			'IT' => "Italy",
			'JE' => "Jersey",
			'JM' => "Jamaica",
			'JO' => "Jordan",
			'JP' => "Japan",
			'KE' => "Kenya",
			'KG' => "Kyrgyzstan",
			'KH' => "Cambodia",
			'KI' => "Kiribati",
			'KM' => "Comoros",
			'KN' => "Saint Kitts and Nevis",
			'KP' => "Korea, Democratic People's Republic of",
			'KR' => "Korea, Republic of",
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
			'MD' => "Moldova, Republic of",
			'ME' => "Montenegro",
			'MF' => "Saint Martin (French part)",
			'MG' => "Madagascar",
			'MH' => "Marshall Islands",
			'MK' => "Macedonia, the former Yugoslav Republic of",
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
			'PS' => "Palestinian Territory, Occupied",
			'PT' => "Portugal",
			'PW' => "Palau",
			'PY' => "Paraguay",
			'QA' => "Qatar",
			'RE' => "Reunion Réunion",
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
			'TL' => "Timor-Leste",
			'TM' => "Turkmenistan",
			'TN' => "Tunisia",
			'TO' => "Tonga",
			'TR' => "Turkey",
			'TT' => "Trinidad and Tobago",
			'TV' => "Tuvalu",
			'TW' => "Taiwan, Province of China",
			'TZ' => "Tanzania, United Republic of",
			'UA' => "Ukraine",
			'UG' => "Uganda",
			'UM' => "United States Minor Outlying Islands",
			'US' => "United States",
			'UY' => "Uruguay",
			'UZ' => "Uzbekistan",
			'VA' => "Holy See (Vatican City State)",
			'VC' => "Saint Vincent and the Grenadines",
			'VE' => "Venezuela",
			'VG' => "Virgin Islands, British",
			'VI' => "Virgin Islands, U.S.",
			'VN' => "Viet Nam",
			'VU' => "Vanuatu",
			'WF' => "Wallis and Futuna",
			'WS' => "Samoa",
			'YE' => "Yemen",
			'YT' => "Mayotte",
			'ZA' => "South Africa",
			'ZM' => "Zambia",
			'ZW' => "Zimbabwe",
		);
	}
};