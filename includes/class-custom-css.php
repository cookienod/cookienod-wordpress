<?php
/**
 * Custom CSS Editor for Banner
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_Custom_CSS
 * Handles custom CSS editing and preview
 */
class CookieNod_Custom_CSS {

    /**
     * Options
     */
    private $options;

    /**
     * Predefined themes
     */
    private $themes = array();

    /**
     * Check whether frontend CookieNod CSS should run.
     *
     * @return bool
     */
    private function has_valid_api_key() {
        $api_key = $this->options['api_key'] ?? '';
        return !empty($api_key);
    }

    /**
     * Safe CSS properties whitelist
     */
    private $safe_properties = array(
        'background', 'background-color', 'background-image', 'background-position',
        'background-size', 'background-repeat', 'background-attachment',
        'color', 'font', 'font-family', 'font-size', 'font-weight', 'font-style',
        'line-height', 'letter-spacing', 'text-align', 'text-decoration',
        'text-transform', 'text-shadow', 'white-space', 'word-wrap',
        'margin', 'margin-top', 'margin-right', 'margin-bottom', 'margin-left',
        'padding', 'padding-top', 'padding-right', 'padding-bottom', 'padding-left',
        'border', 'border-top', 'border-right', 'border-bottom', 'border-left',
        'border-width', 'border-style', 'border-color', 'border-radius',
        'border-top-left-radius', 'border-top-right-radius',
        'border-bottom-left-radius', 'border-bottom-right-radius',
        'box-shadow', 'opacity', 'cursor', 'display', 'visibility',
        'width', 'height', 'max-width', 'max-height', 'min-width', 'min-height',
        'position', 'top', 'right', 'bottom', 'left', 'z-index', 'overflow',
        'float', 'clear', 'align-items', 'justify-content', 'flex-direction',
        'flex-wrap', 'flex', 'flex-grow', 'flex-shrink', 'flex-basis',
        'gap', 'row-gap', 'column-gap', 'grid-template-columns', 'grid-template-rows',
        'grid-column', 'grid-row', 'transform', 'transition', 'animation',
        'outline', 'outline-color', 'outline-style', 'outline-width',
        'list-style', 'list-style-type', 'list-style-position', 'list-style-image',
    );

    /**
     * Safe selectors
     */
    private $safe_selectors = array(
        '#cs-consent-banner',
        '#cs-detailed-settings',
        '#cs-retrigger-btn',
        '.cs-banner-content',
        '.cs-banner-text',
        '.cs-banner-title',
        '.cs-banner-description',
        '.cs-banner-actions',
        '.cs-btn',
        '.cs-btn-primary',
        '.cs-btn-secondary',
        '.cs-btn-tertiary',
        '.cs-settings-modal',
        '.cs-settings-content',
        '.cs-category',
        '.cs-category-header',
        '.cs-category-title',
        '.cs-category-description',
        '.cs-toggle',
        '.cs-toggle-switch',
    );

    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_option('cookienod_wp_options', array());
        $this->init_themes();
        $this->init();
    }

    /**
     * Initialize hooks
     */
    private function init() {
        // Add CSS to frontend
        add_action('wp_head', array($this, 'output_custom_css'), 100);

        // Note: AJAX handlers are now registered in CookieNod_Core class
        // to ensure they're available for admin AJAX requests
    }

    /**
     * Initialize predefined themes
     */
    private function init_themes() {
        $this->themes = array(
            'minimal' => array(
                'name' => __('Minimal', 'cookienod'),
                'css' => '#cs-consent-banner {
    background: #ffffff;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

#cs-consent-banner .cs-btn-primary {
    background: #000000;
    color: #ffffff;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
}

#cs-consent-banner .cs-btn-secondary {
    background: transparent;
    color: #000000;
    border: 1px solid #000000;
    padding: 10px 20px;
    border-radius: 4px;
}',
            ),
            'rounded' => array(
                'name' => __('Rounded', 'cookienod'),
                'css' => '#cs-consent-banner {
    background: #ffffff;
    border-radius: 16px 16px 0 0;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
    margin: 0 20px 20px;
    max-width: calc(100% - 40px);
}

#cs-consent-banner .cs-btn {
    border-radius: 25px;
    padding: 12px 24px;
}

#cs-consent-banner .cs-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border: none;
}

#cs-retrigger-btn {
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(0,0,0,0.2);
}',
            ),
            'glassmorphism' => array(
                'name' => __('Glassmorphism', 'cookienod'),
                'css' => '#cs-consent-banner {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
}

#cs-consent-banner.dark {
    background: rgba(31, 41, 55, 0.85) !important;
    border-color: rgba(75, 85, 99, 0.3) !important;
}

#cs-consent-banner .cs-banner-title,
#cs-consent-banner .cs-banner-description {
    color: inherit;
}

#cs-consent-banner .cs-btn {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    border-radius: 8px;
    font-weight: 500;
}

#cs-consent-banner .cs-btn-primary {
    background: rgba(37, 99, 235, 0.9) !important;
    color: #ffffff !important;
    border: 1px solid rgba(37, 99, 235, 0.5) !important;
}

#cs-consent-banner .cs-btn-secondary {
    background: rgba(243, 244, 246, 0.8) !important;
    color: #1f2937 !important;
    border: 1px solid rgba(209, 213, 219, 0.5) !important;
}

#cs-consent-banner.dark .cs-btn-secondary {
    background: rgba(75, 85, 99, 0.5) !important;
    color: #f9fafb !important;
}

#cs-consent-banner .cs-btn-tertiary {
    background: rgba(255, 255, 255, 0.5) !important;
    border: 1px solid rgba(209, 213, 219, 0.3) !important;
}

#cs-consent-banner.dark .cs-btn-tertiary {
    background: rgba(55, 65, 81, 0.5) !important;
    color: #f9fafb !important;
}

#cs-retrigger-btn {
    background: rgba(255, 255, 255, 0.8);
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    border: 1px solid rgba(209, 213, 219, 0.5);
    border-radius: 50%;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}',
            ),
            'brutalist' => array(
                'name' => __('Brutalist', 'cookienod'),
                'css' => '#cs-consent-banner {
    background: #ff00ff;
    color: #000000;
    border: 4px solid #000000;
    box-shadow: 8px 8px 0 #000000;
    font-family: "Courier New", monospace;
    text-transform: uppercase;
}

#cs-consent-banner .cs-btn {
    border: 2px solid #000000;
    background: #ffff00;
    color: #000000;
    text-transform: uppercase;
    font-weight: bold;
}

#cs-consent-banner .cs-btn:hover {
    background: #00ffff;
}

#cs-retrigger-btn {
    background: #00ff00;
    border: 2px solid #000000;
    box-shadow: 4px 4px 0 #000000;
}',
            ),
            'elegant' => array(
                'name' => __('Elegant', 'cookienod'),
                'css' => '#cs-consent-banner {
    background: #faf8f5;
    border-top: 1px solid #e8e4df;
    font-family: "Georgia", serif;
}

#cs-consent-banner .cs-banner-title {
    font-weight: normal;
    font-size: 1.3em;
    color: #2c2c2c;
}

#cs-consent-banner .cs-btn {
    border-radius: 2px;
    font-family: "Helvetica Neue", sans-serif;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.8em;
}

#cs-consent-banner .cs-btn-primary {
    background: #2c2c2c;
    color: #faf8f5;
}

#cs-consent-banner .cs-btn-secondary {
    background: transparent;
    color: #2c2c2c;
    border: 1px solid #2c2c2c;
}',
            ),
        );
    }

    /**
     * Output custom CSS on frontend using wp_add_inline_style
     */
    public function output_custom_css() {
        if (!$this->has_valid_api_key()) {
            return;
        }

        $custom_css = $this->options['custom_css'] ?? '';

        if (empty($custom_css)) {
            return;
        }

        // Validate CSS before outputting
        $validated_css = $this->validate_css($custom_css);

        if (!empty($validated_css)) {
            // Register a handle if not already done, then add inline CSS
            if (!wp_style_is('cookienod-custom-frontend', 'registered')) {
                wp_register_style('cookienod-custom-frontend', false, [], COOKIENOD_VERSION);
            }
            wp_enqueue_style('cookienod-custom-frontend');
            wp_add_inline_style('cookienod-custom-frontend', $validated_css);
        }
    }

    /**
     * Validate CSS for safety
     */
    public function validate_css($css) {
        $css = wp_strip_all_tags($css);

        // Remove @ rules
        $css = preg_replace('/@[^{]*{[^}]*}/', '', $css);

        // Remove JavaScript
        $css = preg_replace('/expression\s*\(/i', '', $css);
        $css = preg_replace('/javascript:/i', '', $css);
        $css = preg_replace('/behavior:/i', '', $css);
        $css = preg_replace('/-moz-binding/i', '', $css);

        // Only allow specific properties
        $lines = explode("\n", $css);
        $validated_lines = array();

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip empty lines and comments
            if (empty($line) || strpos($line, '/*') === 0) {
                continue;
            }

            // Check if line contains a property
            if (preg_match('/^([a-zA-Z-]+)\s*:/', $line, $matches)) {
                $property = $matches[1];

                if (!in_array($property, $this->safe_properties)) {
                    // Skip this line
                    continue;
                }
            }

            $validated_lines[] = $line;
        }

        return implode("\n", $validated_lines);
    }

    /**
     * AJAX preview CSS
     */
    public function ajax_preview_css() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $css = sanitize_textarea_field(wp_unslash($_POST['css'] ?? ''));
        $validated = $this->validate_css($css);

        wp_send_json_success(array(
            'css' => $validated,
            'valid' => !empty($validated),
        ));
    }

    /**
     * AJAX generate preview HTML
     */
    public function ajax_generate_preview() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        // Get parameters
        $position = sanitize_text_field(wp_unslash($_POST['position'] ?? 'bottom'));
        $banner_theme = sanitize_text_field(wp_unslash($_POST['banner_theme'] ?? 'light'));
        $custom_css = sanitize_textarea_field(wp_unslash($_POST['custom_css'] ?? ''));

        // Validate CSS
        $validated_css = $this->validate_css($custom_css);

        // Load banner-preview.css from file (human-readable source)
        $css_file_path = COOKIENOD_PLUGIN_DIR . 'assets/css/banner-preview.css';
        $banner_css = '';
        if (file_exists($css_file_path)) {
            $banner_css = file_get_contents($css_file_path);
        }

        // Build the preview HTML
        $preview_html = '<!DOCTYPE html>' . "\n";
        $preview_html .= '<html>' . "\n";
        $preview_html .= '<head>' . "\n";
        $preview_html .= '    <meta charset="UTF-8">' . "\n";
        $preview_html .= '    <meta name="viewport" content="width=device-width, initial-scale=1.0">' . "\n";
        $preview_html .= '    <title>' . esc_html__('CookieNod Preview', 'cookienod') . '</title>' . "\n";
        $preview_html .= '    <style type="text/css">' . "\n";
        $preview_html .= '        ' . $banner_css . "\n";
        if (!empty($validated_css)) {
            $preview_html .= '        ' . $validated_css . "\n";
        }
        $preview_html .= '    </style>' . "\n";
        $preview_html .= '</head>' . "\n";
        $preview_html .= '<body>' . "\n";
        $preview_html .= '    <div class="preview-container">' . "\n";
        $preview_html .= '        <div class="preview-content">' . "\n";
        $preview_html .= '            <h1>' . esc_html__('Your Website', 'cookienod') . '</h1>' . "\n";
        $preview_html .= '            <p>' . esc_html__('This is a preview of how the consent banner will appear on your site.', 'cookienod') . '</p>' . "\n";
        $preview_html .= '        </div>' . "\n";
        $preview_html .= '        <div id="cs-consent-banner" class="position-' . esc_attr($position) . ' ' . esc_attr($banner_theme) . '">' . "\n";
        $preview_html .= '            <div class="cs-banner-content">' . "\n";
        $preview_html .= '                <h3 class="cs-banner-title">' . esc_html__('Cookie Preferences', 'cookienod') . '</h3>' . "\n";
        $preview_html .= '                <p class="cs-banner-description">' . esc_html__('We use cookies to enhance your experience. Choose your preferences below.', 'cookienod') . '</p>' . "\n";
        $preview_html .= '                <div class="cs-banner-actions">' . "\n";
        $preview_html .= '                    <button class="cs-btn cs-btn-secondary">' . esc_html__('Reject', 'cookienod') . '</button>' . "\n";
        $preview_html .= '                    <button class="cs-btn cs-btn-tertiary">' . esc_html__('Customize', 'cookienod') . '</button>' . "\n";
        $preview_html .= '                    <button class="cs-btn cs-btn-primary">' . esc_html__('Accept All', 'cookienod') . '</button>' . "\n";
        $preview_html .= '                </div>' . "\n";
        $preview_html .= '            </div>' . "\n";
        $preview_html .= '        </div>' . "\n";
        $preview_html .= '    </div>' . "\n";
        $preview_html .= '</body>' . "\n";
        $preview_html .= '</html>';

        wp_send_json_success(array(
            'preview_html' => $preview_html,
        ));
    }

    /**
     * AJAX validate CSS
     */
    public function ajax_validate_css() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $css = sanitize_textarea_field(wp_unslash($_POST['css'] ?? ''));
        $validated = $this->validate_css($css);

        // Parse CSS to find any removed rules
        $original_rules = $this->css_rule_count($css);
        $validated_rules = $this->css_rule_count($validated);

        wp_send_json_success(array(
            'valid' => true,
            'rules_removed' => $original_rules - $validated_rules,
            'validated_css' => $validated,
        ));
    }

    /**
     * AJAX load theme
     */
    public function ajax_load_theme() {
        check_ajax_referer('cookienod_wp_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }

        $theme = sanitize_key($_POST['theme'] ?? '');

        if (isset($this->themes[$theme])) {
            wp_send_json_success(array(
                'name' => $this->themes[$theme]['name'],
                'css' => $this->themes[$theme]['css'],
            ));
        }

        wp_send_json_error('Theme not found');
    }

    /**
     * Count CSS rules
     */
    private function css_rule_count($css) {
        return substr_count($css, '{');
    }

    /**
     * Get available themes
     */
    public function get_themes() {
        $themes = array();
        foreach ($this->themes as $key => $theme) {
            $themes[$key] = $theme['name'];
        }
        return $themes;
    }

    /**
     * Generate preview HTML
     * Note: This outputs a complete HTML document for iframe preview.
     * CSS is loaded via wp_enqueue_style for human-readable source compliance.
     */
    public function generate_preview_html() {
        $position = $this->options['banner_position'] ?? 'bottom';
        $custom_css = $this->options['custom_css'] ?? '';
        $validated_css = $this->validate_css($custom_css);
        $banner_theme = $this->options['banner_theme'] ?? 'light';
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php esc_html_e('CookieNod Preview', 'cookienod'); ?></title>
            <?php
            // Enqueue preview styles from file (human-readable source)
            wp_enqueue_style(
                'cookienod-banner-preview',
                COOKIENOD_PLUGIN_URL . 'assets/css/banner-preview.css',
                array(),
                COOKIENOD_VERSION
            );
            // Add custom CSS inline
            if (!empty($validated_css)) {
                wp_register_style('cookienod-preview-custom', false, [], COOKIENOD_VERSION);
                wp_enqueue_style('cookienod-preview-custom');
                wp_add_inline_style('cookienod-preview-custom', $validated_css);
            }
            ?>
        </head>
        <body>
            <div class="preview-container">
                <div class="preview-content">
                    <h1><?php esc_html_e('Your Website', 'cookienod'); ?></h1>
                    <p><?php esc_html_e('This is a preview of how the consent banner will appear on your site.', 'cookienod'); ?></p>
                </div>

                <!-- Banner Preview -->
                <div id="cs-consent-banner" class="position-<?php echo esc_attr($position); ?> <?php echo esc_attr($banner_theme); ?>">
                    <div class="cs-banner-content">
                        <h3 class="cs-banner-title"><?php esc_html_e('Cookie Preferences', 'cookienod'); ?></h3>
                        <p class="cs-banner-description">
                            <?php esc_html_e('We use cookies to enhance your experience. Choose your preferences below.', 'cookienod'); ?>
                        </p>
                        <div class="cs-banner-actions">
                            <button class="cs-btn cs-btn-secondary"><?php esc_html_e('Reject', 'cookienod'); ?></button>
                            <button class="cs-btn cs-btn-tertiary"><?php esc_html_e('Customize', 'cookienod'); ?></button>
                            <button class="cs-btn cs-btn-primary"><?php esc_html_e('Accept All', 'cookienod'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </body>
        </html>
        <?php
    }

    /**
     * Get CSS editor settings
     */
    public static function get_settings_fields() {
        return array(
            'custom_css' => array(
                'type' => 'textarea',
                'label' => __('Custom CSS', 'cookienod'),
                'description' => __('Customize the appearance of the consent banner with CSS', 'cookienod'),
                'rows' => 15,
            ),
            'css_theme' => array(
                'type' => 'select',
                'label' => __('Load Theme Template', 'cookienod'),
                'description' => __('Start with a pre-designed theme', 'cookienod'),
                'options' => array(), // Populated dynamically
            ),
        );
    }
}
