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

        // Start output buffering with callback to inject blocker
        ob_start(array($this, 'inject_cookie_blocker'));
    }

    /**
     * Inject cookie blocker at the beginning of HTML output
     */
    public function inject_cookie_blocker($buffer) {
        // Only process HTML responses
        if (strpos($buffer, '<!DOCTYPE') === false && strpos($buffer, '<html') === false) {
            return $buffer;
        }

        if (!$this->has_valid_api_key()) {
            return $buffer;
        }

        // Build the cookie blocker script
        $blocker = "\n<!-- CookieNod Immediate Blocker -->\n<script>\n";
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
     * End output buffering
     */
    public function end_output_buffering() {
        if (ob_get_level()) {
            ob_end_flush();
        }
    }

    /**
     * Output CookieNod script FIRST in head before any other scripts
     * Priority 0 ensures it loads before analytics, tracking, etc.
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

        // Combine all inline scripts into one block
        $inline_js = "\n<!-- CookieNod Scripts -->\n<script>\n";
        $inline_js .= "(function(){\n";
        $inline_js .= "  'use strict';\n\n";

        // Data-consent handler
        $inline_js .= "  // Data-consent handler\n";
        $inline_js .= "  window.__cookienodShouldLoad=function(cat){\n";
        $inline_js .= "    var h=localStorage.getItem('cs_consent_given')==='true';\n";
        $inline_js .= "    if(!h)return cat.indexOf('necessary')>-1;\n";
        $inline_js .= "    var p=JSON.parse(localStorage.getItem('cs_cookie_prefs')||'{}');\n";
        $inline_js .= "    return cat.split(',').some(function(c){\n";
        $inline_js .= "      c=c.trim();if(c==='necessary')return true;\n";
        $inline_js .= "      return p[c]===true;\n";
        $inline_js .= "    });\n";
        $inline_js .= "  };\n";
        $inline_js .= "  document.querySelectorAll('script[data-consent]').forEach(function(sc){\n";
        $inline_js .= "    var cat=sc.getAttribute('data-consent'),src=sc.getAttribute('data-src')||sc.src;\n";
        $inline_js .= "    if(src&&!window.__cookienodShouldLoad(cat))sc.remove();\n";
        $inline_js .= "  });\n\n";

        // Excluded scripts handler
        $excluded_scripts = $this->options['excluded_scripts'] ?? '';
        if (!empty($excluded_scripts)) {
            $patterns = array_filter(array_map('trim', explode("\n", $excluded_scripts)));
            if (!empty($patterns)) {
                $inline_js .= "  // Excluded scripts\n";
                $inline_js .= "  var excludedPat=[";
                $first = true;
                foreach ($patterns as $pattern) {
                    if (empty($pattern)) continue;
                    $escaped = str_replace("'", "\\'", $pattern);
                    if (!$first) $inline_js .= ",";
                    $inline_js .= "'" . $escaped . "'";
                    $first = false;
                }
                $inline_js .= "];\n";
                $inline_js .= "  document.querySelectorAll('script[src]').forEach(function(sc){\n";
                $inline_js .= "    excludedPat.forEach(function(p){\n";
                $inline_js .= "      if(new RegExp(p.replace(/\\*/g,'.*').replace(/\\//g,'\\\\/'),'i').test(sc.src))sc.remove();\n";
                $inline_js .= "    });\n";
                $inline_js .= "  });\n\n";
            }
        }

        // Consent logging and cookie detection combined
        $inline_js .= "  // Consent logging & detection\n";
        $inline_js .= "  var aj='" . $ajax_url . "',no='" . $nonce . "';\n";
        $inline_js .= "  var lk='cs_consent_logged_v2',dk='cs_cookies_detected';\n";
        $inline_js .= "  var sid=Math.random().toString(36).substring(2)+Date.now().toString(36);\n";
        $inline_js .= "  var rs=false,de=sessionStorage.getItem(dk)==='true';\n";

        // Consent logging function - with deduplication and pending check
        $inline_js .= "  var lp='cs_last_logged',ld='cs_log_pending';\n";
        $inline_js .= "  function logC(f,rt){\n";
        $inline_js .= "    if(rs||sessionStorage.getItem(ld))return;\n";
        $inline_js .= "    rt=rt||0;if(rt>10)return;\n";
        $inline_js .= "    var hc=localStorage.getItem('cs_consent_given')==='true';\n";
        $inline_js .= "    if(!hc&&!f)return;\n";
        $inline_js .= "    var pr={};try{var r=localStorage.getItem('cs_cookie_prefs');if(r)pr=JSON.parse(r);}catch(e){}\n";
        $inline_js .= "    if(Object.keys(pr).length===0){if(rt<5){setTimeout(function(){logC(f,rt+1);},500);return;}\n";
        $inline_js .= "    pr={necessary:false,functional:false,analytics:false,marketing:false};}\n";
        $inline_js .= "    var ph=btoa(JSON.stringify(pr));\n";
        $inline_js .= "    if(sessionStorage.getItem(lp)===ph)return;\n";
        $inline_js .= "    sessionStorage.setItem(ld,ph);rs=true;var d=new FormData();\n";
        $inline_js .= "    d.append('action','cookienod_log_consent');d.append('nonce',no);\n";
        $inline_js .= "    d.append('session_id',sid);d.append('preferences',JSON.stringify(pr));\n";
        $inline_js .= "    fetch(aj,{method:'POST',body:d,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(r){\n";
        $inline_js .= "      sessionStorage.setItem(lp,ph);sessionStorage.removeItem(ld);rs=false;\n";
        $inline_js .= "    }).catch(function(){sessionStorage.removeItem(ld);rs=false;});\n";
        $inline_js .= "  }\n";

        // Cookie detection function
        $inline_js .= "  function detC(){\n";
        $inline_js .= "    if(de||!document.cookie)return;\n";
        $inline_js .= "    var co=[],ca=document.cookie.split(';');\n";
        $inline_js .= "    for(var i=0;i<ca.length;i++){var c=ca[i].trim(),eq=c.indexOf('=');\n";
        $inline_js .= "    co.push({name:eq>-1?c.substr(0,eq):c,value:eq>-1?c.substr(eq+1).substring(0,50):'',type:'http',source:window.location.hostname});}\n";
        $inline_js .= "    var d=new FormData();d.append('action','cookienod_detect_cookies');d.append('nonce',no);\n";
        $inline_js .= "    d.append('cookies',JSON.stringify(co));\n";
        $inline_js .= "    fetch(aj,{method:'POST',body:d,credentials:'same-origin'}).then(function(r){return r.json();}).then(function(r){\n";
        $inline_js .= "      if(r.success){de=true;sessionStorage.setItem(dk,'true');}\n";
        $inline_js .= "    });\n";
        $inline_js .= "  }\n";

        // Event listeners
        $inline_js .= "  document.addEventListener('DOMContentLoaded',function(){\n";
        $inline_js .= "    if(localStorage.getItem('cs_consent_given')==='true')logC(true);\n";
        $inline_js .= "    window.addEventListener('cookiePreferencesChanged',function(e){logC(true);});\n";
        $inline_js .= "    setTimeout(function(){logC(true);},5000);\n";
        $inline_js .= "    setTimeout(detC,2000);\n";
        $inline_js .= "  });\n";

        $inline_js .= "})();\n</script>\n";

        // Center modal overlay (if needed)
        if ($position === 'center') {
            $inline_js .= "<div id='cs-modal-overlay' style='position:fixed;inset:0;background:rgba(0,0,0,0.5);z-index:999998;display:none;'></div>\n";
            $inline_js .= "<script>(function(){var o=document.getElementById('cs-modal-overlay');\n";
            $inline_js .= "function h(){if(o)o.style.display='none'}function s(){if(o)o.style.display='block'}\n";
            $inline_js .= "new MutationObserver(function(){if(document.getElementById('cs-consent-banner'))s();}).observe(document.body,{childList:true,subtree:true});\n";
            $inline_js .= "new MutationObserver(function(m){m.forEach(function(x){if(x.removedNodes)for(var i=0;i<x.removedNodes.length;i++)if(x.removedNodes[i].id==='cs-consent-banner'){h();return;}});}).observe(document.body,{childList:true});\n";
            $inline_js .= "if(o)o.onclick=function(e){if(e.target===o){var b=document.getElementById('cs-consent-banner');if(b)b.remove();h();}};})();</script>\n";
        }

        // Theme-specific base CSS to override external JS hardcoded styles
        $theme_css = '';
        if ($theme === 'dark') {
            $theme_css = "
<!-- CookieNod Theme CSS -->
<style type='text/css'>
#cs-consent-banner,#cs-consent-banner div{background:#1f2937 !important;color:#f9fafb !important;border-color:#374151 !important;}
#cs-consent-banner h3,#cs-consent-banner p{color:#f9fafb !important;}
#cs-consent-banner button{background:#374151 !important;color:#f9fafb !important;border-color:#4b5563 !important;}
#cs-consent-banner button[id*='accept'],#cs-consent-banner button.cs-btn-primary{background:#2563eb !important;color:#fff !important;}
</style>
";
        }

        // Build data-texts attribute as JSON
        $texts_json = json_encode($texts);
        $texts_attr = htmlspecialchars($texts_json, ENT_QUOTES, 'UTF-8');

        // Main external script
        $inline_js .= $theme_css;
        $inline_js .= "<!-- CookieNod Main -->\n";
        $inline_js .= "<script src='https://cookienod.com/cookienod.min.js?v=" . COOKIENOD_VERSION . "' data-license-key='{$api_key_escaped}' data-block-mode='{$block_mode}' data-banner-position='{$position}' data-banner-theme='{$theme}' data-texts='{$texts_attr}'></script>\n";
        $inline_js .= "<!-- End CookieNod -->\n";

        echo $inline_js;
    }

    /**
     * Render consent banner
     */
    public function render_banner() {
        if (is_admin() || wp_is_json_request()) {
            return;
        }

        // The banner is handled by the JS
        do_action('cookienod_before_banner');
        do_action('cookienod_after_banner');
    }
}
