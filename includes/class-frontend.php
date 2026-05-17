<?php
/**
 * CookieNod Frontend Class
 * Handles frontend output, script injection, and banner rendering
 *
 * @package Cookienod
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class CookieNod_Frontend
 * Frontend functionality
 */
class CookieNod_Frontend {

    /**
     * Plugin options
     *
     * @var array
     */
    private $options;

    /**
     * Output buffer level when we started buffering
     *
     * @var int
     */
    private $buffer_level = 0;

    /**
     * Check whether frontend consent features should run.
     * Requires a configured API key.
     *
     * @return bool
     */
    private function has_valid_api_key() {
        $api_key = $this->options['api_key'] ?? '';
        return !empty($api_key);
    }

    /**
     * Constructor
     *
     * @param array $options Plugin options.
     */
    public function __construct($options = array()) {
        $this->options = $options;
        $this->init();
    }

    /**
     * Initialize
     */
    private function init() {
        // Frontend hooks - output_script is called by CookieNod_Core
        add_action('template_redirect', array($this, 'start_output_buffering'), 0);
        add_action('wp_footer', array($this, 'render_banner'), 1);
        add_action('shutdown', array($this, 'end_output_buffering'), 0);
    }

    /**
     * Start output buffering to inject blocker before any output
     */
    public function start_output_buffering() {
        if (is_admin() || wp_is_json_request()) {
            return;
        }

        if (!$this->has_valid_api_key()) {
            return;
        }

        // Start output buffering with callback to inject blocker and track the level
        ob_start(array($this, 'inject_cookie_blocker'));
        $this->buffer_level = ob_get_level();
    }

    /**
     * Inject cookie blocker at the beginning of HTML output
     * Note: This is a fallback for early cookie blocking.
     * The proper WordPress way is done via wp_add_inline_script in output_script().
     */
    public function inject_cookie_blocker($buffer) {
        // Only process HTML responses
        if (strpos($buffer, '<!DOCTYPE') === false && strpos($buffer, '<html') === false) {
            return $buffer;
        }

        if (!$this->has_valid_api_key()) {
            return $buffer;
        }

        // Build the cookie blocker script content
        $blocker = '<script>' . "\n";
        $blocker .= "(function(){\n";
        $blocker .= "  'use strict';\n";
        $blocker .= "  var hasCons = localStorage.getItem('cs_consent_given') === 'true';\n";
        $blocker .= "  var prefs = {}; try { prefs = JSON.parse(localStorage.getItem('cs_cookie_prefs')||'{}'); } catch(e){}\n";
        $blocker .= "  var nec = /^(PHPSESSID|wordpress_|wp-|_csrf|csrftoken|session|auth|login|security|__cf|cf_)/i;\n";
        $blocker .= "  var ana = /^(_ga|_gid|_gat|_utm|analytics|__hst|_hj|sbjs)/i;\n";
        $blocker .= "  var mkt = /^(_fbp|fr\\$|IDE\\$|NID\\$|ads|marketing)/i;\n";
        $blocker .= "  try {\n";
        $blocker .= "    var d = Object.getOwnPropertyDescriptor(Document.prototype,'cookie');\n";
        $blocker .= "    if(d){\n";
        $blocker .= "      Object.defineProperty(document,'cookie',{get:function(){return d.get.call(document);},set:function(v){\n";
        $blocker .= "        var n=v.split('=')[0].trim(); var a=true;\n";
        $blocker .= "        if(nec.test(n))a=true;\n";
        $blocker .= "        else if(hasCons){if(ana.test(n))a=prefs.analytics;if(mkt.test(n))a=prefs.marketing;}\n";
        $blocker .= "        else{if(ana.test(n)||mkt.test(n))a=false;}\n";
        $blocker .= "        if(a){d.set.call(document,v);}\n";
        $blocker .= "      },configurable:true});\n";
        $blocker .= "    }\n";
        $blocker .= "  }catch(e){}\n";
        $blocker .= "})();\n";
        $blocker .= "</script>\n";

        // Inject right after <head> tag
        $buffer = preg_replace('/(<head[^>]*>)/i', '$1' . $blocker, $buffer, 1);

        return $buffer;
    }

    /**
     * End output buffering - only closes our own buffer
     */
    public function end_output_buffering() {
        // Only close if we have a tracked buffer level and it still exists
        if ($this->buffer_level > 0 && ob_get_level() >= $this->buffer_level) {
            ob_end_flush();
            $this->buffer_level = 0; // Reset to prevent double-closing
        }
    }

    /**
     * Output CookieNod script FIRST in head before any other scripts
     * Uses WordPress wp_enqueue_script and wp_add_inline_script functions
     */
    public function output_script() {
        if (!$this->has_valid_api_key()) {
            return;
        }

        $api_key = $this->options['api_key'] ?? '';
        $block_mode = esc_attr($this->options['block_mode'] ?? 'auto');
        $position = esc_attr($this->options['banner_position'] ?? 'bottom');
        $theme = esc_attr($this->options['banner_theme'] ?? 'light');
        $api_key_escaped = esc_attr($api_key);

        // Text translations
        $texts = array(
            'title' => esc_attr($this->options['banner_title'] ?? __('Cookie Preferences', 'cookienod')),
            'description' => esc_attr($this->options['banner_description'] ?? __('We use cookies to enhance your experience. Choose your preferences below.', 'cookienod')),
            'accept' => esc_attr($this->options['btn_accept'] ?? __('Accept All', 'cookienod')),
            'reject' => esc_attr($this->options['btn_reject'] ?? __('Reject', 'cookienod')),
            'customize' => esc_attr($this->options['btn_customize'] ?? __('Customize', 'cookienod')),
            'save' => esc_attr($this->options['btn_save'] ?? __('Save Preferences', 'cookienod')),
            'settings_title' => esc_attr($this->options['settings_title'] ?? __('Cookie Settings', 'cookienod')),
            'category_necessary' => esc_attr($this->options['category_necessary'] ?? __('Necessary', 'cookienod')),
            'category_functional' => esc_attr($this->options['category_functional'] ?? __('Functional', 'cookienod')),
            'category_analytics' => esc_attr($this->options['category_analytics'] ?? __('Analytics', 'cookienod')),
            'category_marketing' => esc_attr($this->options['category_marketing'] ?? __('Marketing', 'cookienod')),
            'label_required' => esc_attr($this->options['label_required'] ?? __('Required', 'cookienod')),
        );
        $ajax_url = admin_url('admin-ajax.php');
        $nonce = wp_create_nonce('cookienod_wp_nonce');

        // Build data-texts attribute as JSON
        $texts_json = wp_json_encode($texts);
        $texts_attr = htmlspecialchars($texts_json, ENT_QUOTES, 'UTF-8');

        // Register and enqueue the main cookienod script
        wp_register_script(
            'cookienod-main',
            plugins_url('assets/js/cookienod.min.js', COOKIENOD_PLUGIN_FILE),
            array(),
            COOKIENOD_VERSION,
            false // Load in head
        );

        // Build attributes string - include A/B testing variant if available
        $attrs = sprintf(
            ' data-license-key="%s" data-block-mode="%s" data-banner-position="%s" data-banner-theme="%s" data-texts="%s"',
            esc_attr($api_key_escaped),
            esc_attr($block_mode),
            esc_attr($position),
            esc_attr($theme),
            esc_attr($texts_attr)
        );

        // Apply custom script attributes filter (for A/B testing, Google Consent Mode, etc.)
        $attrs = apply_filters('cookienod_script_attributes', $attrs);

        add_filter(
            'script_loader_tag',
            function ($tag, $handle) use ($attrs) {
                if ($handle !== 'cookienod-main') {
                    return $tag;
                }
                return str_replace('<script ', '<script' . $attrs . ' ', $tag);
            },
            10,
            2
        );

        // Build inline script for data-consent handler, excluded scripts, and consent logging
        $inline_script = "(function(){\n";
        $inline_script .= "  'use strict';\n\n";

        // Data-consent handler
        $inline_script .= "  // Data-consent handler\n";
        $inline_script .= "  window.__cookienodShouldLoad=function(cat){\n";
        $inline_script .= "    var h=localStorage.getItem('cs_consent_given')==='true';\n";
        $inline_script .= "    if(!h)return cat.indexOf('necessary')>-1;\n";
        $inline_script .= "    var p=JSON.parse(localStorage.getItem('cs_cookie_prefs')||'{}');\n";
        $inline_script .= "    return cat.split(',').some(function(c){\n";
        $inline_script .= "      c=c.trim();if(c==='necessary')return true;\n";
        $inline_script .= "      return p[c]===true;\n";
        $inline_script .= "    });\n";
        $inline_script .= "  };\n";
        $inline_script .= "  document.querySelectorAll('script[data-consent]').forEach(function(sc){\n";
        $inline_script .= "    var cat=sc.getAttribute('data-consent'),src=sc.getAttribute('data-src')||sc.src;\n";
        $inline_script .= "    if(src&&!window.__cookienodShouldLoad(cat))sc.remove();\n";
        $inline_script .= "  });\n\n";

        // Excluded scripts handler
        $excluded_scripts = $this->options['excluded_scripts'] ?? '';
        if (!empty($excluded_scripts)) {
            $patterns = array_filter(array_map('trim', explode("\n", $excluded_scripts)));
            if (!empty($patterns)) {
                $inline_script .= "  // Excluded scripts\n";
                $inline_script .= "  var excludedPat=[";
                $first = true;
                foreach ($patterns as $pattern) {
                    if (empty($pattern)) continue;
                    if (!$first) $inline_script .= ",";
                    $inline_script .= wp_json_encode($pattern);
                    $first = false;
                }
                $inline_script .= "];\n";
                $inline_script .= "  document.querySelectorAll('script[src]').forEach(function(sc){\n";
                $inline_script .= "    excludedPat.forEach(function(p){\n";
                $inline_script .= "      if(new RegExp(p.replace(/\\*/g,'.*').replace(/\\//g,'\\\\/'),'i').test(sc.src))sc.remove();\n";
                $inline_script .= "    });\n";
                $inline_script .= "  });\n\n";
            }
        }

        // Consent logging and cookie detection combined
        $inline_script .= "  // Consent logging & detection\n";
        $inline_script .= "  var aj=" . wp_json_encode($ajax_url) . ",no=" . wp_json_encode($nonce) . ";\n";
        $inline_script .= "  var lk='cs_consent_logged_v2',dk='cs_cookies_detected';\n";
        $inline_script .= "  var sid=Math.random().toString(36).substring(2)+Date.now().toString(36);\n";
        $inline_script .= "  var rs=false,de=sessionStorage.getItem(dk)==='true';\n";

        // Consent logging function - with deduplication and pending check
        $inline_script .= "  var lp='cs_last_logged',ld='cs_log_pending';\n";
        $inline_script .= "  function logC(f,rt){\n";
        $inline_script .= "    if(rs||sessionStorage.getItem(ld))return;\n";
        $inline_script .= "    rt=rt||0;if(rt>10)return;\n";
        $inline_script .= "    var hc=localStorage.getItem('cs_consent_given')==='true';\n";
        $inline_script .= "    if(!hc&&!f)return;\n";
        $inline_script .= "    var pr={};try{var r=localStorage.getItem('cs_cookie_prefs');if(r)pr=JSON.parse(r);}catch(e){}\n";
        $inline_script .= "    if(Object.keys(pr).length===0){if(rt<5){setTimeout(function(){logC(f,rt+1);},500);return;}\n";
        $inline_script .= "    pr={necessary:false,functional:false,analytics:false,marketing:false};}\n";
        $inline_script .= "    var ph=btoa(JSON.stringify(pr));\n";
        $inline_script .= "    if(sessionStorage.getItem(lp)===ph)return;\n";
        $inline_script .= "    sessionStorage.setItem(ld,ph);rs=true;var d=new FormData();\n";
        $inline_script .= "    d.append('action','cookienod_log_consent');d.append('nonce',no);\n";
        $inline_script .= "    d.append('session_id',sid);d.append('preferences',JSON.stringify(pr));\n";
        $inline_script .= "    fetch(aj,{method:'POST',body:d,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(r){\n";
        $inline_script .= "      sessionStorage.setItem(lp,ph);sessionStorage.removeItem(ld);rs=false;\n";
        $inline_script .= "    }).catch(function(){sessionStorage.removeItem(ld);rs=false;});\n";
        $inline_script .= "  }\n";

        // Cookie detection function
        $inline_script .= "  function detC(){\n";
        $inline_script .= "    if(de||!document.cookie)return;\n";
        $inline_script .= "    var co=[],ca=document.cookie.split(';');\n";
        $inline_script .= "    for(var i=0;i<ca.length;i++){var c=ca[i].trim(),eq=c.indexOf('=');\n";
        $inline_script .= "    co.push({name:eq>-1?c.substr(0,eq):c,value:eq>-1?c.substr(eq+1).substring(0,50):'',type:'http',source:window.location.hostname});}\n";
        $inline_script .= "    var d=new FormData();d.append('action','cookienod_detect_cookies');d.append('nonce',no);\n";
        $inline_script .= "    d.append('cookies',JSON.stringify(co));\n";
        $inline_script .= "    fetch(aj,{method:'POST',body:d,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(r){\n";
        $inline_script .= "      if(r.success){de=true;sessionStorage.setItem(dk,'true');}\n";
        $inline_script .= "    });\n";
        $inline_script .= "  }\n";

        // Event listeners
        $inline_script .= "  document.addEventListener('DOMContentLoaded',function(){\n";
        $inline_script .= "    if(localStorage.getItem('cs_consent_given')==='true')logC(true);\n";
        $inline_script .= "    window.addEventListener('cookiePreferencesChanged',function(e){logC(true);});\n";
        $inline_script .= "    setTimeout(function(){logC(true);},5000);\n";
        $inline_script .= "    setTimeout(detC,2000);\n";
        $inline_script .= "  });\n";

        // Center modal overlay (if needed)
        if ($position === 'center') {
            $inline_script .= "\n  // Center modal overlay handler\n";
            $inline_script .= "  (function(){\n";
            $inline_script .= "    var overlay = document.getElementById('cs-modal-overlay');\n";
            $inline_script .= "    function hideOverlay(){if(overlay)overlay.style.display='none';}\n";
            $inline_script .= "    function showOverlay(){if(overlay)overlay.style.display='block';}\n";
            $inline_script .= "    new MutationObserver(function(){if(document.getElementById('cs-consent-banner'))showOverlay();}).observe(document.body,{childList:true,subtree:true});\n";
            $inline_script .= "    new MutationObserver(function(m){m.forEach(function(x){if(x.removedNodes)for(var i=0;i<x.removedNodes.length;i++)if(x.removedNodes[i].id==='cs-consent-banner'){hideOverlay();return;}});}).observe(document.body,{childList:true});\n";
            $inline_script .= "    if(overlay)overlay.onclick=function(e){if(e.target===overlay){var b=document.getElementById('cs-consent-banner');if(b)b.remove();hideOverlay();}};\n";
            $inline_script .= "  })();\n";
        }

        // Elementor widget consent sync (auto mode)
        $inline_script .= "\n  // Elementor widget consent sync\n";
        $inline_script .= "  (function(){\n";
        $inline_script .= "    function updateElementorWidgets(){\n";
        $inline_script .= "      var prefs={necessary:true,functional:false,analytics:false,marketing:false};\n";
        $inline_script .= "      try{var p=localStorage.getItem('cs_cookie_prefs');if(p)prefs=JSON.parse(p);}catch(e){}\n";
        $inline_script .= "      ['marketing','analytics','functional'].forEach(function(cat){\n";
        $inline_script .= "        document.querySelectorAll('.elementor-widget.cookienod-require-consent-yes').forEach(function(w){\n";
        $inline_script .= "          var wc=w.getAttribute('data-consent-category')||'marketing';\n";
        $inline_script .= "          if(wc===cat){w.classList.toggle('cookienod-consent-given-'+cat,prefs[cat]===true);}\n";
        $inline_script .= "        });\n";
        $inline_script .= "      });\n";
        $inline_script .= "    }\n";
        $inline_script .= "    document.addEventListener('DOMContentLoaded',function(){updateElementorWidgets();});\n";
        $inline_script .= "    window.addEventListener('cookiePreferencesChanged',function(){setTimeout(updateElementorWidgets,50);});\n";
        $inline_script .= "    if(window.elementorFrontend){window.elementorFrontend.hooks.addAction('frontend/element_ready/widget',updateElementorWidgets);}\n";
        $inline_script .= "  })();\n";

        $inline_script .= "})();\n";

        // Enqueue main script with inline data
        wp_enqueue_script('cookienod-main');
        wp_add_inline_script('cookienod-main', $inline_script, 'after');

        // Theme-specific CSS
        if ($theme === 'dark') {
            $theme_css = ".cs-banner.cs-dark{background:#1f2937;color:#f9fafb;border-color:#374151;}\n";
            $theme_css .= ".cs-banner.cs-dark .cs-banner-title,.cs-banner.cs-dark .cs-banner-description{color:#f9fafb;}\n";
            $theme_css .= ".cs-banner.cs-dark #cs-reject-btn,.cs-banner.cs-dark #cs-customize-btn{background:#374151;color:#f9fafb;border-color:#4b5563;}\n";
            $theme_css .= ".cs-banner.cs-dark #cs-accept-btn{background:#2563eb;color:#fff;border-color:#2563eb;}";

            wp_register_style('cookienod-theme', false, [], COOKIENOD_VERSION);
            wp_enqueue_style('cookienod-theme');
            wp_add_inline_style('cookienod-theme', $theme_css);
        }

        // Center modal overlay HTML
        if ($position === 'center') {
            echo "<div id='cs-modal-overlay' style='position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999998;display:none;'></div>\n";
        }
    }

    /**
     * Render consent banner
     */
    public function render_banner() {
        if (is_admin() || wp_is_json_request()) {
            return;
        }

        // Allow disabling banner via filter (e.g., Elementor editor)
        if (apply_filters('cookienod_disable_banner', false)) {
            return;
        }

        // The banner is handled by the JS
        do_action('cookienod_before_banner');
        do_action('cookienod_after_banner');
    }
}
