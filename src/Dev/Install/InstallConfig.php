<?php

namespace SilverStripe\Dev\Install;

/**
 * Provides environment settings from the current request + environment
 *
 * @skipUpgrade
 */
class InstallConfig
{
    /**
     * List of preferred DB classes in order
     *
     * @var array
     */
    protected $preferredDatabases = [
        'MySQLPDODatabase',
        'MySQLDatabase',
    ];

    /**
     * Get database config from the current environment
     *
     * @param array $request Request object
     * @param array $databaseClasses Supported database config
     * @return array
     */
    public function getDatabaseConfig($request, $databaseClasses)
    {
        // Get config from request
        if (isset($request['db']['type'])) {
            $type = $request['db']['type'];
            if (isset($request['db'][$type])) {
                return array_merge(
                    [ 'type' => $type ],
                    $request['db'][$type]
                );
            }
        }

        // Guess database config
        return [
            'type' => $this->getDatabaseClass($databaseClasses),
            'server' => getenv('SS_DATABASE_SERVER') ?: 'localhost',
            'username' => getenv('SS_DATABASE_USERNAME') ?: 'root',
            'password' => getenv('SS_DATABASE_PASSWORD') ?: '',
            'database' => getenv('SS_DATABASE_NAME') ?: 'SS_mysite',
        ];
    }

    /**
     * Get admin config from the environment
     *
     * @param array $request
     * @return array
     */
    public function getAdminConfig($request)
    {
        if (isset($request['admin'])) {
            return $request['admin'];
        }

        return [
            'username' => getenv('SS_DEFAULT_ADMIN_USERNAME') ?: 'admin',
            'password' => getenv('SS_DEFAULT_ADMIN_PASSWORD') ?: '',
        ];
    }

    /**
     * Check if this site has already been installed
     *
     * @return bool
     */
    public function alreadyInstalled()
    {
        if (file_exists($this->getEnvPath())) {
            return true;
        }
        if (!file_exists($this->getConfigPath())) {
            return false;
        }
        $configContents = file_get_contents($this->getConfigPath());
        if (strstr($configContents, '$databaseConfig')) {
            return true;
        }
        if (strstr($configContents, '$database')) {
            return true;
        }
        return false;
    }

    /**
     * @return string
     */
    protected function getConfigPath()
    {
        return BASE_PATH . '/mysite/_config.php';
    }

    /**
     * @return string
     */
    protected function getEnvPath()
    {
        return BASE_PATH . '/.env';
    }

    /**
     * Database configs available for configuration
     *
     * @param array $databaseClasses
     * @return string
     */
    protected function getDatabaseClass($databaseClasses)
    {
        if (getenv('SS_DATABASE_CLASS')) {
            return getenv('SS_DATABASE_CLASS');
        }

        // Check supported versions
        foreach ($this->preferredDatabases as $candidate) {
            if (!empty($databaseClasses[$candidate]['supported'])) {
                return $candidate;
            }
        }
        return null;
    }

    /**
     * Get string representation of the framework version
     *
     * @return string
     */
    public function getFrameworkVersion()
    {
        $composerLockPath = BASE_PATH . '/composer.lock';
        if (!file_exists($composerLockPath)) {
            return 'unknown';
        }
        $lockData = json_decode(file_get_contents($composerLockPath), true);
        if (json_last_error() || empty($lockData['packages'])) {
            return 'unknown';
        }
        foreach ($lockData['packages'] as $package) {
            if ($package['name'] === 'silverstripe/framework') {
                return $package['version'];
            }
        }
        return 'unknown';
    }

    /**
     * Check if stats should be sent
     *
     * @param array $request
     * @return bool
     */
    public function canSendStats($request)
    {
        return !empty($request['stats']);
    }

    /**
     * Get configured locales
     *
     * @return array
     */
    public function getLocales()
    {
        return [
            'af_ZA' => 'Afrikaans (South Africa)',
            'ar_EG' => 'Arabic (Egypt)',
            'hy_AM' => 'Armenian (Armenia)',
            'ast_ES' => 'Asturian (Spain)',
            'az_AZ' => 'Azerbaijani (Azerbaijan)',
            'bs_BA' => 'Bosnian (Bosnia and Herzegovina)',
            'bg_BG' => 'Bulgarian (Bulgaria)',
            'ca_ES' => 'Catalan (Spain)',
            'zh_CN' => 'Chinese (China)',
            'zh_TW' => 'Chinese (Taiwan)',
            'hr_HR' => 'Croatian (Croatia)',
            'cs_CZ' => 'Czech (Czech Republic)',
            'da_DK' => 'Danish (Denmark)',
            'nl_NL' => 'Dutch (Netherlands)',
            'en_GB' => 'English (United Kingdom)',
            'en_US' => 'English (United States)',
            'eo_XX' => 'Esperanto',
            'et_EE' => 'Estonian (Estonia)',
            'fo_FO' => 'Faroese (Faroe Islands)',
            'fi_FI' => 'Finnish (Finland)',
            'fr_FR' => 'French (France)',
            'de_DE' => 'German (Germany)',
            'el_GR' => 'Greek (Greece)',
            'he_IL' => 'Hebrew (Israel)',
            'hu_HU' => 'Hungarian (Hungary)',
            'is_IS' => 'Icelandic (Iceland)',
            'id_ID' => 'Indonesian (Indonesia)',
            'it_IT' => 'Italian (Italy)',
            'ja_JP' => 'Japanese (Japan)',
            'km_KH' => 'Khmer (Cambodia)',
            'lc_XX' => 'LOLCAT',
            'lv_LV' => 'Latvian (Latvia)',
            'lt_LT' => 'Lithuanian (Lithuania)',
            'ms_MY' => 'Malay (Malaysia)',
            'mi_NZ' => 'Maori (New Zealand)',
            'ne_NP' => 'Nepali (Nepal)',
            'nb_NO' => 'Norwegian',
            'fa_IR' => 'Persian (Iran)',
            'pl_PL' => 'Polish (Poland)',
            'pt_BR' => 'Portuguese (Brazil)',
            'pa_IN' => 'Punjabi (India)',
            'ro_RO' => 'Romanian (Romania)',
            'ru_RU' => 'Russian (Russia)',
            'sr_RS' => 'Serbian (Serbia)',
            'si_LK' => 'Sinhalese (Sri Lanka)',
            'sk_SK' => 'Slovak (Slovakia)',
            'sl_SI' => 'Slovenian (Slovenia)',
            'es_AR' => 'Spanish (Argentina)',
            'es_MX' => 'Spanish (Mexico)',
            'es_ES' => 'Spanish (Spain)',
            'sv_SE' => 'Swedish (Sweden)',
            'th_TH' => 'Thai (Thailand)',
            'tr_TR' => 'Turkish (Turkey)',
            'uk_UA' => 'Ukrainian (Ukraine)',
            'uz_UZ' => 'Uzbek (Uzbekistan)',
            'vi_VN' => 'Vietnamese (Vietnam)',
        ];
    }

    /**
     * Get theme selected
     *
     * @param $request
     * @return string
     */
    public function getTheme($request)
    {
        if (isset($request['template'])) {
            return $request['template'];
        }
        // Default theme
        return 'simple';
    }
}
