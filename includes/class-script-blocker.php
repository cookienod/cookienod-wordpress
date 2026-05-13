<?php
/**
 * Script Blocker - Blocks third-party scripts until consent is given
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_Script_Blocker
 * Blocks and manages third-party scripts based on consent
 */
class CookieNod_Script_Blocker {

    /**
     * Known script patterns by category
     */
    private $script_patterns = array(
        'analytics' => array(
            // Google Analytics
            '/google-analytics\.com\/analytics\.js/',
            '/google-analytics\.com\/gtag\/js/',
            '/googletagmanager\.com\/gtm\.js/',
            '/googletagmanager\.com\/gtag\/js/',
            // Adobe Analytics
            '/adobedtm\.com/',
            '/omniture\.com/',
            '/2o7\.net/',
            // Matomo/Piwik
            '/matomo\.js/',
            '/piwik\.js/',
            // Other analytics
            '/hotjar\.com/',
            '/crazyegg\.com/',
            '/mouseflow\.com/',
            '/fullstory\.com/',
            '/mixpanel\.com/',
            '/segment\.com/',
            '/segment\.io/',
            '/amplitude\.com/',
            '/heap-analytics\.com/',
            '/kissmetrics\.com/',
            '/optimizely\.com/',
            '/clarity\.ms/',
            '/quantserve\.com/',
            '/chartbeat\.com/',
            '/parsely\.com/',
            '/newrelic\.com/',
            '/nr-data\.net/',
            // WordPress specific
            '/monsterinsights/',
            '/exactmetrics/',
        ),
        'marketing' => array(
            // Meta/Facebook
            '/connect\.facebook\.net/',
            '/facebook\.com\/tr/',
            '/facebook\.com\/sdk/',
            // Google Ads
            '/googleadservices\.com/',
            '/google\.com\/ads/',
            '/googlesyndication\.com/',
            '/googletagservices\.com/',
            // LinkedIn
            '/linkedin\.com\/in\.js/',
            '/licdn\.com/',
            // Twitter/X
            '/twitter\.com\/widgets\.js/',
            '/platform\.twitter\.com/',
            // Pinterest
            '/pinterest\.com/',
            '/pinimg\.com/',
            // TikTok
            '/tiktok\.com/',
            // Other ad networks
            '/doubleclick\.net/',
            '/amazon-adsystem\.com/',
            '/criteo\.net/',
            '/criteo\.com/',
            '/outbrain\.com/',
            '/taboola\.com/',
            '/sharethrough\.com/',
            '/appnexus\.com/',
            '/openx\.net/',
            '/pubmatic\.com/',
            '/rubiconproject\.com/',
            '/casalemedia\.com/',
            '/yieldmanager\.com/',
            '/bidswitch\.net/',
            '/adsrvr\.org/',
            '/advertising\.com/',
            '/adform\.net/',
            '/adnxs\.com/',
            '/quantserve\.com/',
            '/advertising\.yahoo\.com/',
            '/bing\.com\/bat\.js/',
            '/bat\.bing\.com/',
        ),
        'functional' => array(
            // Chat widgets
            '/intercom\.io/',
            '/crisp\.chat/',
            '/crisp\.js/',
            '/tawk\.to/',
            '/tawk\.js/',
            '/zendesk\.com/',
            '/zopim\.com/',
            '/livechatinc\.com/',
            '/livechat\.com/',
            '/chatra\.io/',
            '/drift\.com/',
            '/hubspot\.com\/conversations/',
            '/hs-scripts\.com/',
            '/hubspotfeedback\.com/',
            '/purechat\.com/',
            '/olark\.com/',
            '/userlike\.com/',
            '/liveperson\.com/',
            '/smartsupp\.com/',
            '/chatwoot\.com/',
            // Forms
            '/typeform\.com/',
            '/forms\.typeform\.com/',
            '/forminator\.js/', // Forminator forms
            '/wpforms\.js/',
            '/gravityforms\.js/',
            '/contact-form-7/',
            '/calendly\.com/',
            '/calendly\.js/',
            // Maps
            '/maps\.google\.com/',
            '/maps\.googleapis\.com/',
            // Social sharing
            '/addthis\.com/',
            '/addtoany\.com/',
            '/sharethis\.com/',
            '/sumome\.com/',
            // Video
            '/youtube\.com\/embed/',
            '/vimeo\.com/',
            '/wistia\.net/',
            '/dailymotion\.com/',
            // Other
            '/gravatar\.com/',
            '/recaptcha\/api\.js/',
            '/recaptcha\.net/',
            '/hcaptcha\.com/',
            '/cookienod\.com/', // Prevent recursion
            // Elementor
            '/elementor\/assets\/lib\/eicons\/css\/elementor-icons\.min\.css/',
            '/elementor\/assets\/lib\/font-awesome\/css\/fontawesome\.min\.css/',
            '/elementor\/assets\/lib\/swiper\/swiper\.min\.js/',
        ),
        'elementor' => array(
            // Elementor scripts that should be blocked based on settings
            '/elementor\/assets\/js\/frontend\.min\.js/',
            '/elementor\/assets\/lib\/waypoints\/waypoints\.min\.js/',
            '/elementor\/assets\/lib\/swiper\/swiper\.min\.js/',
            '/elementor\/assets\/lib\/share-link\/share-link\.min\.js/',
        ),
    );

    /**
     * Iframe patterns
     */
    private $iframe_patterns = array(
        'marketing' => array(
            '/youtube\.com\/embed/',
            '/vimeo\.com/',
            '/dailymotion\.com/',
            '/google\.com\/maps\/embed/',
        ),
    );

    /**
     * Options
     */
    private $options;

    /**
     * Is blocking enabled
     */
    private $blocking_enabled = false;

    /**
     * Is silent mode enabled
     */
    private $silent_mode = false;

    /**
     * Consent status
     */
    private $consent = array();

    /**
     * Output buffer level when we started buffering
     *
     * @var int
     */
    private $buffer_level = 0;

    /**
     * Constructor
     */
    public function __construct() {
        $this->options = get_option('cookienod_wp_options', array());
        $block_mode = $this->options['block_mode'] ?? 'auto';

        // Server-side blocking:
        // - 'manual': Show placeholders (legacy mode)
        // - 'silent': Hide scripts, no placeholders (server blocks, JS loads after consent)
        // - 'auto': No server blocking - JS handles everything
        $this->blocking_enabled = in_array($block_mode, array('manual', 'silent'));
        $this->silent_mode = ($block_mode === 'silent');

        // Check saved consent
        if ( isset( $_COOKIE['cookienod_consent'] ) ) {

            // Unslash + sanitize first (required for PHPCS)
            $cookie_value = sanitize_text_field(
                wp_unslash( $_COOKIE['cookienod_consent'] )
            );

            $raw_consent = json_decode( $cookie_value, true );

            // Validate consent structure - only allow known category keys with boolean values
            if ( is_array( $raw_consent ) ) {
                $allowed_categories = array( 'necessary', 'functional', 'analytics', 'marketing' );

                foreach ( $allowed_categories as $category ) {
                    if ( isset( $raw_consent[ $category ] ) ) {
                        $this->consent[ $category ] = rest_sanitize_boolean( $raw_consent[ $category ] );
                    }
                }
            }
        }      

        $this->init();
    }

    /**
     * Initialize hooks
     */
    private function init() {
        if (!$this->blocking_enabled) {
            return;
        }

        // Output buffer to capture and block scripts
        add_action('template_redirect', array($this, 'start_buffer'), 1);
        add_action('shutdown', array($this, 'end_buffer'), 0);

        // Block enqueued scripts
        add_action('wp_print_scripts', array($this, 'block_enqueued_scripts'), 100);

        // Add placeholder styles
        add_action('wp_head', array($this, 'output_placeholder_styles'));

        // Filter content for iframes
        add_filter('the_content', array($this, 'filter_content_iframes'), 100);
        add_filter('widget_text_content', array($this, 'filter_content_iframes'), 100);
        add_filter('embed_oembed_html', array($this, 'filter_oembed_html'), 100, 2);

        // Filter script tags in template - run late to let other filters add attributes first
        add_filter('script_loader_tag', array($this, 'filter_script_tag'), 999, 3);

        // Custom embeds
        add_filter('wp_video_shortcode', array($this, 'filter_video_shortcode'), 10, 5);
        add_filter('wp_audio_shortcode', array($this, 'filter_audio_shortcode'), 10, 5);
    }

    /**
     * Start output buffering and track the buffer level
     */
    public function start_buffer() {
        if (!is_admin() && !wp_is_json_request()) {
            ob_start(array($this, 'buffer_callback'));
            $this->buffer_level = ob_get_level();
        }
    }

    /**
     * End output buffering - only closes our own buffer
     */
    public function end_buffer() {
        // Only close if we have a tracked buffer level and it still exists
        if ($this->buffer_level > 0 && ob_get_level() >= $this->buffer_level) {
            ob_end_flush();
            $this->buffer_level = 0; // Reset to prevent double-closing
        }
    }

    /**
     * Buffer callback - process HTML
     */
    public function buffer_callback($buffer) {
        if (empty($buffer)) {
            return $buffer;
        }

        // Process scripts
        $buffer = $this->process_scripts_in_buffer($buffer);

        // Process iframes
        $buffer = $this->process_iframes_in_buffer($buffer);

        // Process images (for external tracking pixels)
        $buffer = $this->process_tracking_pixels($buffer);

        return $buffer;
    }

    /**
     * Process scripts in buffer
     */
    private function process_scripts_in_buffer($buffer) {
        $blocked_categories = $this->get_blocked_categories();

        if (empty($blocked_categories)) {
            return $buffer;
        }

        // Pattern to match script tags
        $pattern = '/<script[^>]*>.*?<\/script>/si';

        $buffer = preg_replace_callback($pattern, function($matches) use ($blocked_categories) {
            $script = $matches[0];

            // Never block the CookieNod script itself
            if (strpos($script, 'cookienod.com/cookienod') !== false) {
                return $script;
            }

            $category = $this->get_script_category($script);

            if ($category && in_array($category, $blocked_categories)) {
                return $this->wrap_blocked_script($script, $category);
            }

            return $script;
        }, $buffer);

        return $buffer;
    }

    /**
     * Process iframes in buffer
     */
    private function process_iframes_in_buffer($buffer) {
        $blocked_categories = $this->get_blocked_categories();

        if (empty($blocked_categories) || !in_array('marketing', $blocked_categories)) {
            return $buffer;
        }

        // Pattern to match iframes
        $pattern = '/<iframe[^>]*>.*?<\/iframe>/si';

        $buffer = preg_replace_callback($pattern, function($matches) {
            $iframe = $matches[0];

            // Check if it's a blocked iframe
            foreach ($this->iframe_patterns['marketing'] as $pattern) {
                if (preg_match($pattern, $iframe)) {
                    return $this->wrap_blocked_iframe($iframe);
                }
            }

            return $iframe;
        }, $buffer);

        return $buffer;
    }

    /**
     * Process tracking pixels
     */
    private function process_tracking_pixels($buffer) {
        // Pattern to match external image requests that are likely tracking pixels
        $patterns = array(
            '/<img[^>]*src=["\'](?:https?:)?\/\/[^"\']*(?:facebook\.com\/tr\/|google-analytics\.com\/collect|doubleclick\.net\/[^"\']*imp|googleadservices\.com\/[^"\']*conversion)[^"\']*["\'][^>]*>/i',
        );

        foreach ($patterns as $pattern) {
            $buffer = preg_replace_callback($pattern, function($matches) {
                return $this->wrap_blocked_image($matches[0]);
            }, $buffer);
        }

        return $buffer;
    }

    /**
     * Get script category
     */
    private function get_script_category($script) {
        foreach ($this->script_patterns as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $script)) {
                    return $category;
                }
            }
        }
        return null;
    }

    /**
     * Get blocked categories based on consent
     */
    private function get_blocked_categories() {
        $blocked = array();
        $categories = array('functional', 'analytics', 'marketing');

        foreach ($categories as $cat) {
            if (empty($this->consent[$cat])) {
                $blocked[] = $cat;
            }
        }

        return $blocked;
    }

    /**
     * Wrap blocked script
     */
    private function wrap_blocked_script($script, $category) {
        // In silent mode, hide the script but don't show placeholder
        // JavaScript will load it after consent
        if ($this->silent_mode) {
            return sprintf(
                '<!-- CookieNod: Blocked %s script - loads after consent -->' .
                '<script type="text/cookienod" data-category="%s" data-script="%s"></script>',
                esc_html($category),
                esc_attr($category),
                esc_attr(base64_encode($script))
            );
        }

        // Original placeholder mode
        $type = $this->get_category_label($category);
        $placeholder = sprintf(
            '<div class="cookienod-blocked-script" data-category="%s" data-script="%s">' .
            '<div class="cookienod-placeholder">' .
            '<span class="cookienod-placeholder-icon">🔒</span>' .
            '<p>%s %s</p>' .
            '<button class="cookienod-load-script" data-category="%s">%s</button>' .
            '</div>' .
            '<noscript>%s</noscript>' .
            '</div>',
            esc_attr($category),
            esc_attr(base64_encode($script)),
            esc_html__('Blocked:', 'cookienod'),
            esc_html($type),
            esc_attr($category),
            esc_html__('Load Script', 'cookienod'),
            $script
        );

        return $placeholder;
    }

    /**
     * Wrap blocked iframe
     */
    private function wrap_blocked_iframe($iframe) {
        // Extract src for display
        preg_match('/src=["\']([^"\']+)["\']/', $iframe, $matches);
        $src = isset($matches[1]) ? $matches[1] : '';
        $host = wp_parse_url($src, PHP_URL_HOST);

        $placeholder = sprintf(
            '<div class="cookienod-blocked-iframe" data-src="%s">' .
            '<div class="cookienod-iframe-placeholder">' .
            '<span class="cookienod-placeholder-icon">📺</span>' .
            '<p>%s %s</p>' .
            '<button class="cookienod-load-iframe">%s</button>' .
            '</div>' .
            '</div>',
            esc_attr($src),
            esc_html__('Blocked content from:', 'cookienod'),
            esc_html($host),
            esc_html__('Load Content', 'cookienod')
        );

        return $placeholder;
    }

    /**
     * Wrap blocked image
     */
    private function wrap_blocked_image($image) {
        return '<!-- Blocked by CookieNod -->' . $image . '<!-- End Blocked -->';
    }

    /**
     * Block enqueued scripts
     */
    public function block_enqueued_scripts() {
        global $wp_scripts;

        if (!is_object($wp_scripts)) {
            return;
        }

        $blocked_categories = $this->get_blocked_categories();
        if (empty($blocked_categories)) {
            return;
        }

        $blocked_handles = array();

        foreach ($wp_scripts->registered as $handle => $script) {
            // Never block the CookieNod script
            if ($handle === 'cookienod') {
                continue;
            }

            $src = $script->src;

            foreach ($this->script_patterns as $category => $patterns) {
                if (!in_array($category, $blocked_categories)) {
                    continue;
                }

                foreach ($patterns as $pattern) {
                    if (preg_match($pattern, $src)) {
                        $blocked_handles[] = $handle;
                        break 2;
                    }
                }
            }
        }

        foreach ($blocked_handles as $handle) {
            wp_dequeue_script($handle);
        }
    }

    /**
     * Filter script tag
     */
    public function filter_script_tag($tag, $handle, $src) {
        // Never block the CookieNod script
        if (strpos($src, 'cookienod.com/cookienod') !== false) {
            return $tag;
        }

        $blocked_categories = $this->get_blocked_categories();

        foreach ($this->script_patterns as $category => $patterns) {
            if (!in_array($category, $blocked_categories)) {
                continue;
            }

            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $src)) {
                    return $this->wrap_blocked_script($tag, $category);
                }
            }
        }

        return $tag;
    }

    /**
     * Filter content iframes
     */
    public function filter_content_iframes($content) {
        return $this->process_iframes_in_buffer($content);
    }

    /**
     * Filter oembed HTML
     */
    public function filter_oembed_html($html, $url) {
        // Check if it's a blocked provider
        foreach ($this->iframe_patterns['marketing'] as $pattern) {
            if (preg_match($pattern, $url)) {
                return $this->wrap_blocked_iframe($html);
            }
        }

        return $html;
    }

    /**
     * Filter video shortcode
     */
    public function filter_video_shortcode($output, $atts, $video, $post_id, $library) {
        // Check if external video
        if (strpos($output, 'youtube') !== false || strpos($output, 'vimeo') !== false) {
            $blocked_categories = $this->get_blocked_categories();
            if (in_array('marketing', $blocked_categories)) {
                return $this->wrap_blocked_iframe($output);
            }
        }
        return $output;
    }

    /**
     * Filter audio shortcode
     */
    public function filter_audio_shortcode($output, $atts, $audio, $post_id, $library) {
        return $output;
    }

    /**
     * Output placeholder styles using wp_enqueue_style
     */
    public function output_placeholder_styles() {
        // Enqueue placeholder styles from file
        wp_enqueue_style(
            'cookienod-script-blocker',
            COOKIENOD_PLUGIN_URL . 'assets/css/script-blocker.css',
            array(),
            COOKIENOD_VERSION
        );

        // Add script loader for silent mode
        if ($this->silent_mode) {
            $this->output_silent_mode_script();
        }
    }

    /**
     * Output JavaScript for silent mode using wp_add_inline_script
     */
    private function output_silent_mode_script() {
        wp_register_script('cookienod-silent-mode', false, array(), COOKIENOD_VERSION, true);
        wp_enqueue_script('cookienod-silent-mode');

        $inline_script = "/* CookieNod Silent Mode - Load blocked scripts after consent */\n";
        $inline_script .= "(function(){\n";
        $inline_script .= "  function loadBlockedScripts(category){\n";
        $inline_script .= "    var scripts = document.querySelectorAll('script[type=\"text/cookienod\"][data-category=\"' + category + '\"]');\n";
        $inline_script .= "    scripts.forEach(function(script){\n";
        $inline_script .= "      var encodedScript = script.getAttribute('data-script');\n";
        $inline_script .= "      if(encodedScript){\n";
        $inline_script .= "        try{\n";
        $inline_script .= "          var decoded = atob(encodedScript);\n";
        $inline_script .= "          var temp = document.createElement('div');\n";
        $inline_script .= "          temp.innerHTML = decoded;\n";
        $inline_script .= "          var newScript = temp.firstChild;\n";
        $inline_script .= "          if(newScript){document.head.appendChild(newScript);}\n";
        $inline_script .= "          script.remove();\n";
        $inline_script .= "        }catch(e){console.error('CookieNod: Failed to load blocked script',e);}\n";
        $inline_script .= "      }\n";
        $inline_script .= "    });\n";
        $inline_script .= "  }\n";
        $inline_script .= "  window.addEventListener('cookiePreferencesChanged',function(e){\n";
        $inline_script .= "    if(e.detail){['functional','analytics','marketing'].forEach(function(cat){if(e.detail[cat]){loadBlockedScripts(cat);}});}\n";
        $inline_script .= "  });\n";
        $inline_script .= "  document.addEventListener('DOMContentLoaded',function(){\n";
        $inline_script .= "    try{\n";
        $inline_script .= "      var prefs = localStorage.getItem('cs_cookie_prefs');\n";
        $inline_script .= "      if(prefs){var parsed = JSON.parse(prefs);['functional','analytics','marketing'].forEach(function(cat){if(parsed[cat]){loadBlockedScripts(cat);}});}\n";
        $inline_script .= "    }catch(e){}\n";
        $inline_script .= "  });\n";
        $inline_script .= "})();\n";

        wp_add_inline_script('cookienod-silent-mode', $inline_script);
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
     * Add custom script pattern
     */
    public function add_script_pattern($category, $pattern) {
        if (!isset($this->script_patterns[$category])) {
            $this->script_patterns[$category] = array();
        }
        $this->script_patterns[$category][] = $pattern;
    }

    /**
     * Get detected scripts on current page
     */
    public function get_detected_scripts() {
        $detected = array();
        global $wp_scripts;

        if (!is_object($wp_scripts)) {
            return $detected;
        }

        foreach ($wp_scripts->queue as $handle) {
            if (isset($wp_scripts->registered[$handle])) {
                $script = $wp_scripts->registered[$handle];
                $category = $this->get_script_category($script->src);

                $detected[] = array(
                    'handle' => $handle,
                    'src' => $script->src,
                    'category' => $category,
                );
            }
        }

        return $detected;
    }
}