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
        // Main containers
        '#cs-consent-banner',
        '#cs-detailed-settings',
        '.cs-banner',
        '.cs-banner.cs-dark',
        '.cs-banner.cs-light',
        '.cs-settings',
        '.cs-settings.cs-dark',
        '.cs-settings.cs-light',
        // Buttons
        '#cs-retrigger-btn',
        '#cs-reject-btn',
        '#cs-customize-btn',
        '#cs-accept-btn',
        '#cs-save-prefs',
        '#cs-close-banner',
        '#cs-close-settings',
        // Category checkboxes
        '#cs-cat-necessary',
        '#cs-cat-functional',
        '#cs-cat-analytics',
        '#cs-cat-marketing',
        // Text elements
        '.cs-banner-title',
        '.cs-banner-description',
        '.cs-settings-title',
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
                'css' => '.cs-banner {
    background: #ffffff;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.1);
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
}

.cs-banner-title {
    font-size: 18px;
    font-weight: bold;
    color: #1f2937;
}

.cs-banner-description {
    font-size: 14px;
    color: #6b7280;
}

#cs-accept-btn {
    background: #000000;
    color: #ffffff;
    border: none;
    padding: 10px 20px;
    border-radius: 4px;
}

#cs-reject-btn, #cs-customize-btn {
    background: transparent;
    color: #000000;
    border: 1px solid #000000;
    padding: 10px 20px;
    border-radius: 4px;
}',
            ),
            'rounded' => array(
                'name' => __('Rounded', 'cookienod'),
                'css' => '.cs-banner {
    background: #ffffff;
    border-radius: 16px 16px 0 0;
    box-shadow: 0 -4px 20px rgba(0,0,0,0.15);
    margin: 0 20px 20px;
    max-width: calc(100% - 40px);
}

#cs-accept-btn, #cs-reject-btn, #cs-customize-btn {
    border-radius: 25px;
    padding: 12px 24px;
}

#cs-accept-btn {
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
                'css' => '.cs-banner {
    background: rgba(255, 255, 255, 0.85) !important;
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.3) !important;
    box-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.1);
}

.cs-banner.cs-dark {
    background: rgba(31, 41, 55, 0.85) !important;
    border-color: rgba(75, 85, 99, 0.3) !important;
}

.cs-banner-title,
.cs-banner-description {
    color: inherit;
}

#cs-accept-btn, #cs-reject-btn, #cs-customize-btn {
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    border-radius: 8px;
    font-weight: 500;
}

#cs-accept-btn {
    background: rgba(37, 99, 235, 0.9) !important;
    color: #ffffff !important;
    border: 1px solid rgba(37, 99, 235, 0.5) !important;
}

#cs-reject-btn {
    background: rgba(243, 244, 246, 0.8) !important;
    color: #1f2937 !important;
    border: 1px solid rgba(209, 213, 219, 0.5) !important;
}

.cs-banner.cs-dark #cs-reject-btn {
    background: rgba(75, 85, 99, 0.5) !important;
    color: #f9fafb !important;
}

#cs-customize-btn {
    background: rgba(255, 255, 255, 0.5) !important;
    border: 1px solid rgba(209, 213, 219, 0.3) !important;
}

.cs-banner.cs-dark #cs-customize-btn {
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
                'css' => '.cs-banner {
    background: #ff00ff;
    color: #000000;
    border: 4px solid #000000;
    box-shadow: 8px 8px 0 #000000;
    font-family: "Courier New", monospace;
    text-transform: uppercase;
}

#cs-accept-btn, #cs-reject-btn, #cs-customize-btn {
    border: 2px solid #000000;
    background: #ffff00;
    color: #000000;
    text-transform: uppercase;
    font-weight: bold;
}

#cs-accept-btn:hover, #cs-reject-btn:hover, #cs-customize-btn:hover {
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
                'css' => '.cs-banner {
    background: #faf8f5;
    border-top: 1px solid #e8e4df;
    font-family: "Georgia", serif;
}

.cs-banner-title {
    font-weight: normal;
    font-size: 1.3em;
    color: #2c2c2c;
}

#cs-accept-btn, #cs-reject-btn, #cs-customize-btn {
    border-radius: 2px;
    font-family: "Helvetica Neue", sans-serif;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-size: 0.8em;
}

#cs-accept-btn {
    background: #2c2c2c;
    color: #faf8f5;
}

#cs-reject-btn, #cs-customize-btn {
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
                wp_register_style('cookienod-custom-frontend', '', array(), COOKIENOD_VERSION);
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

        // Get current settings from database - ensures preview always reflects latest saved settings
        $options = get_option('cookienod_wp_options', array());
        $position = isset($options['banner_position']) ? $options['banner_position'] : 'bottom';
        $banner_theme = isset($options['banner_theme']) ? $options['banner_theme'] : 'light';

        // Custom CSS from request (optional override)
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

        // Modal overlay for center position - matches frontend JS (cookienod.min.js)
        // which creates overlay with: style="position:fixed;inset:0;background:rgba(0,0,0,0.5);..."
        if ($position === 'center') {
            $preview_html .= '        <div id="cs-modal-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999998;"></div>' . "\n";
        }

        $preview_html .= '        <div class="preview-content">' . "\n";
        $preview_html .= '            <h1>' . esc_html__('Your Website', 'cookienod') . '</h1>' . "\n";
        $preview_html .= '            <p>' . esc_html__('This is a preview of how the consent banner will appear on your site.', 'cookienod') . '</p>' . "\n";
        $preview_html .= '        </div>' . "\n";

        // Using inline styles instead of CSS classes to match frontend behavior.
        // The frontend JS (cookienod.min.js) applies position via inline styles:
        // - Center: position:absolute;top:50%;left:50%;transform:translate(-50%,-50%)
        // - Top: top:0;left:0;right:0
        // - Bottom: bottom:0;left:0;right:0
        // CSS classes like .position-center don't exist in the frontend.
        $banner_style = 'position:fixed;';
        if ($position === 'center') {
            $banner_style .= 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:90%;max-width:480px;max-height:90vh;overflow-y:auto;';
        } elseif ($position === 'top') {
            $banner_style .= 'top:0;left:0;right:0;';
        } else {
            $banner_style .= 'bottom:0;left:0;right:0;';
        }
        $banner_style .= 'z-index:999999;';

        $preview_html .= '        <div id="cs-consent-banner" class="cs-banner cs-' . esc_attr($banner_theme) . '" style="' . esc_attr($banner_style) . '">' . "\n";
        $preview_html .= '            <div class="cs-banner-content">' . "\n";
        $preview_html .= '                <h3 class="cs-banner-title">' . esc_html__('Cookie Preferences', 'cookienod') . '</h3>' . "\n";
        $preview_html .= '                <p class="cs-banner-description">' . esc_html__('We use cookies to enhance your experience. Choose your preferences below.', 'cookienod') . '</p>' . "\n";
        $preview_html .= '                <div class="cs-banner-actions">' . "\n";
        $preview_html .= '                    <button id="cs-reject-btn" class="cs-btn cs-btn-secondary">' . esc_html__('Reject', 'cookienod') . '</button>' . "\n";
        $preview_html .= '                    <button id="cs-customize-btn" class="cs-btn cs-btn-tertiary">' . esc_html__('Customize', 'cookienod') . '</button>' . "\n";
        $preview_html .= '                    <button id="cs-accept-btn" class="cs-btn cs-btn-primary">' . esc_html__('Accept All', 'cookienod') . '</button>' . "\n";
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
                            <button id="cs-reject-btn" class="cs-btn cs-btn-secondary"><?php esc_html_e('Reject', 'cookienod'); ?></button>
                            <button id="cs-customize-btn" class="cs-btn cs-btn-tertiary"><?php esc_html_e('Customize', 'cookienod'); ?></button>
                            <button id="cs-accept-btn" class="cs-btn cs-btn-primary"><?php esc_html_e('Accept All', 'cookienod'); ?></button>
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
