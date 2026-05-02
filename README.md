# CookieNod - Cookie Consent & Scanner

**GDPR/CCPA Compliant Cookie Consent Management**

*Automated Scanning · Intelligent Blocking · Beautiful Banners*

---

## Table of Contents

1. [Description](#description)
2. [Installation](#installation)
3. [Frequently Asked Questions](#frequently-asked-questions)
4. [External Services](#external-services)
5. [Privacy Policy](#privacy-policy)
6. [Source Code and Build Process](#source-code-and-build-process)
7. [Credits](#credits)

---

## Description

CookieNod is a powerful cookie consent and management solution for WordPress, designed to help you comply with GDPR, CCPA, LGPD, and 20+ other privacy regulations worldwide.

### Key Features

- **Multi-Region Compliance**: Supports GDPR (EU), CCPA (California), LGPD (Brazil), POPIA (South Africa), PIPEDA (Canada), and 15+ other regulations
- **JavaScript Cookie Scanning**: Real browser scanner detects and categorizes cookies as visitors browse
- **Google Consent Mode v2**: Full integration with Google Analytics 4 and Google Tag Manager
- **Four Blocking Modes**:
  - **Auto** (Recommended) - JavaScript handles cookie blocking, clean UI
  - **Manual Consent Attributes** - Use data-consent attributes on scripts for precise control
  - **Silent Blocking** - Server-side blocking without visible placeholders
  - **Blocking with Placeholders** - Server-side with visible placeholder boxes
- **Script Exclusions** - Exclude specific scripts using wildcards (`*google-analytics.com*`)
- **Data-Consent Attributes** - Mark scripts with `data-consent="analytics,marketing"` for multi-category control
- **Customizable Consent Banner**: Choose between banner (top/bottom) or modal display with light/dark themes
- **Custom CSS Editor**: Built-in CSS editor with live preview and theme templates
- **A/B Testing**: Test different banner designs and automatically optimize conversion rates
- **Consent Logging**: Track user consent choices for compliance auditing
- **Cookie Policy Generator**: Automatically generate and update cookie policy pages
- **WooCommerce Integration**: Add consent checkboxes to checkout, block tracking until consent
- **External Service Integration**: Connects to the CookieNod service for key validation and site configuration

### Cookie Categories

- **Necessary**: Essential cookies that cannot be disabled
- **Functional**: Preferences, language, and personalization cookies
- **Analytics**: Google Analytics, Matomo, Hotjar, and other analytics tools
- **Marketing**: Facebook Pixel, Google Ads, LinkedIn, and advertising cookies

### Blocking Modes Explained

**Auto (Recommended)**
- Shows the consent banner
- JavaScript SDK handles cookie blocking
- No visible placeholders or layout changes
- Works with all caching plugins
- Best for most websites

**Silent Blocking**
- Server blocks scripts server-side
- No visible placeholders (hidden in HTML comments)
- JavaScript loads scripts after user gives consent
- Best balance of protection and UX

**Blocking with Placeholders (Legacy)**
- Server blocks scripts
- Shows visible placeholder boxes with unlock buttons
- Use only if you need visible indicators

### A/B Testing

Optimize your consent banner with built-in A/B testing:
- Create multiple banner variants
- Split traffic between designs
- Track accept/reject rates
- Automatic winner selection
- Improve consent rates over time

### WooCommerce Features

- Marketing consent checkbox at checkout
- Analytics consent option
- Block cart/tracking cookies until functional consent
- Store consent with order data
- "Do Not Sell My Personal Information" link for CCPA

### Supported Regulations

- GDPR (European Union, EEA, UK)
- CCPA/CPRA (California, USA)
- LGPD (Brazil)
- POPIA (South Africa)
- PIPEDA (Canada)
- Australia Privacy Act
- Nigeria NDPR
- Singapore PDPA
- Thailand PDPA
- Japan APPI
- South Korea PIPA
- Indonesia PDP Law
- Vietnam PDPD
- UAE PDPL
- Qatar PDPL
- Saudi Arabia PDPL
- Egypt PDPL
- Kenya DPA
- Ghana DPA
- Turkey KVKK
- Russia FZ-152
- China PIPL
- India DPDP Act

---

## Installation

### Method 1: WordPress Admin (Recommended)

1. Download the plugin ZIP file
2. Go to **WordPress Admin > Plugins > Add New > Upload Plugin**
3. Choose the downloaded ZIP file and click **Install Now**
4. Click **Activate Plugin**

### Method 2: FTP

1. Extract the plugin ZIP file
2. Upload the `cookienod` folder to `/wp-content/plugins/`
3. Go to **WordPress Admin > Plugins** and activate **CookieNod**

### Configuration

1. Get your API Key from [CookieNod Dashboard](https://cookienod.com/dashboard)
2. Go to **CookieNod > Settings** in WordPress
3. Paste your API Key and click **Verify**
4. Choose your preferred **Blocking Mode**
5. Save changes

---

## Frequently Asked Questions

### Do I need an API key?

Yes, an API key is required. You can get an API key at https://cookienod.com.

### Does this work with Google Tag Manager?

Yes! CookieNod fully supports Google Consent Mode v2. Enable it in settings to integrate with GTM and Google Analytics 4.

### Can I customize the banner appearance?

Yes, you can choose between light/dark themes and banner position (top, bottom, or center modal). Advanced CSS customization is also supported.

### What are the blocking modes?

- **Banner Only** - JavaScript handles all cookie blocking (recommended)
- **Manual Consent Attributes** - Use data-consent attributes on script tags for precise control
- **Silent Blocking** - Server blocks scripts without visible placeholders, JS loads them after consent
- **Blocking with Placeholders** - Server blocks with visible placeholder boxes (legacy mode)

### Does this block cookies automatically?

In "Auto" mode, the plugin intercepts and blocks third-party cookies and scripts until the user gives consent. In "Manual" mode, it only displays the consent banner.

### How do I use data-consent attributes?

Add the `data-consent` attribute to your script tags:

```html
<!-- Single category -->
<script src="analytics.js" data-consent="analytics"></script>

<!-- Multiple categories (loads if ANY consent given) -->
<script src="tracking.js" data-consent="analytics,marketing"></script>

<!-- Always loads -->
<script src="jquery.js" data-consent="necessary"></script>
```

Valid categories: `necessary`, `functional`, `analytics`, `marketing`

### How do I exclude scripts?

In Settings > Excluded Scripts, enter patterns (one per line):

```
*google-analytics.com*
*googletagmanager.com*
*facebook.com/tr
*hotjar.com
analytics.js
```

Use `*` as wildcards to match any part of the script URL.

### Is consent logging available?

Yes, all user consent choices are logged in your WordPress database for compliance auditing. You can export these logs as CSV.

### Can I use this without the external service?

No. The plugin requires the CookieNod service for API key validation and site configuration.

### Does this work with Elementor?

Yes! CookieNod is fully compatible with Elementor and Elementor Pro. You can:
- Add consent controls to any Elementor widget
- Block widgets until user gives consent (marketing/analytics)
- Integrate consent tracking with Elementor forms
- Show/hide popups based on consent status

### Does this work with Gravity Forms?

Yes! CookieNod integrates with Gravity Forms to:
- Track consent status with form submissions
- Require specific consent categories before form submission
- Log consent data with entries for compliance

### Does this work with Contact Form 7?

Yes! CookieNod works with Contact Form 7 via:
- A special `[cookienod_consent]` form tag
- Automatic consent data attachment to submissions
- Consent summary display option

### The banner is not showing, what should I do?

1. Verify your API key is entered correctly
2. Check that your site URL in CookieNod matches your WordPress URL
3. Clear any WordPress cache
4. Check browser console for errors

### Will it work with my caching plugin?

Yes! The "Banner Only" mode works seamlessly with caching plugins like WP Rocket, W3 Total Cache, and LiteSpeed Cache.

---

## External Services

This plugin connects to the CookieNod external service to provide cookie consent management functionality. An API key is required to use these services.

### CookieNod API and Frontend Script

The plugin requires the CookieNod service (provided by CookieNod Team) to function. This service is used for:

- **API Key Validation**: Verifying your license key is valid
- **Site Configuration**: Obtaining your site's consent banner settings
- **Frontend Banner Rendering**: Loading the JavaScript that displays and manages the consent banner

### What data is sent

- Your **API Key** - sent when verifying the key and loading site configuration
- Your **Site URL** - sent to validate the API key is being used on the correct domain
- **Current page URL** - sent when loading the frontend script to render the banner

### When data is sent

- API key validation: When you enter/verify your API key in settings
- Site configuration: When the plugin loads your banner settings
- Frontend script: On every page load for visitors (only if a valid API key is configured)

### Service provider

CookieNod Team

- **Terms of Service**: https://cookienod.com/terms
- **Privacy Policy**: https://cookienod.com/privacy

### Google Services (Optional)

If you enable Google Consent Mode v2, the plugin integrates with Google Analytics and Google Tag Manager. This is optional and disabled by default.

- **Google Analytics Terms**: https://marketingplatform.google.com/about/analytics/terms/
- **Google Privacy Policy**: https://policies.google.com/privacy

---

## Privacy Policy

CookieNod processes the following data:

- **API Key**: Stored in WordPress and sent to the CookieNod API for key validation
- **Site URL**: Sent to the CookieNod API to validate the key for the current site
- **Cookie Data**: Cookie names and categories detected on your site and stored in your WordPress database
- **Consent Data**: User consent preferences stored locally in the visitor browser and, if consent logging is enabled, logged in your WordPress database
- **IP Addresses**: Logged in your WordPress database for consent audit records when consent logging is enabled

The plugin loads a remote JavaScript file from `https://cookienod.com/cookienod.min.js` after a valid API key is verified. API key validation and site configuration requests are sent to `https://api.cookienod.com`. Consent logging and cookie detection requests are sent to your own site through WordPress `admin-ajax.php`.

---

## Source Code and Build Process

This plugin follows WordPress guidelines for human-readable code. All JavaScript and CSS files are distributed in their original, unminified source form.

### Source Code Repository

https://github.com/cookienod/cookienod-wordpress

### Build Process

No build process is required. The plugin uses vanilla JavaScript and CSS files that are human-readable and can be studied, modified, and extended directly. All inline scripts and styles are output using WordPress's `wp_enqueue_script()`, `wp_enqueue_style()`, `wp_add_inline_script()`, and `wp_add_inline_style()` functions.

### File Structure

**JavaScript:**
- `assets/js/admin.js` - Admin JavaScript (human-readable source, no build)

**CSS:**
- `assets/css/admin.css` - Admin styles (human-readable source, no build)
- `assets/css/banner-preview-source.css` - Banner preview CSS for admin JS preview function
- `assets/css/banner-preview.css` - CSS editor iframe preview styles
- `assets/css/custom-css-editor.css` - Custom CSS editor admin page styles
- `assets/css/policy-generator.css` - Policy generator page styles
- `assets/css/script-blocker.css` - Script blocker placeholder styles

### Developer Notes

If you modify this plugin, simply edit the source files directly. No compilation step is needed.

---

## Changelog

### Version 1.0.0

- Initial release
- Multi-region compliance support (20+ regulations)
- Google Consent Mode v2 integration
- Four blocking modes: Banner Only, Manual Consent Attributes, Silent Blocking, and Placeholders
- JavaScript-based cookie scanning and categorization
- Script blocking with data-consent attributes
- Consent logging with CSV export
- Custom CSS Editor with live preview and style presets (Minimal, Rounded, Glassmorphism, Brutalist, Elegant)
- A/B testing for banner optimization
- Cookie Policy Generator with multiple templates (GDPR, CCPA, Combined, Simple)
- WooCommerce integration with consent checkboxes
- Elementor, Gravity Forms, and Contact Form 7 compatibility
- WordPress directory compliance:
  - All scripts use `wp_enqueue_script()` and `wp_add_inline_script()`
  - All styles use `wp_enqueue_style()` and `wp_add_inline_style()`
  - Human-readable source files included
  - External services documented with Terms/Privacy links
  - Proper data sanitization and escaping throughout

---

## Credits

Developed by the CookieNod team.

- **Website**: https://cookienod.com
- **Facebook**: https://www.facebook.com/cookienod/
- **GitHub**: https://github.com/cookienod
