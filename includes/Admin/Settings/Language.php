<?php

namespace Airalo\Admin\Settings;

class Language
{

    public static function get_all_languages(): array {
        return [
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
            'es' => 'Spanish',
            'tl' => 'Tagalog',
            'th' => 'Thai',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
        ];
    }
}
