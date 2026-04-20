<?php
/**
 * WooCommerce Integration
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_WooCommerce
 * Handles WooCommerce-specific cookie consent features
 */
class CookieNod_WooCommerce {

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
        // Only proceed if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Add consent checkbox to checkout
        add_action('woocommerce_checkout_billing', array($this, 'add_marketing_consent_checkbox'));
        add_action('woocommerce_checkout_order_processed', array($this, 'save_marketing_consent'), 10, 3);
        add_action('woocommerce_created_customer', array($this, 'save_registration_consent'), 10, 3);

        // Block WooCommerce tracking scripts
        add_action('wp_enqueue_scripts', array($this, 'block_woocommerce_scripts'), 100);

        // Add consent to order meta
        add_action('woocommerce_checkout_update_order_meta', array($this, 'add_consent_to_order_meta'));
        add_action('woocommerce_admin_order_data_after_billing_address', array($this, 'display_consent_in_admin'), 10, 1);

        // Add to privacy policy
        add_action('admin_init', array($this, 'add_privacy_policy_content'));

        // Filter cart fragments (requires functional cookies)
        add_filter('woocommerce_add_to_cart_fragments', array($this, 'filter_cart_fragments'));

        // Block Google Analytics eCommerce tracking
        add_filter('woocommerce_ga_snippet_output', array($this, 'filter_ga_tracking'));
        add_filter('woocommerce_gtag_snippet', array($this, 'filter_gtag_tracking'));

        // Cart/Checkout cookie consent notices
        add_action('woocommerce_before_cart', array($this, 'maybe_show_cart_cookie_notice'));
        add_action('woocommerce_before_checkout_form', array($this, 'maybe_show_cart_cookie_notice'));

        // Product view tracking (requires analytics consent)
        add_action('woocommerce_before_single_product', array($this, 'maybe_block_product_tracking'));

        // My account - privacy settings
        add_action('woocommerce_edit_account_form', array($this, 'add_privacy_preferences_to_account'));
        add_action('woocommerce_save_account_details', array($this, 'save_account_privacy_preferences'));
    }

    /**
     * Add marketing consent checkbox to checkout
     */
    public function add_marketing_consent_checkbox($checkout) {
        if (!$this->is_marketing_consent_enabled()) {
            return;
        }

        $label = $this->options['wc_marketing_consent_label'] ?? __('I want to receive marketing emails and offers', 'cookienod');
        $checked = $this->options['wc_marketing_consent_default'] ?? 'no';

        woocommerce_form_field('cookienod_marketing_consent', array(
            'type'      => 'checkbox',
            'class'     => array('form-row-wide'),
            'label'     => esc_html($label),
            'default'   => $checked === 'yes',
            'required'  => false,
        ), $checkout->get_value('cookienod_marketing_consent'));

        // Add analytics consent checkbox if enabled
        if ($this->options['wc_analytics_consent'] ?? false) {
            $analytics_label = $this->options['wc_analytics_consent_label'] ??
                __('I consent to analytics tracking for order improvement', 'cookienod');

            woocommerce_form_field('cookienod_analytics_consent', array(
                'type'      => 'checkbox',
                'class'     => array('form-row-wide'),
                'label'     => esc_html($analytics_label),
                'default'   => true,
                'required'  => false,
            ), $checkout->get_value('cookienod_analytics_consent'));
        }

        // Add privacy notice
        $privacy_text = $this->options['wc_privacy_notice'] ?? sprintf(
            __('Your personal data will be used to process your order. See our %s for details.', 'cookienod'),
            '<a href="' . esc_url(get_privacy_policy_url()) . '">' . __('Privacy Policy', 'cookienod') . '</a>'
        );

        echo '<div class="woocommerce-privacy-policy-text">' . wp_kses_post($privacy_text) . '</div>';
    }

    /**
     * Save marketing consent with order
     */
    public function save_marketing_consent($order_id, $posted_data, $order) {
        if (isset($_POST['cookienod_marketing_consent'])) {
            update_post_meta($order_id, '_cookienod_marketing_consent', 'yes');

            // Also update user meta if user is logged in
            $user_id = $order->get_user_id();
            if ($user_id) {
                update_user_meta($user_id, 'cookienod_marketing_consent', 'yes');
            }
        } else {
            update_post_meta($order_id, '_cookienod_marketing_consent', 'no');
        }

        // Save analytics consent
        if (isset($_POST['cookienod_analytics_consent'])) {
            update_post_meta($order_id, '_cookienod_analytics_consent', 'yes');
        } else {
            update_post_meta($order_id, '_cookienod_analytics_consent', 'no');
        }
    }

    /**
     * Save consent during registration
     */
    public function save_registration_consent($customer_id, $new_customer_data, $password_generated) {
        if (isset($_POST['cookienod_marketing_consent'])) {
            update_user_meta($customer_id, 'cookienod_marketing_consent', 'yes');
        } else {
            update_user_meta($customer_id, 'cookienod_marketing_consent', 'no');
        }
    }

    /**
     * Add consent to order meta
     */
    public function add_consent_to_order_meta($order_id) {
        $consent_data = array(
            'marketing' => get_post_meta($order_id, '_cookienod_marketing_consent', true),
            'analytics' => get_post_meta($order_id, '_cookienod_analytics_consent', true),
            'timestamp' => current_time('mysql'),
            'ip_address' => $this->get_client_ip(),
        );

        update_post_meta($order_id, '_cookienod_consent_data', $consent_data);
    }

    /**
     * Display consent in admin order view
     */
    public function display_consent_in_admin($order) {
        $marketing = get_post_meta($order->get_id(), '_cookienod_marketing_consent', true);
        $analytics = get_post_meta($order->get_id(), '_cookienod_analytics_consent', true);

        echo '<div class="cookienod-consent-info" style="margin-top: 10px;">';
        echo '<h4>' . esc_html__('Cookie Consent', 'cookienod') . '</h4>';
        echo '<p><strong>' . esc_html__('Marketing:', 'cookienod') . '</strong> ' .
             ($marketing === 'yes' ? '✓ ' . __('Consented', 'cookienod') : '✗ ' . __('Not consented', 'cookienod')) .
             '</p>';
        if ($analytics) {
            echo '<p><strong>' . esc_html__('Analytics:', 'cookienod') . '</strong> ' .
                 ($analytics === 'yes' ? '✓ ' . __('Consented', 'cookienod') : '✗ ' . __('Not consented', 'cookienod')) .
                 '</p>';
        }
        echo '</div>';
    }

    /**
     * Block WooCommerce scripts until consent
     */
    public function block_woocommerce_scripts() {
        // Check if user has given functional consent
        $has_functional_consent = isset($_COOKIE['cookienod_consent']) &&
            $this->check_consent_category('functional');

        if (!$has_functional_consent) {
            // Dequeue WooCommerce cart fragments if no functional consent
            wp_dequeue_script('wc-cart-fragments');
        }

        // Check analytics consent for eCommerce tracking
        $has_analytics_consent = isset($_COOKIE['cookienod_consent']) &&
            $this->check_consent_category('analytics');

        if (!$has_analytics_consent) {
            // Disable Facebook Pixel for WooCommerce
            add_filter('facebook_for_woocommerce_integration_pixel_enabled', '__return_false');

            // Disable Pinterest tracking
            add_filter('woocommerce_pinterest_tracking_enabled', '__return_false');

            // Disable TikTok pixel
            add_filter('woocommerce_tiktok_pixel_enabled', '__return_false');
        }
    }

    /**
     * Filter cart fragments - may need consent
     */
    public function filter_cart_fragments($fragments) {
        // Cart fragments require functional cookies
        $has_functional_consent = isset($_COOKIE['cookienod_consent']) &&
            $this->check_consent_category('functional');

        if (!$has_functional_consent) {
            // Return empty fragments or disable cart count updates
            return array();
        }

        return $fragments;
    }

    /**
     * Filter Google Analytics eCommerce tracking
     */
    public function filter_ga_tracking($snippet) {
        $has_analytics_consent = isset($_COOKIE['cookienod_consent']) &&
            $this->check_consent_category('analytics');

        if (!$has_analytics_consent) {
            return '';
        }

        return $snippet;
    }

    /**
     * Filter Google Tag tracking
     */
    public function filter_gtag_tracking($snippet) {
        $has_analytics_consent = isset($_COOKIE['cookienod_consent']) &&
            $this->check_consent_category('analytics');

        if (!$has_analytics_consent) {
            return '';
        }

        return $snippet;
    }

    /**
     * Show cookie notice for cart/checkout
     */
    public function maybe_show_cart_cookie_notice() {
        $has_functional_consent = isset($_COOKIE['cookienod_consent']) &&
            $this->check_consent_category('functional');

        if (!$has_functional_consent && $this->options['wc_show_cookie_notice'] ?? true) {
            wc_print_notice(
                __('Some cart features require functional cookies. Please accept cookies in the banner above for the best shopping experience.', 'cookienod'),
                'notice'
            );
        }
    }

    /**
     * Maybe block product view tracking
     */
    public function maybe_block_product_tracking() {
        $has_analytics_consent = isset($_COOKIE['cookienod_consent']) &&
            $this->check_consent_category('analytics');

        if (!$has_analytics_consent) {
            // Remove product view tracking
            remove_action('woocommerce_before_single_product', 'wc_google_analytics_pro_track_product_view');
        }
    }

    /**
     * Add privacy preferences to my account
     */
    public function add_privacy_preferences_to_account() {
        $user_id = get_current_user_id();
        $marketing = get_user_meta($user_id, 'cookienod_marketing_consent', true);
        $saved_preferences = get_user_meta($user_id, 'cookienod_preferences', true);

        wp_nonce_field('cookienod_account_preferences', 'cookienod_account_preferences_nonce');

        ?>
        <h3><?php esc_html_e('Privacy Preferences', 'cookienod'); ?></h3>
        <p class="woocommerce-form-row woocommerce-form-row--wide form-row form-row-wide">
            <label for="cookienod_marketing_consent_account">
                <input type="checkbox" name="cookienod_marketing_consent_account" id="cookienod_marketing_consent_account"
                       value="yes" <?php checked($marketing, 'yes'); ?> />
                <?php esc_html_e('I want to receive marketing emails and offers', 'cookienod'); ?>
            </label>
        </p>

        <p>
            <a href="#" onclick="if(window.CookieScanner) { window.CookieScanner.showConsentBanner(); } return false;" class="button">
                <?php esc_html_e('Manage Cookie Preferences', 'cookienod'); ?>
            </a>
        </p>
        <?php
    }

    /**
     * Save account privacy preferences
     */
    public function save_account_privacy_preferences($user_id) {
        if (!isset($_POST['cookienod_account_preferences_nonce']) ||
            !wp_verify_nonce($_POST['cookienod_account_preferences_nonce'], 'cookienod_account_preferences')) {
            return;
        }

        $marketing = isset($_POST['cookienod_marketing_consent_account']) ? 'yes' : 'no';
        update_user_meta($user_id, 'cookienod_marketing_consent', $marketing);

        // Log the consent change
        do_action('cookienod_consent_changed', $user_id, array('marketing' => $marketing === 'yes'));
    }

    /**
     * Add privacy policy content
     */
    public function add_privacy_policy_content() {
        if (!function_exists('wp_add_privacy_policy_content')) {
            return;
        }

        $content = sprintf(
            __('This website uses cookies to process your orders and improve your shopping experience.
            Necessary cookies are required for the cart and checkout to function.
            Functional cookies enable features like saved addresses.
            Analytics cookies help us improve our store.
            Marketing cookies are used to show you relevant offers.
            See our %s for more information.', 'cookienod'),
            '<a href="' . esc_url(get_privacy_policy_url()) . '">' . __('Privacy Policy', 'cookienod') . '</a>'
        );

        wp_add_privacy_policy_content(
            __('CookieNod WooCommerce', 'cookienod'),
            wp_kses_post(wpautop($content))
        );
    }

    /**
     * Check if marketing consent is enabled
     */
    private function is_marketing_consent_enabled() {
        return $this->options['wc_enable_marketing_consent'] ?? true;
    }

    /**
     * Check consent category from cookie
     */
    private function check_consent_category($category) {
        if (!isset($_COOKIE['cookienod_consent'])) {
            return false;
        }

        $consent = json_decode(sanitize_text_field($_COOKIE['cookienod_consent']), true);
        return isset($consent[$category]) && $consent[$category] === true;
    }

    /**
     * Get client IP
     */
    private function get_client_ip() {
        $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
        foreach ($ip_keys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', sanitize_text_field($_SERVER[$key]));
                return trim($ips[0]);
            }
        }
        return '';
    }

    /**
     * Get WooCommerce settings fields
     */
    public static function get_settings_fields() {
        return array(
            'wc_enable_marketing_consent' => array(
                'type' => 'checkbox',
                'label' => __('Enable Marketing Consent Checkbox', 'cookienod'),
                'description' => __('Add a marketing consent checkbox at checkout', 'cookienod'),
                'default' => true,
            ),
            'wc_marketing_consent_label' => array(
                'type' => 'text',
                'label' => __('Marketing Consent Label', 'cookienod'),
                'description' => __('Text shown next to the marketing consent checkbox', 'cookienod'),
                'default' => __('I want to receive marketing emails and offers', 'cookienod'),
            ),
            'wc_marketing_consent_default' => array(
                'type' => 'select',
                'label' => __('Default State', 'cookienod'),
                'options' => array(
                    'no' => __('Unchecked', 'cookienod'),
                    'yes' => __('Checked', 'cookienod'),
                ),
                'default' => 'no',
            ),
            'wc_analytics_consent' => array(
                'type' => 'checkbox',
                'label' => __('Enable Analytics Consent Checkbox', 'cookienod'),
                'description' => __('Add an analytics consent checkbox at checkout', 'cookienod'),
                'default' => false,
            ),
            'wc_show_cookie_notice' => array(
                'type' => 'checkbox',
                'label' => __('Show Cookie Notice on Cart/Checkout', 'cookienod'),
                'description' => __('Display a notice if functional cookies are not accepted', 'cookienod'),
                'default' => true,
            ),
        );
    }
}
