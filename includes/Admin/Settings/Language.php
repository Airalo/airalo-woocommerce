<?php

namespace Airalo\Admin\Settings;

class Language {

	public static function get_all_languages(): array {
		$languages = [
			'ar' => 'Arabic',
			'zh' => 'Chinese',
			'cs' => 'Czech',
			'nl' => 'Dutch',
			'en' => 'English',
			'fr' => 'French',
			'ka' => 'Georgian',
			'de' => 'German',
			'el' => 'Greek',
			'he' => 'Hebrew',
			'hi' => 'Hindi',
			'it' => 'Italian',
			'ja' => 'Japanese',
			'ko' => 'Korean',
			'pl' => 'Polish',
			'pt' => 'Portuguese',
			'ru' => 'Russian',
			'tl' => 'Tagalog',
			'th' => 'Thai',
			'tr' => 'Turkish',
			'uk' => 'Ukrainian',
			'zh-CN' => 'Chinese (Simplified)',
			'pt-BR' => 'Portuguese (Brazil)',
			'es-419' => 'Spanish (Latin America)',
			'hy-AM' => 'Armenian',
			'zh-TW' => 'Chinese (Traditional)',
			'vls-BE' => 'Vlaams',
			'pt-PT' => 'Portuguese (Portugal)',
			'sr-CS' => 'Serbian (Cyrillic)',
			'es-ES' => 'Spanish (Spain)',
			'sv-SE' => 'Swedish',
			'ur-PK' => 'Urdu',
			'fil' => 'Filipino',
			'sq' => 'Albanian',
			'az' => 'Azerbaijani',
			'bg' => 'Bulgarian',
			'hr' => 'Croatian',
			'da' => 'Danish',
			'et' => 'Estonian',
			'fi' => 'Finnish',
			'hun' => 'Hungarian',
			'kmr' => 'Kurdish',
			'ky' => 'Kyrgyz',
			'lv' => 'Latvian',
			'lt' => 'Lithuanian',
			'mk' => 'Macedonian',
			'ms' => 'Malay',
			'nb' => 'Norwegian',
			'ro' => 'Romanian',
			'sk' => 'Slovak',
			'uz' => 'Uzbek',
			'vi' => 'Vietnamese',
		];

		asort( $languages );

		return $languages;
	}
}
