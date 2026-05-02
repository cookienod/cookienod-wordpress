<?php
/**
 * Google Consent Mode v2 Integration
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_Google_Consent_Mode
 * Handles Google Consent Mode v2 integration
 */
class CookieNod_Google_Consent_Mode {

    /**
     * GCM v2 consent types
     */
    private $consent_types = array(
        'ad_storage' => array(
            'label' => 'Advertising Storage',
            'category' => 'marketing',
            'description' => 'Enables storage related to advertising',
        ),
        'analytics_storage' => array(
            'label' => 'Analytics Storage',
            'category' => 'analytics',
            'description' => 'Enables storage related to analytics',
        ),
        'functionality_storage' => array(
            'label' => 'Functionality Storage',
            'category' => 'functional',
            'description' => 'Enables storage that supports site functionality',
        ),
        'personalization_storage' => array(
            'label' => 'Personalization Storage',
            'category' => 'functional',
            'description' => 'Enables storage related to personalization',
        ),
        'security_storage' => array(
            'label' => 'Security Storage',
            'category' => 'necessary',
            'description' => 'Enables storage related to security',
            'always_granted' => true,
        ),
        'ad_user_data' => array(
            'label' => 'Ad User Data',
            'category' => 'marketing',
            'description' => 'Sets consent for sending user data to Google for advertising purposes (v2)',
            'v2_only' => true,
        ),
        'ad_personalization' => array(
            'label' => 'Ad Personalization',
            'category' => 'marketing',
            'description' => 'Sets consent for personalized advertising (v2)',
            'v2_only' => true,
        ),
    );

    /**
     * Cookie category mapping to GCM types
     */
    private $category_mapping = array(
        'necessary' => array('security_storage'),
        'functional' => array('functionality_storage', 'personalization_storage'),
        'analytics' => array('analytics_storage'),
        'marketing' => array('ad_storage', 'ad_user_data', 'ad_personalization'),
    );

    /**
     * Options
     */
    private $options;

    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_option('cookienod_wp_options', array());
        $this->init();
    }

    /**
     * Initialize hooks
     */
    private function init() {
        // Add GCM script to head (high priority, before GTM)
        add_action('wp_head', array($this, 'output_gcm_default_consent'), 1);

        // Update GCM when consent changes
        add_action('wp_footer', array($this, 'output_gcm_update_script'), 100);

        // Disable GTM if not consented
        add_filter('gtm4wp_gtm_container_code', array($this, 'filter_gtm_container'), 10, 2);

        // Disable analytics scripts
        add_action('wp_enqueue_scripts', array($this, 'dequeue_analytics_scripts'), 100);

        // Add GCM to cookienod JS
        add_filter('cookienod_script_attributes', array($this, 'add_gcm_attributes'));
    }

    /**
     * Output default consent state
     */
    public function output_gcm_default_consent() {
        if (!$this->is_enabled()) {
            return;
        }

        $default_state = $this->get_default_consent_state();

        // Register and enqueue a handle for GCM default consent script
        wp_register_script('cookienod-gcm-default', false, array(), COOKIENOD_VERSION, false);
        wp_enqueue_script('cookienod-gcm-default');

        $inline_script = "/* Google Consent Mode v2 - Default Consent */\n";
        $inline_script .= "window.dataLayer = window.dataLayer || [];\n";
        $inline_script .= "function gtag(){dataLayer.push(arguments);}\n";
        $inline_script .= "gtag('consent', 'default', " . wp_json_encode($default_state) . ");\n";
        $inline_script .= "gtag('set', 'url_passthrough', true);\n";
        $inline_script .= "gtag('set', 'ads_data_redaction', true);\n";
        $inline_script .= "gtag('event', 'consent_init', {'consent_source': 'cookienod_wp', 'consent_mode_version': '2.0'});\n";

        wp_add_inline_script('cookienod-gcm-default', $inline_script);

        // Also output for Tag Manager if enabled
        if ($this->options['gcm_enable_gtm'] ?? true) {
            $this->output_gtm_consent_state();
        }
    }

    /**
     * Get default consent state (denied by default)
     */
    private function get_default_consent_state() {
        $state = array();

        // Get visitor's saved consent if available
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Cookie value will be validated by json_decode
        $saved_consent = isset($_COOKIE['cookienod_consent']) ? json_decode(sanitize_text_field(wp_unslash($_COOKIE['cookienod_consent'])), true) : array();

        foreach ($this->consent_types as $type => $config) {
            // Security storage is always granted
            if (!empty($config['always_granted'])) {
                $state[$type] = 'granted';
                continue;
            }

            // Check saved consent
            if (!empty($saved_consent[$config['category']])) {
                $state[$type] = 'granted';
            } else {
                $state[$type] = 'denied';
            }
        }

        // Add wait_for_update for async consent detection
        $state['wait_for_update'] = 500;

        return $state;
    }

    /**
     * Output GTM-specific consent state using wp_add_inline_script
     */
    private function output_gtm_consent_state() {
        wp_register_script('cookienod-gcm-gtm', false, array(), COOKIENOD_VERSION, false);
        wp_enqueue_script('cookienod-gcm-gtm');

        $inline_script = "/* For Google Tag Manager */\n";
        $inline_script .= "(function(){\n";
        $inline_script .= "  var consentData = window.dataLayer.find(function(item){ return item[0]==='consent' && item[1]==='default'; });\n";
        $inline_script .= "  if(consentData){ window.dataLayer.push({'event':'default_consent','consent':consentData[2]}); }\n";
        $inline_script .= "})();\n";

        wp_add_inline_script('cookienod-gcm-gtm', $inline_script);
    }

    /**
     * Output GCM update script using wp_add_inline_script
     */
    public function output_gcm_update_script() {
        if (!$this->is_enabled()) {
            return;
        }

        wp_register_script('cookienod-gcm-update', false, array(), COOKIENOD_VERSION, false);
        wp_enqueue_script('cookienod-gcm-update');

        $gcm_mapping = $this->category_mapping;
        $inline_script = "/* Google Consent Mode v2 - Consent Update Handler */\n";
        $inline_script .= "(function(){\n";
        $inline_script .= "  var gcmMapping = " . wp_json_encode($gcm_mapping) . ";\n";
        $inline_script .= "  function updateGCMConsent(preferences){\n";
        $inline_script .= "    if(typeof gtag !== 'function'){console.warn('[CookieNod] gtag not available');return;}\n";
        $inline_script .= "    var consentUpdate = {};\n";
        $inline_script .= "    var hasChanges = false;\n";
        $inline_script .= "    for(var category in preferences){\n";
        $inline_script .= "      if(preferences[category]===true && gcmMapping[category]){\n";
        $inline_script .= "        gcmMapping[category].forEach(function(consentType){\n";
        $inline_script .= "          if(consentUpdate[consentType]!=='granted'){consentUpdate[consentType]='granted';hasChanges=true;}\n";
        $inline_script .= "        });\n";
        $inline_script .= "      }\n";
        $inline_script .= "    }\n";
        $inline_script .= "    for(var type in gcmMapping){\n";
        $inline_script .= "      gcmMapping[type].forEach(function(consentType){\n";
        $inline_script .= "        if(!consentUpdate[consentType]){consentUpdate[consentType]='denied';hasChanges=true;}\n";
        $inline_script .= "      });\n";
        $inline_script .= "    }\n";
        $inline_script .= "    if(hasChanges){\n";
        $inline_script .= "      gtag('consent','update',consentUpdate);\n";
        $inline_script .= "      if(window.dataLayer){window.dataLayer.push({'event':'consent_update','consent':consentUpdate,'consent_source':'cookienod_wp'});}\n";
        $inline_script .= "      console.log('[CookieNod] GCM consent updated:',consentUpdate);\n";
        $inline_script .= "    }\n";
        $inline_script .= "  }\n";
        $inline_script .= "  window.addEventListener('cookiePreferencesChanged',function(e){\n";
        $inline_script .= "    if(e.detail){updateGCMConsent(e.detail);try{document.cookie='cookienod_consent='+JSON.stringify(e.detail)+';path=/;SameSite=Strict';}catch(err){}}\n";
        $inline_script .= "  });\n";
        $inline_script .= "  if(window.CookieManager&&window.CookieManager.preferences){updateGCMConsent(window.CookieManager.preferences);}\n";
        $inline_script .= "  window.addEventListener('cookienodConsentAccepted',function(e){if(window.dataLayer){window.dataLayer.push({'event':'cookienod_consent_accepted','consent_status':'accepted_all'});}});\n";
        $inline_script .= "  window.addEventListener('cookienodConsentRejected',function(e){if(window.dataLayer){window.dataLayer.push({'event':'cookienod_consent_rejected','consent_status':'rejected_all'});}});\n";
        $inline_script .= "  window.addEventListener('cookienodConsentCustomized',function(e){if(window.dataLayer&&e.detail){window.dataLayer.push({'event':'cookienod_consent_customized','consent_preferences':e.detail});}});\n";
        $inline_script .= "})();\n";

        wp_add_inline_script('cookienod-gcm-update', $inline_script);
    }

    /**
     * Filter GTM container output
     */
    public function filter_gtm_container($code, $container_id) {
        // Check if user has consented to analytics/marketing
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Cookie value will be validated by json_decode
        $saved_consent = isset($_COOKIE['cookienod_consent']) ? json_decode(sanitize_text_field(wp_unslash($_COOKIE['cookienod_consent'])), true) : array();

        if (empty($saved_consent['marketing']) && empty($saved_consent['analytics'])) {
            // User hasn't consented - only load GTM with limited consent
            // GCM will handle what fires
            return $code;
        }

        return $code;
    }

    /**
     * Dequeue analytics scripts if not consented
     */
    public function dequeue_analytics_scripts() {
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Cookie value will be validated by json_decode
        $saved_consent = isset($_COOKIE['cookienod_consent']) ? json_decode(sanitize_text_field(wp_unslash($_COOKIE['cookienod_consent'])), true) : array();

        // List of known analytics script handles to block
        $analytics_scripts = apply_filters('cookienod_analytics_scripts', array(
            'google-analytics',
            'ga-*in',
            'gtm-*in',
            'gtag',
            'google-tag-manager',
            'monsterinsights',
            'exactmetrics',
            'matomo',
            'piwik',
            'analytics',
        ));

        if (empty($saved_consent['analytics'])) {
            foreach ($analytics_scripts as $handle) {
                wp_dequeue_script($handle);
            }
        }
    }

    /**
     * Add GCM attributes to cookienod script
     */
    public function add_gcm_attributes($attributes) {
        if ($this->is_enabled()) {
            $attributes .= ' data-google-consent-mode="true"';
        }
        return $attributes;
    }

    /**
     * Check if GCM is enabled
     */
    public function is_enabled() {
        return !empty($this->options['enable_google_consent_mode']);
    }

    /**
     * Get GCM settings fields
     */
    public function get_settings_fields() {
        return array(
            'enable_google_consent_mode' => array(
                'type' => 'checkbox',
                'label' => __('Enable Google Consent Mode v2', 'cookienod'),
                'description' => __('Integrates with Google Tag Manager and Google Analytics for consent-based data collection', 'cookienod'),
            ),
            'gcm_enable_gtm' => array(
                'type' => 'checkbox',
                'label' => __('Enable Google Tag Manager Integration', 'cookienod'),
                'description' => __('Pass consent state to GTM dataLayer', 'cookienod'),
            ),
            'gcm_redact_ads_data' => array(
                'type' => 'checkbox',
                'label' => __('Redact Ads Data When Denied', 'cookienod'),
                'description' => __('Automatically redact advertising data when marketing consent is denied', 'cookienod'),
            ),
        );
    }

    /**
     * Get consent types for documentation
     */
    public function get_consent_types_documentation() {
        $docs = array();

        foreach ($this->consent_types as $type => $config) {
            $docs[] = array(
                'consent_type' => $type,
                'label' => $config['label'],
                'category' => $config['category'],
                'description' => $config['description'],
                'v2_only' => !empty($config['v2_only']),
                'always_granted' => !empty($config['always_granted']),
            );
        }

        return $docs;
    }
}