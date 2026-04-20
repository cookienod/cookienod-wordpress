# CookieNod WordPress Plugin - Complete User Guide

<p align="center">
  <img src="https://www.cookienod.com/logo-white.svg" alt="CookieNod Logo" width="120">
  <br>
  <strong>GDPR & CCPA Compliant Cookie Consent Management</strong>
  <br>
  <em>Automated Scanning • Intelligent Blocking • Beautiful Banners</em>
</p>

---

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Quick Start](#quick-start)
4. [Dashboard Overview](#dashboard-overview)
5. [Settings Explained](#settings-explained)
6. [Cookie Manager](#cookie-manager)
7. [Consent Log](#consent-log)
8. [Advanced Features](#advanced-features)
9. [Troubleshooting](#troubleshooting)
10. [FAQ](#faq)

---

## Overview

CookieNod is a powerful cookie consent management solution that combines **server-side scanning** with **client-side intelligent blocking** to ensure your WordPress site complies with GDPR, CCPA, and other privacy regulations.

### How It Works

```
┌─────────────────────────────────────────────────────────────┐
│                    YOUR WORDPRESS SITE                       │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│   CookieNod uses two layers of protection:                   │
│                                                              │
│   ┌─────────────────┐      ┌──────────────────┐           │
│   │  Server Layer   │      │  Browser Layer   │           │
│   │                 │      │                  │           │
│   │ • Blocks Google │      │ • Intercepts     │           │
│   │   Analytics     │      │   cookie setting │           │
│   │ • Blocks FB     │      │ • Shows consent  │           │
│   │   Pixel         │      │   banner         │           │
│   │ • Prevents load │      │ • Respects user  │           │
│   │                 │      │   choices        │           │
│   └─────────────────┘      └──────────────────┘           │
│                                                              │
│   Plus: Automatic cookie scanning keeps your                 │
│   cookie list up-to-date                                     │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Key Features

| Feature | Description |
|---------|-------------|
| 🛡️ **Auto Blocking** | Blocks cookies & scripts before page load |
| 🔍 **Server Scanning** | Crawls your site to discover all cookies |
| 📊 **Consent Log** | Records all user consent decisions |
| 🎨 **Customizable** | Match your brand colors & style |
| 📱 **Responsive** | Works perfectly on mobile devices |
| ⚡ **Fast** | Minimal impact on page speed |

---

## Installation

### Method 1: WordPress Admin (Recommended)

1. Download `cookienod.zip` from your [CookieNod Dashboard](https://cookienod.com/dashboard)
2. Go to **WordPress Admin → Plugins → Add New → Upload Plugin**
3. Select `cookienod.zip` and click **Install Now**
4. Click **Activate Plugin**

### Method 2: FTP Upload

1. Extract `cookienod.zip` to your computer
2. Upload the `cookienod` folder to `/wp-content/plugins/`
3. Go to **WordPress Admin → Plugins** and activate **CookieNod**

### Method 3: Git Clone (Developers)

```bash
cd /wp-content/plugins/
git clone https://github.com/cookienod/wp-plugin.git cookienod
```

---

## Quick Start

### Step 1: Get Your License Key

1. Log in to [CookieNod Dashboard](https://cookienod.com/dashboard)
2. Go to **License Keys** section
3. Copy your license key (starts with `CB-`)

### Step 2: Configure the Plugin

1. In WordPress Admin, go to **CookieNod → Settings**
2. Paste your license key in the **API Key** field
3. Click **Save Changes**
4. Click **Verify API Key** to confirm it's working

### Step 3: Customize (Optional)

1. Choose your **Banner Position** (bottom, top, or center)
2. Select **Blocking Mode** (auto, manual-consent, silent, or manual with placeholders)
3. Pick a **Theme** (light or dark)
4. Save changes

### Step 4: Test Your Site

Visit your site in an incognito window. You should see the cookie consent banner!

---

## Dashboard Overview

After activation, you'll see a new **CookieNod** menu in your WordPress admin sidebar.

```
┌─────────────────────────────────────┐
│  🍪 CookieNod                      │
├─────────────────────────────────────┤
│  Dashboard     → Overview & stats    │
│  Settings      → Configure plugin    │
│  Cookie Manager → View & manage      │
│  Consent Log   → View user choices   │
│  A/B Testing   → Optimize banners    │
│  Customize CSS → Style adjustments   │
│  Cookie Policy → Generate policy     │
└─────────────────────────────────────┘
```

### Dashboard Page

The main dashboard shows:
- **License Status**: Active/Inactive
- **Plan Details**: Your current subscription tier
- **Recent Activity**: Latest cookie discoveries
- **Quick Actions**: Links to common settings

---

## Settings Explained

### General Settings

#### API Key
```
┌──────────────────────────────────────────────────┐
│  🔑 API Key                                        │
│  ┌────────────────────────────────────────────┐  │
│  │ CB-AEwsWPRD6I9BWOwi5VoCwt8xdpxqovYrnlRVE_ │  │
│  │ dFYuw                                      │  │
│  └────────────────────────────────────────────┘  │
│                                    [Verify Key]  │
└──────────────────────────────────────────────────┘
```

Your unique identifier that connects your WordPress site to CookieNod services. 

- **Where to find**: CookieNod Dashboard → License Keys
- **Format**: Starts with `CB-` followed by 64 characters
- **Security**: Keep this secret - it controls access to your data

**Status Indicators:**
- ✅ **Verified** - Connected successfully
- ❌ **Invalid** - Check the key is copied correctly
- ⚠️ **Domain Mismatch** - Key is for a different domain

---

#### Cookie Blocking Mode

```
┌──────────────────────────────────────────────────┐
│  🛡️ Cookie Blocking Mode                           │
│                                                  │
│  ○ Auto (recommended)                            │
│  ○ Manual Consent Attributes                     │
│  ○ Silent Blocking                               │
│  ○ Blocking with Placeholders                    │
│                                                  │
│  ℹ️ Choose how cookies and scripts are blocked   │
│     based on user consent preferences.           │
└──────────────────────────────────────────────────┘
```

**Auto (Recommended)**
- JavaScript banner handles all cookie blocking
- Blocks Google Analytics, Facebook Pixel, and other trackers
- Intercepts cookies before they're set
- Only allows "Necessary" cookies until consent
- Best for GDPR compliance

**Manual Consent Attributes**
- Use `data-consent` attributes on script tags for precise control
- Example: `<script data-consent="analytics" src="ga.js">`
- Supports multiple categories: `data-consent="analytics,marketing"`
- Good for custom implementations requiring fine-grained control

**Silent Blocking**
- Server blocks scripts without visible placeholders
- Cookies are blocked in the background
- No visible indication of blocked content
- Clean look but less transparent to users

**Blocking with Placeholders (Legacy)**
- Server blocks with visible placeholder boxes
- Shows placeholder where blocked scripts would appear
- Users see what content is being withheld
- Higher transparency but more visually intrusive

---

#### Banner Position

```
┌──────────────────────────────────────────────────┐
│  📍 Banner Position                                │
│                                                  │
│  ○ Bottom Banner                                 │
│  ○ Top Banner                                    │
│  ● Center Modal                                  │
│                                                  │
├──────────────────────────────────────────────────┤
│                                                  │
│   ┌──────────────────────────────────────────┐   │
│   │                                          │   │
│   │        Cookie Preferences               │   │
│   │                                          │   │
│   │   We use cookies to enhance...         │   │
│   │                                          │   │
│   │   [Reject] [Customize] [Accept All]   │   │
│   │                                          │   │
│   └──────────────────────────────────────────┘   │
│              ↑ Center Modal Preview              │
└──────────────────────────────────────────────────┘
```

**Bottom Banner**
- Standard footer banner
- Least intrusive
- Most common choice

**Top Banner**
- Header notification style
- Visible but can push content down

**Center Modal**
- Blocks interaction until decision
- Highest visibility
- Best for strict compliance requirements

---

#### Banner Theme

```
┌──────────────────────────────────────────────────┐
│  🎨 Banner Theme                                   │
│                                                  │
│  ○ Light Theme                                   │
│  ● Dark Theme                                    │
│                                                  │
├──────────────────────────────────────────────────┤
│                                                  │
│  Preview:                                        │
│  ┌──────────────────────────────────────────┐   │
│  │ 🍪 Cookie Preferences                   │   │
│  │ We use cookies to enhance...           │   │
│  │ [Reject] [Customize] [Accept All]       │   │
│  └──────────────────────────────────────────┘   │
│           ↑ Matches your selection              │
└──────────────────────────────────────────────────┘
```

Choose between **Light** (white background, dark text) or **Dark** (dark background, light text) to match your website design.

---

### Advanced Settings

#### Enable Google Consent Mode

```
┌──────────────────────────────────────────────────┐
│  📊 Google Consent Mode v2                         │
│  ☑ Enable Google Consent Mode                     │
└──────────────────────────────────────────────────┘
```

When enabled, CookieNod integrates with Google's Consent Mode API:
- Sends consent signals to Google Tag Manager
- Controls Google Analytics 4 and Google Ads behavior
- Supports the new `ad_user_data` and `ad_personalization` signals

**Requires**: Google Tag Manager or gtag.js already on your site

---

#### A/B Testing

```
┌──────────────────────────────────────────────────┐
│  🧪 A/B Testing                                    │
│                                                  │
│  Create tests to optimize your consent banner    │
│  and improve consent rates.                        │
│                                                  │
│  [ Create New Test ]                              │
└──────────────────────────────────────────────────┘
```

Allows you to test:
- Different banner positions (bottom, top, center)
- Different button colors
- Traffic splitting between variants
- View conversion rates and accept rates
- Set winning variant to apply permanently

---

## Cookie Manager

### Automatic Scanning

CookieNod automatically scans your site weekly to discover new cookies.

### Cookie Detection

Cookies are detected automatically when visitors browse your website. The CookieNod JavaScript scanner identifies cookies as they are set in users' browsers.

**To trigger detection:**
1. Visit your website in an incognito/private window
2. Interact with the consent banner (accept/reject/customize)
3. Browse different pages on your site
4. Return to **CookieNod → Cookie Manager** to see detected cookies

```
┌──────────────────────────────────────────────────┐
│  🔍 Cookie Manager                                 │
│                                                  │
│  Last updated: 3 days ago                         │
│                                                  │
│  ┌─────────┐ ┌─────────┐ ┌─────────┐           │
│  │    5    │ │    3    │ │    4    │           │
│  │Necessary│ │Functional│ │Analytics│           │
│  └─────────┘ └─────────┘ └─────────┘           │
│                                                  │
│  [All Cookies] [Necessary] [Functional] ...      │
│                                                  │
│  Cookie Name    Category   Type   Source        │
│  ────────────────────────────────────────────── │
│  _ga            Analytics HTTP   google.com     │
│  PHPSESSID      Necessary HTTP   example.com    │
│                                                  │
└──────────────────────────────────────────────────┘
```

### Scan Results

| Column | Description |
|--------|-------------|
| Cookie Name | The actual cookie identifier |
| Domain | Where the cookie is set |
| Category | Necessary, Functional, Analytics, or Marketing |
| Description | What the cookie does |
| Source | First-party or Third-party |

---

## Consent Log

### Viewing Consent Records

```
┌──────────────────────────────────────────────────┐
│  📋 Consent Log                                    │
├──────────────────────────────────────────────────┤
│  Date              │ User │ Preferences           │
├──────────────────┼──────┼─────────────────────┤
│  2024-01-15      │ #123 │ ✅ Necessary        │
│  14:32:01        │      │ ❌ Functional       │
│                  │      │ ✅ Analytics         │
│                  │      │ ❌ Marketing         │
├──────────────────┼──────┼─────────────────────┤
│  2024-01-15      │ #124 │ ✅ All Categories   │
│  14:45:22        │      │                     │
└──────────────────┴──────┴─────────────────────┘

Export: [ 📥 CSV ] [ 📥 JSON ]
```

The consent log records:
- **Timestamp**: When consent was given/changed
- **User ID**: Anonymous identifier (not personal data)
- **IP Address**: Hashed for privacy
- **Consent Choices**: Which categories were accepted
- **Banner Variant**: If A/B testing is enabled

### Data Retention

Consent logs are stored for **12 months** by default. You can export them for compliance audits.

---

## Advanced Features

### Custom CSS

```
┌──────────────────────────────────────────────────┐
│  🎨 Customize CSS                                  │
│                                                  │
│  .cookienod-banner {                             │
│    background: #your-brand-color !important;     │
│    border-radius: 12px !important;             │
│  }                                               │
│                                                  │
│  .cookienod-accept-btn {                        │
│    background: linear-gradient(...) !important; │
│  }                                               │
└──────────────────────────────────────────────────┘
```

**Common Customizations:**

```css
/* Change banner background */
#cs-consent-banner {
  background: #1a1a2e !important;
}

/* Style the accept button */
#cs-accept-btn {
  background: #e94560 !important;
  border-radius: 8px !important;
}

/* Change font */
#cs-consent-banner {
  font-family: 'Your Font', sans-serif !important;
}
```

---

### Cookie Policy Generator

```
┌──────────────────────────────────────────────────┐
│  📄 Cookie Policy Generator                      │
│                                                  │
│  Generate a comprehensive cookie policy based   │
│  on your actual cookie scan results.              │
│                                                  │
│  [ 📄 Generate Policy ]                           │
│                                                  │
│  ┌──────────────────────────────────────────┐   │
│  │ Cookie Policy for example.com            │   │
│  │                                          │   │
│  │ This website uses cookies...             │   │
│  │                                          │   │
│  │ [ Copy to Clipboard ]                    │   │
│  └──────────────────────────────────────────┘   │
└──────────────────────────────────────────────────┘
```

The generated policy includes:
- Introduction to cookies
- List of all detected cookies
- Purpose of each cookie
- Expiration information
- How to manage preferences

**To use:**
1. Generate the policy
2. Copy the content
3. Create a new WordPress page
4. Paste and publish
5. Link from your footer

---

## Troubleshooting

### API Key Not Verified

**Symptoms**: "Invalid API Key" message

**Solutions**:
1. Check you've copied the entire key (starts with `CB-` and is 64 characters long)
2. Ensure your domain matches the license key domain
3. Verify your WordPress site URL matches exactly what's registered
4. Contact support if the issue persists

---

### Banner Not Showing

**Symptoms**: No cookie banner appears

**Checklist**:
- [ ] Plugin is activated
- [ ] License key is valid
- [ ] No JavaScript errors in browser console
- [ ] Consent cookie not already set (check in incognito/private window)

**Debug Mode**:
Enable debug mode in settings to see detailed information in your browser's developer console:
1. Go to **CookieNod → Settings**
2. Check "Enable Debug Mode"
3. Open browser developer console (F12) and check the Console tab

---

### Cookies Not Being Blocked

**Symptoms**: Analytics still firing before consent

**Common Causes**:
1. **Blocking mode not enabled**: Check settings
2. **Caching**: Clear all caches (plugin, CDN, browser)
3. **Consent already given**: Check in an incognito/private window

**Solution**:
1. Go to **CookieNod → Settings**
2. Ensure **Auto** mode is selected for JavaScript-based blocking
3. For server-side blocking, select **Silent** or **Placeholders** mode
4. Clear all caches and test again

---

### Translation Not Loading

**Symptoms**: English text instead of your language

**Solution**:
CookieNod uses WordPress standard translation:
1. Go to **Settings → General → Site Language**
2. Select your language
3. CookieNod will load matching translations

Available languages: English, Spanish, French, German, Italian, Dutch

---

## FAQ

### General Questions

**Q: Is CookieNod free?**
A: Yes! We offer a free tier with basic features. Premium plans unlock advanced scanning, A/B testing, and priority support.

**Q: Does it work with caching plugins?**
A: Yes. CookieNod is compatible with WP Rocket, W3 Total Cache, LiteSpeed Cache, and others. The banner uses JavaScript which loads after cached content.

**Q: Will it slow down my site?**
A: No. CookieNod loads asynchronously and has minimal impact. The script is ~12KB minified and gzipped.

### Technical Questions

**Q: How does CookieNod block cookies?**
A: CookieNod uses two layers of protection. First, it prevents third-party scripts (like Google Analytics, Facebook Pixel) from loading on your page. Second, it monitors cookie activity in the browser to ensure only approved cookies are set based on user consent.

**Q: What cookie categories are supported?**
A: Necessary, Functional, Analytics, and Marketing. You can customize these in your CookieNod Dashboard.

**Q: How do I update the cookie database?**
A: Cookies are detected automatically when visitors browse your site. Visit your website in an incognito window and interact with pages to trigger detection. The Cookie Manager page displays all detected cookies.

**Q: Can I use it with Google Tag Manager?**
A: Yes! Enable "Google Consent Mode" in settings and use our GTM template from the Tag Gallery.

### Compliance Questions

**Q: Is it GDPR compliant?**
A: Yes. CookieNod implements GDPR requirements including prior consent, granular categories, and consent logs.

**Q: Does it support CCPA?**
A: Yes. The "Do Not Sell" requirements are handled through the Marketing category.

**Q: What regulations are supported?**
A: CookieNod supports 20+ global regulations including GDPR (EU/UK), CCPA/CPRA (California), LGPD (Brazil), POPIA (South Africa), PIPEDA (Canada), and many more. Auto-detection is available based on visitor location (requires GeoIP).

**Q: How long are consent logs kept?**
A: 12 months by default, configurable in your dashboard.

**Q: Can users withdraw consent?**
A: Yes. The floating cookie button (appears after first consent) allows users to change preferences anytime.

---

## Getting Help

### Support Channels

| Method | Response Time | Best For |
|--------|---------------|----------|
| Documentation | Instant | How-to guides |
| Email Support | 24 hours | Complex issues |
| Live Chat | Real-time | Quick questions |
| GitHub Issues | 48 hours | Bug reports |

### Contact Us

- 🌐 **Website**: [cookienod.com](https://cookienod.com)
- 📧 **Email**: support@cookienod.com
- 💬 **Live Chat**: Available in dashboard (9am-6pm EST)
- 🐙 **GitHub**: github.com/cookienod/wp-plugin

---

## Changelog

### Version 1.0.0
- ✅ Initial release
- ✅ WordPress 6.x compatibility
- ✅ WooCommerce integration
- ✅ Google Consent Mode v2 support
- ✅ A/B testing framework
- ✅ Custom CSS support

---

<p align="center">
  <strong>Made with ❤️ by the CookieNod Team</strong>
  <br>
  <a href="https://cookienod.com">cookienod.com</a> • 
  <a href="https://twitter.com/cookienod">Twitter</a> • 
  <a href="https://github.com/cookienod">GitHub</a>
</p>
