<?php
/**
 * Settings Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$options = get_option('cookienod_wp_options', array());
$site_info = get_option('cookienod_wp_site_info');
$gcm = new CookieNod_Google_Consent_Mode();
$compliance = new CookieNod_Compliance();
?>

<div class="wrap cookienod-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('cookienod_wp_options'); ?>

        <div class="cookienod-settings-grid">
            <!-- API Settings -->
            <div class="cookienod-card">
                <h2><?php _e('API Configuration', 'cookienod'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="api_key"><?php _e('API Key', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="api_key" name="cookienod_wp_options[api_key]"
                                   value="<?php echo esc_attr($options['api_key'] ?? ''); ?>"
                                   class="regular-text" />

                            <?php if (!empty($options['api_key'])) : ?>
                                <button type="button" class="button" id="verify-api-key">
                                    <?php _e('Verify Key', 'cookienod'); ?>
                                </button>
                                <span id="api-key-status"></span>
                            <?php endif; ?>

                            <p class="description">
                                <?php _e('Get your API key at', 'cookienod'); ?>
                                <a href="https://cookienod.com" target="_blank">cookienod.com</a>
                            </p>
                        </td>
                    </tr>

                    <?php if ($site_info) : ?>
                        <tr>
                            <th scope="row"><?php _e('Connected Site', 'cookienod'); ?></th>
                            <td>
                                <p><strong><?php echo esc_html($site_info['site_name'] ?? 'Unknown'); ?></strong></p>
                                <p class="description">
                                    <?php _e('URL:', 'cookienod'); ?> <?php echo esc_html($site_info['site_url'] ?? ''); ?><br>
                                    <?php _e('Plan:', 'cookienod'); ?> <?php echo esc_html(ucfirst($site_info['plan'] ?? 'free')); ?><br>
                                    <?php _e('Status:', 'cookienod'); ?> <?php echo esc_html($site_info['status'] ?? ''); ?>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Display Settings -->
            <div class="cookienod-card">
                <h2><?php _e('Display Settings', 'cookienod'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="block_mode"><?php _e('Cookie Blocking Mode', 'cookienod'); ?></label></th>
                        <td>
                            <select id="block_mode" name="cookienod_wp_options[block_mode]">
                                <option value="auto" <?php selected($options['block_mode'] ?? '', 'auto'); ?>>
                                    <?php _e('Auto (recommended) - Automatically block cookies', 'cookienod'); ?>
                                </option>
                                <option value="manual-consent" <?php selected($options['block_mode'] ?? '', 'manual-consent'); ?>>
                                    <?php _e('Manual Consent Attributes - Use data-consent attributes on script tags', 'cookienod'); ?>
                                </option>
                                <option value="silent" <?php selected($options['block_mode'] ?? '', 'silent'); ?>>
                                    <?php _e('Silent Blocking - Server blocks without visible placeholders', 'cookienod'); ?>
                                </option>
                                <option value="manual" <?php selected($options['block_mode'] ?? '', 'manual'); ?>>
                                    <?php _e('Blocking with Placeholders - Server blocks with visible placeholder boxes', 'cookienod'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php _e('<strong>Banner Only:</strong> JavaScript handles all cookie blocking (recommended). <strong>Manual Consent:</strong> Use data-consent attributes on script tags for precise control. <strong>Silent:</strong> Server blocks scripts without visible placeholders. <strong>Placeholders:</strong> Server blocks with visible placeholder boxes (legacy mode).', 'cookienod'); ?>
                            </p>
                            <?php if (($options['block_mode'] ?? 'manual') === 'manual-consent') : ?>
                            <div class="cookienod-manual-consent-docs" style="margin-top: 10px; padding: 10px; background: #f0f6fc; border-left: 4px solid #2271b1;">
                                <p><strong><?php _e('Use data-consent attributes:', 'cookienod'); ?></strong></p>
                                <code style="display: block; padding: 8px; background: #fff; margin: 5px 0; font-size: 12px;">
                                    &lt;script data-consent="analytics" src="ga.js"&gt;&lt;/script&gt;<br>
                                    &lt;script data-consent="analytics,marketing" src="tracking.js"&gt;&lt;/script&gt;<br>
                                    &lt;script data-consent="necessary" src="jquery.js"&gt;&lt;/script&gt;
                                </code>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="excluded_scripts"><?php _e('Excluded Scripts', 'cookienod'); ?></label></th>
                        <td>
                            <textarea id="excluded_scripts" name="cookienod_wp_options[excluded_scripts]" rows="4" class="large-text code" placeholder="*google-analytics.com*&#10;*googletagmanager.com*&#10;*facebook.com/tr*"><?php echo esc_textarea($options['excluded_scripts'] ?? ''); ?></textarea>
                            <p class="description">
                                <?php _e('Enter script URL patterns to exclude (one per line). Use * for wildcards. Example: *google-analytics.com*', 'cookienod'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="banner_position"><?php _e('Banner Position', 'cookienod'); ?></label></th>
                        <td>
                            <select id="banner_position" name="cookienod_wp_options[banner_position]">
                                <option value="bottom" <?php selected($options['banner_position'] ?? 'bottom', 'bottom'); ?>>
                                    <?php _e('Bottom Banner', 'cookienod'); ?>
                                </option>
                                <option value="top" <?php selected($options['banner_position'] ?? '', 'top'); ?>>
                                    <?php _e('Top Banner', 'cookienod'); ?>
                                </option>
                                <option value="center" <?php selected($options['banner_position'] ?? '', 'center'); ?>>
                                    <?php _e('Center Modal', 'cookienod'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="banner_theme"><?php _e('Banner Theme', 'cookienod'); ?></label></th>
                        <td>
                            <select id="banner_theme" name="cookienod_wp_options[banner_theme]">
                                <option value="light" <?php selected($options['banner_theme'] ?? 'light', 'light'); ?>>
                                    <?php _e('Light', 'cookienod'); ?>
                                </option>
                                <option value="dark" <?php selected($options['banner_theme'] ?? '', 'dark'); ?>>
                                    <?php _e('Dark', 'cookienod'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Banner Text Settings -->
            <div class="cookienod-card">
                <h2><?php _e('Banner Text', 'cookienod'); ?></h2>
                <p class="description"><?php _e('Customize the text displayed on the cookie consent banner.', 'cookienod'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="banner_title"><?php _e('Banner Title', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="banner_title" name="cookienod_wp_options[banner_title]"
                                   value="<?php echo esc_attr($options['banner_title'] ?? __('Cookie Preferences', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="banner_description"><?php _e('Banner Description', 'cookienod'); ?></label></th>
                        <td>
                            <textarea id="banner_description" name="cookienod_wp_options[banner_description]"
                                      rows="2" class="large-text"><?php echo esc_textarea($options['banner_description'] ?? __('We use cookies to enhance your experience. Choose your preferences below.', 'cookienod')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="btn_accept"><?php _e('Accept All Button', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="btn_accept" name="cookienod_wp_options[btn_accept]"
                                   value="<?php echo esc_attr($options['btn_accept'] ?? __('Accept All', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="btn_reject"><?php _e('Reject Button', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="btn_reject" name="cookienod_wp_options[btn_reject]"
                                   value="<?php echo esc_attr($options['btn_reject'] ?? __('Reject', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="btn_customize"><?php _e('Customize Button', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="btn_customize" name="cookienod_wp_options[btn_customize]"
                                   value="<?php echo esc_attr($options['btn_customize'] ?? __('Customize', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="btn_save"><?php _e('Save Preferences Button', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="btn_save" name="cookienod_wp_options[btn_save]"
                                   value="<?php echo esc_attr($options['btn_save'] ?? __('Save Preferences', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="settings_title"><?php _e('Settings Modal Title', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="settings_title" name="cookienod_wp_options[settings_title]"
                                   value="<?php echo esc_attr($options['settings_title'] ?? __('Cookie Settings', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="category_necessary"><?php _e('Necessary Category Label', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="category_necessary" name="cookienod_wp_options[category_necessary]"
                                   value="<?php echo esc_attr($options['category_necessary'] ?? __('Necessary', 'cookienod')); ?>"
                                   class="regular-text" />
                            <p class="description"><?php _e('Label for essential cookies category', 'cookienod'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="category_functional"><?php _e('Functional Category Label', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="category_functional" name="cookienod_wp_options[category_functional]"
                                   value="<?php echo esc_attr($options['category_functional'] ?? __('Functional', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="category_analytics"><?php _e('Analytics Category Label', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="category_analytics" name="cookienod_wp_options[category_analytics]"
                                   value="<?php echo esc_attr($options['category_analytics'] ?? __('Analytics', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="category_marketing"><?php _e('Marketing Category Label', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="category_marketing" name="cookienod_wp_options[category_marketing]"
                                   value="<?php echo esc_attr($options['category_marketing'] ?? __('Marketing', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="label_required"><?php _e('Required Badge', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="label_required" name="cookienod_wp_options[label_required]"
                                   value="<?php echo esc_attr($options['label_required'] ?? __('Required', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Compliance Settings -->
            <div class="cookienod-card">
                <h2><?php _e('Compliance Settings', 'cookienod'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Auto-Detect Law', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[auto_detect_law]"
                                       value="1" <?php checked($options['auto_detect_law'] ?? false); ?> />
                                <?php _e('Automatically detect applicable law based on visitor location (requires GeoIP)', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="regulation"><?php _e('Default Regulation', 'cookienod'); ?></label></th>
                        <td>
                            <select id="regulation" name="cookienod_wp_options[regulation]">
                                <?php foreach ($compliance->get_all_regulations() as $key => $reg) : ?>
                                    <option value="<?php echo esc_attr($key); ?>"
                                            <?php selected($options['regulation'] ?? 'gdpr', $key); ?>>
                                        <?php echo esc_html($reg['name']); ?> -
                                        <?php echo esc_html($reg['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php _e('Used when auto-detect is disabled or GeoIP is unavailable.', 'cookienod'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Consent Logging', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[log_consent]"
                                       value="1" <?php checked($options['log_consent'] ?? true); ?> />
                                <?php _e('Log user consent choices for compliance auditing', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Google Consent Mode Settings -->
            <div class="cookienod-card">
                <h2><?php _e('Google Consent Mode v2', 'cookienod'); ?></h2>
                <p class="description">
                    <?php _e('Integrates with Google Tag Manager and Google Analytics for consent-based data collection. Required for compliance with Google\'s EU user consent policy.', 'cookienod'); ?>
                    <a href="https://support.google.com/analytics/answer/9976101" target="_blank">
                        <?php _e('Learn more', 'cookienod'); ?>
                    </a>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php _e('Enable Consent Mode', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[enable_google_consent_mode]"
                                       value="1" <?php checked($options['enable_google_consent_mode'] ?? false); ?> />
                                <?php _e('Enable Google Consent Mode v2 integration', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('GTM Integration', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[gcm_enable_gtm]"
                                       value="1" <?php checked($options['gcm_enable_gtm'] ?? true); ?> />
                                <?php _e('Pass consent state to Google Tag Manager dataLayer', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php _e('Redact Data', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[gcm_redact_ads_data]"
                                       value="1" <?php checked($options['gcm_redact_ads_data'] ?? true); ?> />
                                <?php _e('Redact advertising data when marketing consent is denied', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Consent Types', 'cookienod'); ?></h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Type', 'cookienod'); ?></th>
                            <th><?php _e('Category', 'cookienod'); ?></th>
                            <th><?php _e('Description', 'cookienod'); ?></th>
                            <th><?php _e('v2 Only', 'cookienod'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($gcm->get_consent_types_documentation() as $doc) : ?>
                            <tr>
                                <td><code><?php echo esc_html($doc['consent_type']); ?></code></td>
                                <td><?php echo esc_html(ucfirst($doc['category'])); ?></td>
                                <td><?php echo esc_html($doc['description']); ?></td>
                                <td><?php echo $doc['v2_only'] ? 'âœ“' : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>