<?php
/**
 * Cookie Policy Page Generator
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_Policy_Generator
 * Handles automatic cookie policy generation
 */
class CookieNod_Policy_Generator {

    /**
     * Options
     */
    private $options;

    /**
     * Policy templates by region
     */
    private $templates = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_option('cookienod_wp_options', array());
        $this->init_templates();
        $this->init();
    }

    /**
     * Initialize hooks
     */
    private function init() {
        // Shortcode for cookie policy
        add_shortcode('cookienod_policy', array($this, 'render_policy_shortcode'));
        add_shortcode('cookienod_cookie_table', array($this, 'render_cookie_table_shortcode'));

        // Admin AJAX
        if (is_admin()) {
            add_action('wp_ajax_cookienod_generate_policy', array($this, 'ajax_generate_policy'));
            add_action('wp_ajax_cookienod_update_policy', array($this, 'ajax_update_policy'));
            add_action('wp_ajax_cookienod_create_policy_page', array($this, 'ajax_create_policy_page'));
        }

        // Auto-update policy on new cookie detection
        add_action('cookienod_new_cookies_detected', array($this, 'maybe_update_policy'), 10, 1);

        // Add meta box to policy pages
        add_action('add_meta_boxes', array($this, 'add_policy_meta_box'));
        add_action('save_post', array($this, 'save_policy_meta_box'));
    }

    /**
     * Initialize policy templates
     */
    private function init_templates() {
        $this->templates = array(
            'gdpr' => array(
                'name' => __('GDPR Compliant', 'cookienod'),
                'sections' => array(
                    'introduction',
                    'what_are_cookies',
                    'how_we_use',
                    'cookie_types',
                    'third_party',
                    'data_retention',
                    'your_rights',
                    'contact',
                ),
            ),
            'ccpa' => array(
                'name' => __('CCPA Compliant', 'cookienod'),
                'sections' => array(
                    'introduction',
                    'what_are_cookies',
                    'information_we_collect',
                    'sale_of_personal_info',
                    'your_rights_ca',
                    'do_not_sell',
                    'contact',
                ),
            ),
            'combined' => array(
                'name' => __('Combined (GDPR + CCPA)', 'cookienod'),
                'sections' => array(
                    'introduction',
                    'what_are_cookies',
                    'how_we_use',
                    'cookie_types',
                    'third_party',
                    'sale_of_personal_info',
                    'your_rights',
                    'contact',
                ),
            ),
            'simple' => array(
                'name' => __('Simple/General', 'cookienod'),
                'sections' => array(
                    'introduction',
                    'what_are_cookies',
                    'cookie_types',
                    'managing_cookies',
                    'contact',
                ),
            ),
        );
    }

    /**
     * Render policy shortcode
     */
    public function render_policy_shortcode($atts) {
        $atts = shortcode_atts(array(
            'template' => 'combined',
            'show_table' => 'yes',
        ), $atts, 'cookienod_policy');

        $template = $atts['template'];
        $show_table = $atts['show_table'] === 'yes';

        if (!isset($this->templates[$template])) {
            $template = 'combined';
        }

        // Get detected cookies
        $admin = new CookieNod_Admin();
        $cookies = $admin->get_detected_cookies();
        $categorized = $admin->categorize_cookies($cookies);

        // Generate policy content
        $content = $this->generate_policy_content($template, $categorized, $show_table);

        return '<div class="cookienod-policy">' . $content . '</div>';
    }

    /**
     * Render cookie table shortcode
     */
    public function render_cookie_table_shortcode($atts) {
        $atts = shortcode_atts(array(
            'category' => 'all',
        ), $atts, 'cookienod_cookie_table');

        $admin = new CookieNod_Admin();
        $cookies = $admin->get_detected_cookies();
        $categorized = $admin->categorize_cookies($cookies);

        if ($atts['category'] !== 'all' && isset($categorized[$atts['category']])) {
            $cookies = array($atts['category'] => $categorized[$atts['category']]);
        } else {
            $cookies = $categorized;
        }

        return $this->generate_cookie_table($cookies);
    }

    /**
     * Generate policy content
     */
    private function generate_policy_content($template, $categorized_cookies, $show_table = true) {
        $sections = $this->templates[$template]['sections'];
        $content = '';

        foreach ($sections as $section) {
            $content .= $this->render_section($section, $categorized_cookies, $show_table);
        }

        return $content;
    }

    /**
     * Render individual section
     */
    private function render_section($section, $cookies, $show_table) {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');

        switch ($section) {
            case 'introduction':
                return sprintf(
                    '<h2>%s</h2><p>%s</p>',
                    __('Introduction', 'cookienod'),
                    sprintf(
                        /* translators: 1: Site name, 2: Site URL */
                        __('This Cookie Policy explains how %1$s ("we", "us", or "our") uses cookies and similar technologies when you visit our website at %2$s. This policy is designed to help you understand what cookies are, how we use them, and the choices you have regarding their use.', 'cookienod'),
                        esc_html($site_name),
                        esc_url($site_url)
                    )
                );

            case 'what_are_cookies':
                return sprintf(
                    '<h2>%s</h2><p>%s</p>',
                    __('What Are Cookies', 'cookienod'),
                    __('Cookies are small text files that are stored on your computer or mobile device when you visit a website. They are widely used to make websites work more efficiently and provide information to the website owners. Cookies can be "persistent" or "session" cookies.', 'cookienod')
                );

            case 'how_we_use':
                return sprintf(
                    '<h2>%s</h2><p>%s</p><ul>%s</ul>',
                    __('How We Use Cookies', 'cookienod'),
                    __('We use cookies for various purposes, including:', 'cookienod'),
                    sprintf(
                        '<li>%s</li><li>%s</li><li>%s</li><li>%s</li>',
                        __('Enabling certain functions of the website', 'cookienod'),
                        __('Providing analytics about website usage', 'cookienod'),
                        __('Remembering your preferences and settings', 'cookienod'),
                        __('Delivering personalized content and advertisements', 'cookienod')
                    )
                );

            case 'cookie_types':
                $table = $show_table ? $this->generate_cookie_table($cookies) : '';
                return sprintf(
                    '<h2>%s</h2><p>%s</p>%s',
                    __('Types of Cookies We Use', 'cookienod'),
                    __('The cookies we use fall into the following categories:', 'cookienod'),
                    $table
                );

            case 'third_party':
                return sprintf(
                    '<h2>%s</h2><p>%s</p><p>%s</p>',
                    __('Third-Party Cookies', 'cookienod'),
                    __('In addition to our own cookies, we may also use various third-party cookies to report usage statistics of the website, deliver advertisements, and so on.', 'cookienod'),
                    __('These third parties may include analytics providers, advertising networks, and social media platforms. Please refer to their respective privacy policies for more information about their cookie practices.', 'cookienod')
                );

            case 'data_retention':
                return sprintf(
                    '<h2>%s</h2><p>%s</p>',
                    __('Data Retention', 'cookienod'),
                    __('The length of time a cookie stays on your device depends on its type. Session cookies are temporary and are deleted when you close your browser. Persistent cookies remain on your device until they expire or are deleted manually. The specific retention periods for each cookie are listed in the table above.', 'cookienod')
                );

            case 'your_rights':
                return sprintf(
                    '<h2>%s</h2><p>%s</p><ul>%s</ul>',
                    __('Your Rights', 'cookienod'),
                    __('You have the right to:', 'cookienod'),
                    sprintf(
                        '<li>%s</li><li>%s</li><li>%s</li><li>%s</li><li>%s</li>',
                        __('Access the personal data we hold about you', 'cookienod'),
                        __('Rectify inaccurate personal data', 'cookienod'),
                        __('Erase your personal data (right to be forgotten)', 'cookienod'),
                        __('Restrict processing of your personal data', 'cookienod'),
                        __('Object to processing of your personal data', 'cookienod')
                    )
                );

            case 'your_rights_ca':
                return sprintf(
                    '<h2>%s</h2><p>%s</p><ul>%s</ul>',
                    __('Your California Privacy Rights', 'cookienod'),
                    __('If you are a California resident, you have the following rights under the CCPA:', 'cookienod'),
                    sprintf(
                        '<li>%s</li><li>%s</li><li>%s</li><li>%s</li>',
                        __('Right to know what personal information is being collected', 'cookienod'),
                        __('Right to know whether personal information is sold or disclosed', 'cookienod'),
                        __('Right to opt-out of the sale of personal information', 'cookienod'),
                        __('Right to non-discrimination for exercising your rights', 'cookienod')
                    )
                );

            case 'information_we_collect':
                return sprintf(
                    '<h2>%s</h2><p>%s</p><ul>%s</ul>',
                    __('Information We Collect', 'cookienod'),
                    __('Through cookies and similar technologies, we may collect:', 'cookienod'),
                    sprintf(
                        '<li>%s</li><li>%s</li><li>%s</li><li>%s</li>',
                        __('Device and browser information', 'cookienod'),
                        __('IP address and approximate location', 'cookienod'),
                        __('Browsing behavior and interactions', 'cookienod'),
                        __('Referral source and pages visited', 'cookienod')
                    )
                );

            case 'sale_of_personal_info':
                return sprintf(
                    '<h2>%s</h2><p>%s</p>',
                    __('Sale of Personal Information', 'cookienod'),
                    __('We do not sell your personal information to third parties in the traditional sense. However, certain cookies and tracking technologies used by our advertising partners may be considered a "sale" of personal information under the CCPA. You can opt out of this by clicking "Do Not Sell My Personal Information" or by rejecting marketing cookies in our cookie banner.', 'cookienod')
                );

            case 'do_not_sell':
                return sprintf(
                    '<h2>%s</h2><p>%s</p><p><a href="#" class="cookienod-do-not-sell">%s</a></p>',
                    __('Do Not Sell My Personal Information', 'cookienod'),
                    __('Under the CCPA, you have the right to opt out of the sale of your personal information. Click the link below to opt out:', 'cookienod'),
                    __('Do Not Sell My Personal Information', 'cookienod')
                );

            case 'managing_cookies':
                return sprintf(
                    '<h2>%s</h2><p>%s</p><p>%s</p><ul>%s</ul>',
                    __('Managing Cookies', 'cookienod'),
                    __('You can manage your cookie preferences at any time by clicking the cookie settings button at the bottom of our website or by adjusting your browser settings.', 'cookienod'),
                    __('Most web browsers allow you to control cookies through their settings. Here are links to instructions for major browsers:', 'cookienod'),
                    sprintf(
                        '<li><a href="https://support.google.com/chrome/answer/95647" target="_blank">%s</a></li>' .
                        '<li><a href="https://support.mozilla.org/kb/cookies-information-websites-store-on-your-computer" target="_blank">%s</a></li>' .
                        '<li><a href="https://support.apple.com/guide/safari/manage-cookies-websites-sfri11471/" target="_blank">%s</a></li>' .
                        '<li><a href="https://support.microsoft.com/help/17442" target="_blank">%s</a></li>',
                        __('Google Chrome', 'cookienod'),
                        __('Mozilla Firefox', 'cookienod'),
                        __('Apple Safari', 'cookienod'),
                        __('Microsoft Edge', 'cookienod')
                    )
                );

            case 'contact':
                $contact_email = get_option('admin_email');
                return sprintf(
                    '<h2>%s</h2><p>%s</p>',
                    __('Contact Us', 'cookienod'),
                    sprintf(
                        /* translators: %s: Email address or contact link */
                        __('If you have any questions about our use of cookies or this Cookie Policy, please contact us at %s.', 'cookienod'),
                        sprintf('<a href="mailto:%1$s">%2$s</a>', esc_url('mailto:' . antispambot($contact_email)), esc_html(antispambot($contact_email)))
                    )
                );

            default:
                return '';
        }
    }

    /**
     * Generate cookie table
     */
    private function generate_cookie_table($categorized_cookies) {
        $categories = array(
            'necessary' => __('Necessary', 'cookienod'),
            'functional' => __('Functional', 'cookienod'),
            'analytics' => __('Analytics', 'cookienod'),
            'marketing' => __('Marketing', 'cookienod'),
        );

        $html = '<div class="cookienod-cookie-table">';

        foreach ($categories as $key => $label) {
            if (empty($categorized_cookies[$key])) {
                continue;
            }

            $html .= sprintf('<h3>%s</h3>', esc_html($label));
            $html .= '<table class="wp-list-table widefat striped">';
            $html .= '<thead><tr>';
            $html .= '<th>' . esc_html__('Cookie Name', 'cookienod') . '</th>';
            $html .= '<th>' . esc_html__('Provider', 'cookienod') . '</th>';
            $html .= '<th>' . esc_html__('Purpose', 'cookienod') . '</th>';
            $html .= '<th>' . esc_html__('Expiry', 'cookienod') . '</th>';
            $html .= '</tr></thead><tbody>';

            foreach ($categorized_cookies[$key] as $cookie) {
                $html .= '<tr>';
                $html .= '<td><code>' . esc_html($cookie['name']) . '</code></td>';
                $html .= '<td>' . esc_html($cookie['provider'] ?? $this->get_provider_from_domain($cookie['domain'] ?? '')) . '</td>';
                $html .= '<td>' . esc_html($this->get_cookie_purpose($cookie['name'], $key)) . '</td>';
                $html .= '<td>' . esc_html($cookie['expiry'] ?? 'Session') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody></table>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get cookie purpose description
     */
    private function get_cookie_purpose($name, $category) {
        $purposes = array(
            'necessary' => __('Essential for website functionality', 'cookienod'),
            'functional' => __('Enables enhanced functionality', 'cookienod'),
            'analytics' => __('Helps us analyze website usage', 'cookienod'),
            'marketing' => __('Used for advertising purposes', 'cookienod'),
        );

        return $purposes[$category] ?? __('Unknown purpose', 'cookienod');
    }

    /**
     * Get provider from domain
     */
    private function get_provider_from_domain($domain) {
        $providers = array(
            'google' => 'Google',
            'facebook' => 'Facebook',
            'twitter' => 'Twitter',
            'linkedin' => 'LinkedIn',
            'amazon' => 'Amazon',
            'youtube' => 'YouTube',
            'vimeo' => 'Vimeo',
        );

        foreach ($providers as $key => $name) {
            if (strpos($domain, $key) !== false) {
                return $name;
            }
        }

        return $domain ?: __('First Party', 'cookienod');
    }

    /**
     * AJAX generate policy
     */
    public function ajax_generate_policy() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $template = sanitize_key($_POST['template'] ?? 'combined');
        $admin = new CookieNod_Admin();
        $cookies = $admin->get_detected_cookies();
        $categorized = $admin->categorize_cookies($cookies);

        $content = $this->generate_policy_content($template, $categorized, true);

        wp_send_json_success(array(
            'content' => $content,
            'template' => $template,
        ));
    }

    /**
     * AJAX update policy
     */
    public function ajax_update_policy() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $page_id = intval($_POST['page_id'] ?? 0);
        $content = wp_kses_post(wp_unslash($_POST['content'] ?? ''));

        if (!$page_id) {
            wp_send_json_error('Invalid page ID');
        }

        wp_update_post(array(
            'ID' => $page_id,
            'post_content' => $content,
        ));

        update_post_meta($page_id, '_cookienod_policy_last_updated', current_time('mysql'));

        wp_send_json_success(array('updated' => true));
    }

    /**
     * AJAX create policy page
     */
    public function ajax_create_policy_page() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $template = sanitize_key($_POST['template'] ?? 'combined');

        // Check if page already exists
        $existing = get_page_by_path('cookie-policy');
        if ($existing) {
            wp_send_json_error(__('A cookie policy page already exists.', 'cookienod'));
        }

        // Generate content
        $admin = new CookieNod_Admin();
        $cookies = $admin->get_detected_cookies();
        $categorized = $admin->categorize_cookies($cookies);
        $content = $this->generate_policy_content($template, $categorized, true);

        // Create page
        $page_id = wp_insert_post(array(
            'post_title' => __('Cookie Policy', 'cookienod'),
            'post_name' => 'cookie-policy',
            'post_content' => $content,
            'post_status' => 'publish',
            'post_type' => 'page',
            'meta_input' => array(
                '_cookienod_policy_page' => true,
                '_cookienod_policy_template' => $template,
                '_cookienod_policy_created' => current_time('mysql'),
            ),
        ));

        if ($page_id) {
            // Update option
            update_option('cookienod_policy_page_id', $page_id);

            wp_send_json_success(array(
                'page_id' => $page_id,
                'url' => get_permalink($page_id),
                'edit_url' => admin_url('post.php?post=' . $page_id . '&action=edit'),
            ));
        } else {
            wp_send_json_error('Failed to create page');
        }
    }

    /**
     * Maybe update policy when new cookies detected
     */
    public function maybe_update_policy($new_cookies) {
        $page_id = get_option('cookienod_policy_page_id');
        if (!$page_id) {
            return;
        }

        $template = get_post_meta($page_id, '_cookienod_policy_template', true) ?: 'combined';
        $auto_update = $this->options['policy_auto_update'] ?? true;

        if (!$auto_update) {
            return;
        }

        $admin = new CookieNod_Admin();
        $cookies = $admin->get_detected_cookies();
        $categorized = $admin->categorize_cookies($cookies);
        $content = $this->generate_policy_content($template, $categorized, true);

        wp_update_post(array(
            'ID' => $page_id,
            'post_content' => $content,
        ));

        update_post_meta($page_id, '_cookienod_policy_last_updated', current_time('mysql'));
    }

    /**
     * Add meta box to policy pages
     */
    public function add_policy_meta_box() {
        add_meta_box(
            'cookienod_policy_meta',
            __('CookieNod Policy', 'cookienod'),
            array($this, 'render_policy_meta_box'),
            'page',
            'side',
            'high'
        );
    }

    /**
     * Render policy meta box
     */
    public function render_policy_meta_box($post) {
        $is_policy_page = get_post_meta($post->ID, '_cookienod_policy_page', true);
        $template = get_post_meta($post->ID, '_cookienod_policy_template', true);
        $created = get_post_meta($post->ID, '_cookienod_policy_created', true);
        $updated = get_post_meta($post->ID, '_cookienod_policy_last_updated', true);

        wp_nonce_field('cookienod_policy_meta', 'cookienod_policy_meta_nonce');
        ?>
        <label>
            <input type="checkbox" name="cookienod_is_policy_page" value="1" <?php checked($is_policy_page); ?> />
            <?php esc_html_e('This is a Cookie Policy page', 'cookienod'); ?>
        </label>

        <?php if ($is_policy_page) : ?>
            <p><strong><?php esc_html_e('Template:', 'cookienod'); ?></strong> <?php echo esc_html($template); ?></p>
            <p><strong><?php esc_html_e('Created:', 'cookienod'); ?></strong> <?php echo esc_html($created); ?></p>
            <?php if ($updated) : ?>
                <p><strong><?php esc_html_e('Last Updated:', 'cookienod'); ?></strong> <?php echo esc_html($updated); ?></p>
            <?php endif; ?>

            <p>
                <button type="button" class="button" id="regenerate-policy"><?php esc_html_e('Regenerate Policy', 'cookienod'); ?></button>
            </p>
        <?php endif; ?>
        <?php
    }

    /**
     * Save policy meta box
     */
    public function save_policy_meta_box($post_id) {
        if (!isset($_POST['cookienod_policy_meta_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cookienod_policy_meta_nonce'])), 'cookienod_policy_meta')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $is_policy = isset($_POST['cookienod_is_policy_page']) ? true : false;

        if ($is_policy) {
            update_post_meta($post_id, '_cookienod_policy_page', true);
            update_option('cookienod_policy_page_id', $post_id);
        } else {
            delete_post_meta($post_id, '_cookienod_policy_page');
        }
    }

    /**
     * Get available templates
     */
    public function get_templates() {
        $list = array();
        foreach ($this->templates as $key => $template) {
            $list[$key] = $template['name'];
        }
        return $list;
    }

    /**
     * Export policy as PDF
     */
    public function export_pdf($page_id) {
        // This would require a PDF library like TCPDF or mPDF
        // For now, return the HTML content
        $post = get_post($page_id);
        if (!$post) {
            return false;
        }

        // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WordPress core hook
        return apply_filters('the_content', $post->post_content);
    }
}
