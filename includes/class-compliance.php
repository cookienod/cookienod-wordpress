<?php
/**
 * Compliance Module - GDPR, CCPA, LGPD, POPIA, etc.
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_Compliance
 * Handles multi-jurisdiction compliance requirements
 */
class CookieNod_Compliance {

    /**
     * Supported regulations
     */
    private $regulations = array(
        'gdpr' => array(
            'name' => 'GDPR',
            'full_name' => 'General Data Protection Regulation',
            'regions' => array('EU', 'EEA', 'UK'),
            'cookie_wall' => false,
            'prior_consent' => true,
            'withdraw_consent' => true,
            'cookie_categories' => true,
            'data_controller_info' => true,
            'purpose_specification' => true,
            'retention_period' => true,
        ),
        'ccpa' => array(
            'name' => 'CCPA/CPRA',
            'full_name' => 'California Consumer Privacy Act',
            'regions' => array('California, USA'),
            'cookie_wall' => false,
            'prior_consent' => false, // Opt-out model
            'do_not_sell' => true,
            'cookie_categories' => true,
        ),
        'lgpd' => array(
            'name' => 'LGPD',
            'full_name' => 'Lei Geral de Proteção de Dados',
            'regions' => array('Brazil'),
            'cookie_wall' => false,
            'prior_consent' => true,
            'similar_to' => 'gdpr',
        ),
        'popia' => array(
            'name' => 'POPIA',
            'full_name' => 'Protection of Personal Information Act',
            'regions' => array('South Africa'),
            'cookie_wall' => false,
            'prior_consent' => true,
            'similar_to' => 'gdpr',
        ),
        'pipeda' => array(
            'name' => 'PIPEDA',
            'full_name' => 'Personal Information Protection and Electronic Documents Act',
            'regions' => array('Canada'),
            'cookie_wall' => false,
            'prior_consent' => true,
            'implied_consent_timeout' => 6, // months
        ),
        'apa' => array(
            'name' => 'APA',
            'full_name' => 'Australia Privacy Act',
            'regions' => array('Australia'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'nigerian_ndpr' => array(
            'name' => 'NDPR',
            'full_name' => 'Nigeria Data Protection Regulation',
            'regions' => array('Nigeria'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'singapore_pdpa' => array(
            'name' => 'PDPA',
            'full_name' => 'Personal Data Protection Act',
            'regions' => array('Singapore'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'thailand_pdpa' => array(
            'name' => 'PDPA',
            'full_name' => 'Personal Data Protection Act B.E. 2562',
            'regions' => array('Thailand'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'japan_apPI' => array(
            'name' => 'APPI',
            'full_name' => 'Act on Protection of Personal Information',
            'regions' => array('Japan'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'south_korea_pipa' => array(
            'name' => 'PIPA',
            'full_name' => 'Personal Information Protection Act',
            'regions' => array('South Korea'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'indonesia_pdp' => array(
            'name' => 'PDP Law',
            'full_name' => 'Personal Data Protection Law',
            'regions' => array('Indonesia'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'vietnam_pdpl' => array(
            'name' => 'PDPD',
            'full_name' => 'Personal Data Protection Decree',
            'regions' => array('Vietnam'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'uae_pdpl' => array(
            'name' => 'PDPL',
            'full_name' => 'Personal Data Protection Law',
            'regions' => array('UAE'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'qatar_pdpl' => array(
            'name' => 'PDPL',
            'full_name' => 'Personal Data Privacy Protection Law',
            'regions' => array('Qatar'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'saudi_spdil' => array(
            'name' => 'PDPL',
            'full_name' => 'Personal Data Protection Law',
            'regions' => array('Saudi Arabia'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'egypt_pdpl' => array(
            'name' => 'PDPL',
            'full_name' => 'Personal Data Protection Law',
            'regions' => array('Egypt'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'kenya_odpc' => array(
            'name' => 'DPA',
            'full_name' => 'Data Protection Act',
            'regions' => array('Kenya'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'ghana_dpa' => array(
            'name' => 'DPA',
            'full_name' => 'Data Protection Act',
            'regions' => array('Ghana'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'turkey_kvkk' => array(
            'name' => 'KVKK',
            'full_name' => 'Kişisel Verilerin Korunması Kanunu',
            'regions' => array('Turkey'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
        'russia_fz152' => array(
            'name' => 'FZ-152',
            'full_name' => 'Federal Law on Personal Data',
            'regions' => array('Russia'),
            'cookie_wall' => false,
            'prior_consent' => true,
            'local_storage' => true, // Requires local data storage
        ),
        'china_pip' => array(
            'name' => 'PIPL',
            'full_name' => 'Personal Information Protection Law',
            'regions' => array('China'),
            'cookie_wall' => false,
            'prior_consent' => true,
            'separate_consent' => true, // For each purpose
        ),
        'india_dpdp' => array(
            'name' => 'DPDP Act',
            'full_name' => 'Digital Personal Data Protection Act',
            'regions' => array('India'),
            'cookie_wall' => false,
            'prior_consent' => true,
        ),
    );

    /**
     * Get regulation details
     */
    public function get_regulation($key) {
        return isset($this->regulations[$key]) ? $this->regulations[$key] : null;
    }

    /**
     * Get all regulations
     */
    public function get_all_regulations() {
        return $this->regulations;
    }

    /**
     * Get regulations by region
     */
    public function get_regulations_by_region($region) {
        $result = array();
        foreach ($this->regulations as $key => $reg) {
            if (in_array($region, $reg['regions'])) {
                $result[$key] = $reg;
            }
        }
        return $result;
    }

    /**
     * Auto-detect regulation based on settings
     */
    public function auto_detect_regulation() {
        $options = get_option('cookienod_wp_options', array());

        // Check if auto-detect is enabled
        if (!empty($options['auto_detect_law'])) {
            // Get visitor's country (requires GeoIP)
            $country = $this->get_visitor_country();

            if ($country) {
                return $this->get_regulation_for_country($country);
            }
        }

        return $options['regulation'] ?? 'gdpr';
    }

    /**
     * Get regulation for country
     */
    public function get_regulation_for_country($country_code) {
        $eu_countries = array('AT', 'BE', 'BG', 'HR', 'CY', 'CZ', 'DK', 'EE', 'FI', 'FR', 'DE', 'GR', 'HU', 'IE', 'IT', 'LV', 'LT', 'LU', 'MT', 'NL', 'PL', 'PT', 'RO', 'SK', 'SI', 'ES', 'SE');
        $eea_countries = array('IS', 'LI', 'NO');

        if (in_array($country_code, $eu_countries) || in_array($country_code, $eea_countries) || $country_code === 'GB') {
            return 'gdpr';
        }

        $country_regulations = array(
            'BR' => 'lgpd',
            'ZA' => 'popia',
            'CA' => 'pipeda',
            'AU' => 'apa',
            'NG' => 'nigerian_ndpr',
            'SG' => 'singapore_pdpa',
            'TH' => 'thailand_pdpa',
            'JP' => 'japan_apPI',
            'KR' => 'south_korea_pipa',
            'ID' => 'indonesia_pdp',
            'VN' => 'vietnam_pdpl',
            'AE' => 'uae_pdpl',
            'QA' => 'qatar_pdpl',
            'SA' => 'saudi_spdil',
            'EG' => 'egypt_pdpl',
            'KE' => 'kenya_odpc',
            'GH' => 'ghana_dpa',
            'TR' => 'turkey_kvkk',
            'RU' => 'russia_fz152',
            'CN' => 'china_pip',
            'IN' => 'india_dpdp',
            'US' => 'ccpa', // California specifically, but used as US default
        );

        return isset($country_regulations[$country_code]) ? $country_regulations[$country_code] : 'gdpr';
    }

    /**
     * Get visitor country (requires GeoIP)
     */
    private function get_visitor_country() {
        // Try Cloudflare
        if (isset($_SERVER['HTTP_CF_IPCOUNTRY'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Country code sanitized
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_CF_IPCOUNTRY']));
        }

        // Try MaxMind if available
        if (function_exists('geoip_country_code_by_name')) {
            $ip = $this->get_visitor_ip();
            return @geoip_country_code_by_name($ip);
        }

        // Try WP Engine GeoIP
        if (isset($_SERVER['HTTP_X_GEO_COUNTRY'])) {
            // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Country code sanitized
            return sanitize_text_field(wp_unslash($_SERVER['HTTP_X_GEO_COUNTRY']));
        }

        return null;
    }

    /**
     * Get visitor IP
     */
    private function get_visitor_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- IP addresses sanitized after unslash
                $ips = explode(',', sanitize_text_field(wp_unslash($_SERVER[$key])));
                return trim($ips[0]);
            }
        }
        return '';
    }

    /**
     * Get consent text for regulation
     */
    public function get_consent_text($regulation = 'gdpr', $type = 'banner') {
        $texts = array(
            'gdpr' => array(
                'banner' => __('We use cookies to enhance your browsing experience, serve personalized content, and analyze our traffic. By clicking "Accept All", you consent to our use of cookies.', 'cookienod'),
                'necessary' => __('Necessary cookies are essential for the website to function properly.', 'cookienod'),
                'functional' => __('Functional cookies help perform certain functionalities like sharing website content on social media platforms.', 'cookienod'),
                'analytics' => __('Analytics cookies help us understand how visitors interact with our website.', 'cookienod'),
                'marketing' => __('Marketing cookies are used to deliver personalized advertisements.', 'cookienod'),
            ),
            'ccpa' => array(
                'banner' => __('We use cookies and similar technologies to improve your experience and for advertising purposes. You can customize your preferences or opt out of the sale of personal information.', 'cookienod'),
                'do_not_sell' => __('Do Not Sell My Personal Information', 'cookienod'),
            ),
            'lgpd' => array(
                'banner' => __('Utilizamos cookies para melhorar sua experiça e analisar nosso tráfego. Ao clicar em "Aceitar Todos", você concorda com o uso de cookies.', 'cookienod'),
            ),
        );

        return isset($texts[$regulation][$type]) ? $texts[$regulation][$type] : $texts['gdpr'][$type];
    }

    /**
     * Get required cookie categories for regulation
     */
    public function get_required_categories($regulation = 'gdpr') {
        $reg = $this->get_regulation($regulation);

        if (!$reg || !isset($reg['cookie_categories'])) {
            return array('necessary');
        }

        if ($reg['cookie_categories']) {
            return array('necessary', 'functional', 'analytics', 'marketing');
        }

        return array('necessary');
    }

    /**
     * Check if prior consent is required
     */
    public function requires_prior_consent($regulation = 'gdpr') {
        $reg = $this->get_regulation($regulation);
        return isset($reg['prior_consent']) ? $reg['prior_consent'] : true;
    }
}