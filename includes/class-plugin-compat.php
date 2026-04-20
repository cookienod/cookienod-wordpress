<?php
/**
 * Plugin Compatibility - Elementor, Gravity Forms, Contact Form 7
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_Plugin_Compat
 * Handles compatibility with popular WordPress plugins
 */
class CookieNod_Plugin_Compat {

    /**
     * Plugin options
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
        // Elementor compatibility
        add_action('elementor/init', array($this, 'setup_elementor_compat'));
        add_action('elementor/editor/before_enqueue_scripts', array($this, 'elementor_editor_compat'));

        // Elementor Pro compatibility
        add_action('elementor_pro/init', array($this, 'setup_elementor_pro_compat'));

        // Gravity Forms compatibility
        add_filter('gform_form_tag', array($this, 'add_gform_consent_field'), 10, 2);
        add_filter('gform_entry_created', array($this, 'log_gform_consent'), 10, 2);
        add_filter('gform_pre_render', array($this, 'check_gform_consent'));

        // Contact Form 7 compatibility
        add_action('wpcf7_init', array($this, 'add_cf7_consent_field'));
        add_filter('wpcf7_posted_data', array($this, 'process_cf7_consent'));
        add_action('wpcf7_before_send_mail', array($this, 'log_cf7_consent'));

        // General form integrations
        add_action('wp_footer', array($this, 'inject_form_scripts'), 100);

        // AJAX consent check
        add_action('wp_ajax_cookienod_check_consent', array($this, 'ajax_check_consent'));
        add_action('wp_ajax_nopriv_cookienod_check_consent', array($this, 'ajax_check_consent'));
    }

    /**
     * Setup Elementor compatibility
     */
    public function setup_elementor_compat() {
        // Add consent-aware widget controls
        add_action('elementor/element/before_section_end', array($this, 'add_elementor_consent_controls'), 10, 3);

        // Filter widgets based on consent
        add_filter('elementor/widget/render_content', array($this, 'filter_elementor_widget'), 10, 2);

        // Add consent attributes to Elementor scripts
        add_filter('elementor/frontend/script_config', array($this, 'add_consent_to_elementor_scripts'));

        // Handle Elementor popups
        add_action('elementor/frontend/popup/before_render', array($this, 'check_popup_consent'));

        // Integrate with Elementor forms
        add_action('elementor_pro/forms/form_submitted', array($this, 'log_elementor_form_consent'), 10, 2);
    }

    /**
     * Elementor editor compatibility
     */
    public function elementor_editor_compat() {
        // Ensure banner doesn't show in Elementor editor
        if (isset($_GET['elementor-preview'])) {
            remove_action('wp_footer', array(Cookienod::get_instance(), 'render_consent_banner'), 1);
        }
    }

    /**
     * Setup Elementor Pro compatibility
     */
    public function setup_elementor_pro_compat() {
        // Handle Elementor Pro forms
        add_action('elementor_pro/forms/process', array($this, 'process_elementor_pro_form'), 10, 2);

        // Handle dynamic content based on consent
        add_filter('elementor_pro/dynamic_tags/tag_data', array($this, 'filter_dynamic_content'));

        // Marketing widgets consent check
        add_filter('elementor_pro/marketing_widgets/available', array($this, 'filter_marketing_widgets'));
    }

    /**
     * Add Elementor widget consent controls
     */
    public function add_elementor_consent_controls($element, $section_id, $args) {
        if ($section_id !== 'section_advanced') {
            return;
        }

        $element->add_control(
            'cookienod_require_consent',
            array(
                'label' => __('Require Consent', 'cookienod'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'description' => __('Hide this widget until user gives consent', 'cookienod'),
                'return_value' => 'yes',
                'default' => '',
                'prefix_class' => 'cookienod-require-consent-',
            )
        );

        $element->add_control(
            'cookienod_consent_category',
            array(
                'label' => __('Consent Category', 'cookienod'),
                'type' => \Elementor\Controls_Manager::SELECT,
                'options' => array(
                    'marketing' => __('Marketing', 'cookienod'),
                    'analytics' => __('Analytics', 'cookienod'),
                    'functional' => __('Functional', 'cookienod'),
                ),
                'default' => 'marketing',
                'condition' => array(
                    'cookienod_require_consent' => 'yes',
                ),
            )
        );
    }

    /**
     * Filter Elementor widget content based on consent
     */
    public function filter_elementor_widget($content, $widget) {
        $settings = $widget->get_settings();

        if (!empty($settings['cookienod_require_consent'])) {
            $category = $settings['cookienod_consent_category'] ?? 'marketing';

            if (!$this->has_consent($category)) {
                return $this->get_consent_placeholder($category);
            }
        }

        return $content;
    }

    /**
     * Add consent attributes to Elementor scripts
     */
    public function add_consent_to_elementor_scripts($config) {
        $config['cookienod'] = array(
            'has_consent' => $this->has_consent('marketing'),
        );
        return $config;
    }

    /**
     * Check popup consent before rendering
     */
    public function check_popup_consent($popup) {
        $settings = $popup->get_settings();

        if (!empty($settings['cookienod_require_consent'])) {
            $category = $settings['cookienod_consent_category'] ?? 'marketing';

            if (!$this->has_consent($category)) {
                // Prevent popup from showing
                $popup->set_id(null);
            }
        }
    }

    /**
     * Log Elementor Pro form submission with consent
     */
    public function log_elementor_form_consent($form, $record) {
        $consent_data = array(
            'marketing' => $this->has_consent('marketing'),
            'analytics' => $this->has_consent('analytics'),
            'functional' => $this->has_consent('functional'),
        );

        // Store consent data with form submission
        update_post_meta($record->get_form_settings('id'), '_cookienod_form_consent', $consent_data);
    }

    /**
     * Add Gravity Forms consent field
     */
    public function add_gform_consent_field($form_tag, $form) {
        $consent_html = '<input type="hidden" name="cookienod_consent_data" value="' .
                        esc_attr(json_encode($this->get_consent_data())) . '" />';

        // Add nonce for security
        $consent_html .= wp_nonce_field('cookienod_gform_' . $form['id'], 'cookienod_gform_nonce', true, false);

        return $form_tag . $consent_html;
    }

    /**
     * Log Gravity Forms consent on submission
     */
    public function log_gform_consent($entry, $form) {
        // Verify nonce
        if (!isset($_POST['cookienod_gform_nonce']) ||
            !wp_verify_nonce($_POST['cookienod_gform_nonce'], 'cookienod_gform_' . $form['id'])) {
            return;
        }

        // Store consent data
        gform_update_meta($entry['id'], 'cookienod_consent', $this->get_consent_data());

        // Log if marketing consent is given
        if ($this->has_consent('marketing')) {
            do_action('cookienod_gform_marketing_consent', $entry, $form);
        }
    }

    /**
     * Check Gravity Forms consent before rendering
     */
    public function check_gform_consent($form) {
        // Check if form requires specific consent
        $required_consent = rgar($form, 'cookienodRequiredConsent');

        if ($required_consent && !$this->has_consent($required_consent)) {
            // Add notice about required consent
            add_filter('gform_validation_message', array($this, 'gform_consent_notice'), 10, 2);
        }

        return $form;
    }

    /**
     * Gravity Forms consent notice
     */
    public function gform_consent_notice($message, $form) {
        return $message . '<div class="gform_consent_notice">' .
               esc_html__('Please accept cookies to submit this form.', 'cookienod') .
               '</div>';
    }

    /**
     * Add Contact Form 7 consent field
     */
    public function add_cf7_consent_field() {
        wpcf7_add_form_tag('cookienod_consent', array($this, 'render_cf7_consent_field'));
    }

    /**
     * Render CF7 consent field
     */
    public function render_cf7_consent_field($tag) {
        $consent_data = $this->get_consent_data();

        $html = '<input type="hidden" name="cookienod_consent_data" value="' .
                esc_attr(json_encode($consent_data)) . '" />';

        $html .= '<input type="hidden" name="cookienod_consent_nonce" value="' .
                wp_create_nonce('cookienod_cf7_consent') . '" />';

        // Add consent summary for display
        if (!empty($tag->values) && in_array('show_summary', $tag->values)) {
            $html .= '<div class="wpcf7-consent-summary">';
            $html .= '<p><strong>' . esc_html__('Your Privacy Settings:', 'cookienod') . '</strong></p>';
            $html .= '<ul>';
            foreach ($consent_data as $cat => $value) {
                $html .= '<li>' . esc_html(ucfirst($cat)) . ': ' .
                         ($value ? '✓' : '✗') . '</li>';
            }
            $html .= '</ul>';
            $html .= '</div>';
        }

        return $html;
    }

    /**
     * Process CF7 consent data
     */
    public function process_cf7_consent($posted_data) {
        // Verify nonce
        if (isset($posted_data['cookienod_consent_nonce'])) {
            if (!wp_verify_nonce($posted_data['cookienod_consent_nonce'], 'cookienod_cf7_consent')) {
                // Invalid nonce, remove consent data
                unset($posted_data['cookienod_consent_data']);
            }
        }

        return $posted_data;
    }

    /**
     * Log CF7 submission with consent
     */
    public function log_cf7_consent($contact_form) {
        $submission = WPCF7_Submission::get_instance();

        if ($submission) {
            $data = $submission->get_posted_data();

            if (isset($data['cookienod_consent_data'])) {
                $consent = json_decode(sanitize_text_field($data['cookienod_consent_data']), true);

                // Store with submission meta
                do_action('cookienod_cf7_submission', $contact_form, $consent);
            }
        }
    }

    /**
     * Process Elementor Pro form
     */
    public function process_elementor_pro_form($record, $ajax_handler) {
        $raw_fields = $record->get('fields');

        // Add consent data to submission
        $ajax_handler->add_response_data('cookienod_consent', $this->get_consent_data());
    }

    /**
     * Filter dynamic content based on consent
     */
    public function filter_dynamic_content($tag_data) {
        if (isset($tag_data['settings']['cookienod_require_consent'])) {
            $category = $tag_data['settings']['cookienod_consent_category'] ?? 'marketing';

            if (!$this->has_consent($category)) {
                return array(); // Hide dynamic content
            }
        }

        return $tag_data;
    }

    /**
     * Filter marketing widgets
     */
    public function filter_marketing_widgets($widgets) {
        if (!$this->has_consent('marketing')) {
            // Remove marketing widgets
            unset($widgets['facebook-button']);
            unset($widgets['facebook-comments']);
            unset($widgets['facebook-embed']);
            unset($widgets['twitter']);
        }

        return $widgets;
    }

    /**
     * Inject form integration scripts
     */
    public function inject_form_scripts() {
        // Only add if we have consent data
        if (!isset($_COOKIE['cookienod_consent'])) {
            return;
        }

        ?>
        <script type="text/javascript">
        (function() {
            // Update all form consent fields dynamically
            function updateFormConsentFields() {
                var consentData = {
                    marketing: window.cookienod?.consent?.marketing || false,
                    analytics: window.cookienod?.consent?.analytics || false,
                    functional: window.cookienod?.consent?.functional || false
                };

                // Update Gravity Forms
                document.querySelectorAll('input[name="cookienod_consent_data"]').forEach(function(field) {
                    field.value = JSON.stringify(consentData);
                });

                // Update Contact Form 7
                document.querySelectorAll('input[name="cookienod_consent_data"]').forEach(function(field) {
                    field.value = JSON.stringify(consentData);
                });
            }

            // Listen for consent changes
            window.addEventListener('cookienod:consentChanged', updateFormConsentFields);

            // Initial update
            updateFormConsentFields();
        })();
        </script>
        <?php
    }

    /**
     * AJAX check consent
     */
    public function ajax_check_consent() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        $category = sanitize_text_field($_POST['category'] ?? '');

        wp_send_json_success(array(
            'has_consent' => $this->has_consent($category),
            'consent_data' => $this->get_consent_data(),
        ));
    }

    /**
     * Check if user has given consent for a category
     */
    private function has_consent($category) {
        if (isset($_COOKIE['cookienod_consent'])) {
            $consent = json_decode(sanitize_text_field($_COOKIE['cookienod_consent']), true);
            return !empty($consent[$category]);
        }

        // If no consent cookie, check if blocking is enabled
        $block_mode = $this->options['block_mode'] ?? 'auto';
        return $block_mode !== 'auto';
    }

    /**
     * Get consent data
     */
    private function get_consent_data() {
        $consent = array();

        if (isset($_COOKIE['cookienod_consent'])) {
            $consent = json_decode(sanitize_text_field($_COOKIE['cookienod_consent']), true);
        }

        return array_merge(array(
            'necessary' => true,
            'functional' => false,
            'analytics' => false,
            'marketing' => false,
        ), $consent ?: array());
    }

    /**
     * Get consent placeholder HTML
     */
    private function get_consent_placeholder($category) {
        $category_label = $this->get_category_label($category);

        return sprintf(
            '<div class="cookienod-widget-placeholder" data-category="%s">' .
            '<p>%s %s %s</p>' .
            '<button class="cookienod-give-consent" data-category="%s">%s</button>' .
            '</div>',
            esc_attr($category),
            esc_html__('This content requires', 'cookienod'),
            esc_html($category_label),
            esc_html__('consent to display.', 'cookienod'),
            esc_attr($category),
            esc_html(sprintf(__('Allow %s', 'cookienod'), $category_label))
        );
    }

    /**
     * Get category label
     */
    private function get_category_label($category) {
        $labels = array(
            'necessary' => __('Necessary', 'cookienod'),
            'functional' => __('Functional', 'cookienod'),
            'analytics' => __('Analytics', 'cookienod'),
            'marketing' => __('Marketing', 'cookienod'),
        );
        return isset($labels[$category]) ? $labels[$category] : $category;
    }

    /**
     * Check if plugin is active
     */
    public static function is_plugin_active($plugin) {
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        return is_plugin_active($plugin);
    }

    /**
     * Check if Elementor is active
     */
    public static function is_elementor_active() {
        return self::is_plugin_active('elementor/elementor.php');
    }

    /**
     * Check if Elementor Pro is active
     */
    public static function is_elementor_pro_active() {
        return self::is_plugin_active('elementor-pro/elementor-pro.php');
    }

    /**
     * Check if Gravity Forms is active
     */
    public static function is_gravity_forms_active() {
        return self::is_plugin_active('gravityforms/gravityforms.php');
    }

    /**
     * Check if Contact Form 7 is active
     */
    public static function is_cf7_active() {
        return self::is_plugin_active('contact-form-7/wp-contact-form-7.php');
    }
}
