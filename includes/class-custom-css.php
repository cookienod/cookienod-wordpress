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
            'dark' => array(
                'name' => __('Dark Mode', 'cookienod'),
                'css' => '#cs-consent-banner {
    background: #1a1a2e;
    color: #ffffff;
    border-top-color: #30354d;
    box-shadow: 0 -2px 10px rgba(0,0,0,0.3);
}

#cs-consent-banner .cs-banner-title,
#cs-consent-banner .cs-banner-description {
    color: #ffffff;
}

#cs-consent-banner .cs-btn {
    border-color: #ffffff;
}

#cs-consent-banner .cs-btn-primary {
    background: #e94560;
    color: #ffffff;
    border-color: #e94560;
}

#cs-consent-banner .cs-btn-secondary {
    background: transparent;
    color: #ffffff;
    border: 1px solid #ffffff;
}

#cs-consent-banner .cs-btn-tertiary {
    background: #16213e;
    color: #ffffff;
    border: 1px solid #30354d;
}

#cs-detailed-settings {
    background: #1a1a2e;
    color: #ffffff;
}

#cs-detailed-settings .cs-settings-content {
    background: #16213e;
    color: #ffffff;
}',
            ),
            'glassmorphism' => array(
                'name' => __('Glassmorphism', 'cookienod'),
                'css' => '#cs-consent-banner {
    background: rgba(255, 255, 255, 0.25);
    backdrop-filter: blur(10px);
    -webkit-backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.18);
    box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.37);
}

#cs-consent-banner .cs-btn-primary {
    background: rgba(255, 255, 255, 0.3);
    border: 1px solid rgba(255, 255, 255, 0.4);
    backdrop-filter: blur(4px);
}

#cs-retrigger-btn {
    background: rgba(255, 255, 255, 0.3);
    backdrop-filter: blur(4px);
    border: 1px solid rgba(255, 255, 255, 0.4);
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
     * Output custom CSS on frontend
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
            echo "\n<!-- CookieNod Custom CSS -->\n";
            echo "<style type=\"text/css\">\n";
            echo esc_html($validated_css);
            echo "\n</style>\n";
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

        $css = sanitize_textarea_field($_POST['css'] ?? '');
        $validated = $this->validate_css($css);

        wp_send_json_success(array(
            'css' => $validated,
            'valid' => !empty($validated),
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

        $css = sanitize_textarea_field($_POST['css'] ?? '');
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
     */
    public function generate_preview_html() {
        $position = $this->options['banner_position'] ?? 'bottom';
        $custom_css = $this->options['custom_css'] ?? '';
        $validated_css = $this->validate_css($custom_css);

        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php _e('CookieNod Preview', 'cookienod'); ?></title>
            <style>
                body {
                    margin: 0;
                    padding: 20px;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    background: #f0f0f0;
                    min-height: 100vh;
                }

                .preview-container {
                    background: #fff;
                    min-height: 600px;
                    position: relative;
                    border-radius: 8px;
                    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                    overflow: hidden;
                }

                .preview-content {
                    padding: 40px;
                }

                #cs-consent-banner {
                    left: 0;
                    right: 0;
                    background: <?php echo (($this->options['banner_theme'] ?? 'light') === 'dark') ? '#1f2937' : '#fff'; ?>;
                    border-top: 1px solid <?php echo (($this->options['banner_theme'] ?? 'light') === 'dark') ? '#374151' : '#ddd'; ?>;
                    padding: 20px;
                    box-shadow: <?php echo (($this->options['banner_theme'] ?? 'light') === 'dark') ? '0 -2px 10px rgba(0,0,0,0.25)' : '0 -2px 10px rgba(0,0,0,0.1)'; ?>;
                    color: <?php echo (($this->options['banner_theme'] ?? 'light') === 'dark') ? '#f9fafb' : '#1d2327'; ?>;
                }

                #cs-consent-banner.position-top {
                    position: absolute;
                    top: 0;
                }

                #cs-consent-banner.position-bottom {
                    position: absolute;
                    bottom: 0;
                }

                #cs-consent-banner .cs-banner-title {
                    margin: 0 0 8px;
                    font-size: 20px;
                    color: inherit;
                }

                #cs-consent-banner .cs-banner-description {
                    margin: 0;
                    color: inherit;
                }

                #cs-consent-banner .cs-banner-actions {
                    margin-top: 15px;
                    display: flex;
                    gap: 10px;
                    flex-wrap: wrap;
                }

                #cs-consent-banner .cs-btn {
                    padding: 8px 16px;
                    border: 1px solid <?php echo (($this->options['banner_theme'] ?? 'light') === 'dark') ? '#4b5563' : '#ccc'; ?>;
                    background: <?php echo (($this->options['banner_theme'] ?? 'light') === 'dark') ? '#111827' : '#f6f7f7'; ?>;
                    color: <?php echo (($this->options['banner_theme'] ?? 'light') === 'dark') ? '#f9fafb' : '#1d2327'; ?>;
                    cursor: pointer;
                    border-radius: 4px;
                }

                #cs-consent-banner .cs-btn-primary {
                    background: <?php echo (($this->options['banner_theme'] ?? 'light') === 'dark') ? '#2563eb' : '#2271b1'; ?>;
                    color: #fff;
                    border-color: <?php echo (($this->options['banner_theme'] ?? 'light') === 'dark') ? '#2563eb' : '#2271b1'; ?>;
                }

                #cs-consent-banner .cs-btn-secondary {
                    background: transparent;
                    color: inherit;
                    border-color: <?php echo (($this->options['banner_theme'] ?? 'light') === 'dark') ? '#9ca3af' : '#ccc'; ?>;
                }

                #cs-consent-banner .cs-btn-tertiary {
                    background: <?php echo (($this->options['banner_theme'] ?? 'light') === 'dark') ? '#374151' : '#f0f0f1'; ?>;
                }

                <?php echo esc_html($validated_css); ?>
            </style>
        </head>
        <body>
            <div class="preview-container">
                <div class="preview-content">
                    <h1><?php _e('Your Website', 'cookienod'); ?></h1>
                    <p><?php _e('This is a preview of how the consent banner will appear on your site.', 'cookienod'); ?></p>
                </div>

                <!-- Banner Preview -->
                <div id="cs-consent-banner" class="position-<?php echo esc_attr($position); ?>">
                    <div class="cs-banner-content">
                        <h3 class="cs-banner-title"><?php _e('Cookie Preferences', 'cookienod'); ?></h3>
                        <p class="cs-banner-description">
                            <?php _e('We use cookies to enhance your experience. Choose your preferences below.', 'cookienod'); ?>
                        </p>
                        <div class="cs-banner-actions">
                            <button class="cs-btn cs-btn-secondary"><?php _e('Reject', 'cookienod'); ?></button>
                            <button class="cs-btn cs-btn-tertiary"><?php _e('Customize', 'cookienod'); ?></button>
                            <button class="cs-btn cs-btn-primary"><?php _e('Accept All', 'cookienod'); ?></button>
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
