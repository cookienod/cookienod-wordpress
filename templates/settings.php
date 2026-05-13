<?php
/**
 * Settings Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$cookienod_options = get_option('cookienod_wp_options', array());
$cookienod_site_info = get_option('cookienod_wp_site_info');
$cookienod_gcm = new CookieNod_Google_Consent_Mode();
$cookienod_compliance = new CookieNod_Compliance();
?>

<div class="wrap cookienod-settings">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields('cookienod_wp_options'); ?>

        <div class="cookienod-settings-grid">
            <!-- API Settings -->
            <div class="cookienod-card">
                <h2><?php esc_html_e('API Configuration', 'cookienod'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="api_key"><?php esc_html_e('API Key', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="api_key" name="cookienod_wp_options[api_key]"
                                   value="<?php echo esc_attr($cookienod_options['api_key'] ?? ''); ?>"
                                   class="regular-text" />

                            <?php if (!empty($cookienod_options['api_key'])) : ?>
                                <button type="button" class="button" id="verify-api-key">
                                    <?php esc_html_e('Verify Key', 'cookienod'); ?>
                                </button>
                                <span id="api-key-status"></span>
                            <?php endif; ?>

                            <p class="description">
                                <?php esc_html_e('Get your API key at', 'cookienod'); ?>
                                <a href="https://cookienod.com" target="_blank">cookienod.com</a>
                            </p>
                        </td>
                    </tr>

                    <?php if ($cookienod_site_info) : ?>
                        <tr>
                            <th scope="row"><?php esc_html_e('Connected Site', 'cookienod'); ?></th>
                            <td>
                                <p><strong><?php echo esc_html($cookienod_site_info['site_name'] ?? 'Unknown'); ?></strong></p>
                                <p class="description">
                                    <?php esc_html_e('URL:', 'cookienod'); ?> <?php echo esc_html($cookienod_site_info['site_url'] ?? ''); ?><br>
                                    <?php esc_html_e('Plan:', 'cookienod'); ?> <?php echo esc_html(ucfirst($cookienod_site_info['plan'] ?? 'free')); ?><br>
                                    <?php esc_html_e('Status:', 'cookienod'); ?> <?php echo esc_html($cookienod_site_info['status'] ?? ''); ?>
                                </p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </table>
            </div>

            <!-- Display Settings -->
            <div class="cookienod-card">
                <h2><?php esc_html_e('Display Settings', 'cookienod'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="block_mode"><?php esc_html_e('Cookie Blocking Mode', 'cookienod'); ?></label></th>
                        <td>
                            <select id="block_mode" name="cookienod_wp_options[block_mode]">
                                <option value="auto" <?php selected($cookienod_options['block_mode'] ?? '', 'auto'); ?>>
                                    <?php esc_html_e('Auto (recommended) - Automatically block cookies', 'cookienod'); ?>
                                </option>
                                <option value="manual-consent" <?php selected($cookienod_options['block_mode'] ?? '', 'manual-consent'); ?>>
                                    <?php esc_html_e('Manual Consent Attributes - Use data-consent attributes on script tags', 'cookienod'); ?>
                                </option>
                                <option value="silent" <?php selected($cookienod_options['block_mode'] ?? '', 'silent'); ?>>
                                    <?php esc_html_e('Silent Blocking - Server blocks without visible placeholders', 'cookienod'); ?>
                                </option>
                                <option value="manual" <?php selected($cookienod_options['block_mode'] ?? '', 'manual'); ?>>
                                    <?php esc_html_e('Blocking with Placeholders - Server blocks with visible placeholder boxes', 'cookienod'); ?>
                                </option>
                            </select>
                            <p class="description">
                                <?php
                                echo wp_kses_post(
                                    '<strong>' . esc_html__('Auto:', 'cookienod') . '</strong> ' . esc_html__('JavaScript handles all cookie blocking (recommended).', 'cookienod') . '<br>' .
                                    '<strong>' . esc_html__('Manual Consent Attributes:', 'cookienod') . '</strong> ' . esc_html__('Use data-consent attributes on script tags for precise control.', 'cookienod') . '<br>' .
                                    '<strong>' . esc_html__('Silent:', 'cookienod') . '</strong> ' . esc_html__('Server blocks scripts without visible placeholders.', 'cookienod') . '<br>' .
                                    '<strong>' . esc_html__('Placeholders:', 'cookienod') . '</strong> ' . esc_html__('Server blocks with visible placeholder boxes (legacy mode).', 'cookienod'),
                                    'cookienod'
                                );
                                ?>
                            </p>
                            <?php if (($cookienod_options['block_mode'] ?? 'manual') === 'manual-consent') : ?>
                            <div class="cookienod-manual-consent-docs" style="margin-top: 10px; padding: 10px; background: #f0f6fc; border-left: 4px solid #2271b1;">
                                <p><strong><?php esc_html_e('Use data-consent attributes:', 'cookienod'); ?></strong></p>
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
                        <th scope="row"><label for="excluded_scripts"><?php esc_html_e('Excluded Scripts', 'cookienod'); ?></label></th>
                        <td>
                            <textarea id="excluded_scripts" name="cookienod_wp_options[excluded_scripts]" rows="4" class="large-text code" placeholder="*google-analytics.com*&#10;*googletagmanager.com*&#10;*facebook.com/tr*"><?php echo esc_textarea($cookienod_options['excluded_scripts'] ?? ''); ?></textarea>
                            <p class="description">
                                <?php esc_html_e('Enter script URL patterns to exclude (one per line). Use * for wildcards. Example: *google-analytics.com*', 'cookienod'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="banner_position"><?php esc_html_e('Banner Position', 'cookienod'); ?></label></th>
                        <td>
                            <select id="banner_position" name="cookienod_wp_options[banner_position]">
                                <option value="bottom" <?php selected($cookienod_options['banner_position'] ?? 'bottom', 'bottom'); ?>>
                                    <?php esc_html_e('Bottom Banner', 'cookienod'); ?>
                                </option>
                                <option value="top" <?php selected($cookienod_options['banner_position'] ?? '', 'top'); ?>>
                                    <?php esc_html_e('Top Banner', 'cookienod'); ?>
                                </option>
                                <option value="center" <?php selected($cookienod_options['banner_position'] ?? '', 'center'); ?>>
                                    <?php esc_html_e('Center Modal', 'cookienod'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="banner_theme"><?php esc_html_e('Banner Theme', 'cookienod'); ?></label></th>
                        <td>
                            <select id="banner_theme" name="cookienod_wp_options[banner_theme]">
                                <option value="light" <?php selected($cookienod_options['banner_theme'] ?? 'light', 'light'); ?>>
                                    <?php esc_html_e('Light', 'cookienod'); ?>
                                </option>
                                <option value="dark" <?php selected($cookienod_options['banner_theme'] ?? '', 'dark'); ?>>
                                    <?php esc_html_e('Dark', 'cookienod'); ?>
                                </option>
                            </select>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Banner Text Settings -->
            <div class="cookienod-card">
                <h2><?php esc_html_e('Banner Text', 'cookienod'); ?></h2>
                <p class="description"><?php esc_html_e('Customize the text displayed on the cookie consent banner.', 'cookienod'); ?></p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="banner_title"><?php esc_html_e('Banner Title', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="banner_title" name="cookienod_wp_options[banner_title]"
                                   value="<?php echo esc_attr($cookienod_options['banner_title'] ?? esc_html__('Cookie Preferences', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="banner_description"><?php esc_html_e('Banner Description', 'cookienod'); ?></label></th>
                        <td>
                            <textarea id="banner_description" name="cookienod_wp_options[banner_description]"
                                      rows="2" class="large-text"><?php echo esc_textarea($cookienod_options['banner_description'] ?? esc_html__('We use cookies to enhance your experience. Choose your preferences below.', 'cookienod')); ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="btn_accept"><?php esc_html_e('Accept All Button', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="btn_accept" name="cookienod_wp_options[btn_accept]"
                                   value="<?php echo esc_attr($cookienod_options['btn_accept'] ?? esc_html__('Accept All', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="btn_reject"><?php esc_html_e('Reject Button', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="btn_reject" name="cookienod_wp_options[btn_reject]"
                                   value="<?php echo esc_attr($cookienod_options['btn_reject'] ?? esc_html__('Reject', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="btn_customize"><?php esc_html_e('Customize Button', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="btn_customize" name="cookienod_wp_options[btn_customize]"
                                   value="<?php echo esc_attr($cookienod_options['btn_customize'] ?? esc_html__('Customize', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="btn_save"><?php esc_html_e('Save Preferences Button', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="btn_save" name="cookienod_wp_options[btn_save]"
                                   value="<?php echo esc_attr($cookienod_options['btn_save'] ?? esc_html__('Save Preferences', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="settings_title"><?php esc_html_e('Settings Modal Title', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="settings_title" name="cookienod_wp_options[settings_title]"
                                   value="<?php echo esc_attr($cookienod_options['settings_title'] ?? esc_html__('Cookie Settings', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="category_necessary"><?php esc_html_e('Necessary Category Label', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="category_necessary" name="cookienod_wp_options[category_necessary]"
                                   value="<?php echo esc_attr($cookienod_options['category_necessary'] ?? esc_html__('Necessary', 'cookienod')); ?>"
                                   class="regular-text" />
                            <p class="description"><?php esc_html_e('Label for essential cookies category', 'cookienod'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="category_functional"><?php esc_html_e('Functional Category Label', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="category_functional" name="cookienod_wp_options[category_functional]"
                                   value="<?php echo esc_attr($cookienod_options['category_functional'] ?? esc_html__('Functional', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="category_analytics"><?php esc_html_e('Analytics Category Label', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="category_analytics" name="cookienod_wp_options[category_analytics]"
                                   value="<?php echo esc_attr($cookienod_options['category_analytics'] ?? esc_html__('Analytics', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="category_marketing"><?php esc_html_e('Marketing Category Label', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="category_marketing" name="cookienod_wp_options[category_marketing]"
                                   value="<?php echo esc_attr($cookienod_options['category_marketing'] ?? esc_html__('Marketing', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="label_required"><?php esc_html_e('Required Badge', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="label_required" name="cookienod_wp_options[label_required]"
                                   value="<?php echo esc_attr($cookienod_options['label_required'] ?? esc_html__('Required', 'cookienod')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Compliance Settings -->
            <div class="cookienod-card">
                <h2><?php esc_html_e('Compliance Settings', 'cookienod'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Auto-Detect Law', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[auto_detect_law]"
                                       value="1" <?php checked($cookienod_options['auto_detect_law'] ?? false); ?> />
                                <?php esc_html_e('Automatically detect applicable law based on visitor location (requires GeoIP)', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><label for="regulation"><?php esc_html_e('Default Regulation', 'cookienod'); ?></label></th>
                        <td>
                            <select id="regulation" name="cookienod_wp_options[regulation]">
                                <?php foreach ($cookienod_compliance->get_all_regulations() as $cookienod_key => $cookienod_reg) : ?>
                                    <option value="<?php echo esc_attr($cookienod_key); ?>"
                                            <?php selected($cookienod_options['regulation'] ?? 'gdpr', $cookienod_key); ?>>
                                        <?php echo esc_html($cookienod_reg['name']); ?> -
                                        <?php echo esc_html($cookienod_reg['full_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="description">
                                <?php esc_html_e('Used when auto-detect is disabled or GeoIP is unavailable.', 'cookienod'); ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Consent Logging', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[log_consent]"
                                       value="1" <?php checked($cookienod_options['log_consent'] ?? true); ?> />
                                <?php esc_html_e('Log user consent choices for compliance auditing', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- Google Consent Mode Settings -->
            <div class="cookienod-card">
                <h2><?php esc_html_e('Google Consent Mode v2', 'cookienod'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Integrates with Google Tag Manager and Google Analytics for consent-based data collection. Required for compliance with Google\'s EU user consent policy.', 'cookienod'); ?>
                    <a href="https://support.google.com/analytics/answer/9976101" target="_blank">
                        <?php esc_html_e('Learn more', 'cookienod'); ?>
                    </a>
                </p>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e('Enable Consent Mode', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[enable_google_consent_mode]"
                                       value="1" <?php checked($cookienod_options['enable_google_consent_mode'] ?? false); ?> />
                                <?php esc_html_e('Enable Google Consent Mode v2 integration', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('GTM Integration', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[gcm_enable_gtm]"
                                       value="1" <?php checked($cookienod_options['gcm_enable_gtm'] ?? true); ?> />
                                <?php esc_html_e('Pass consent state to Google Tag Manager dataLayer', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e('Redact Data', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[gcm_redact_ads_data]"
                                       value="1" <?php checked($cookienod_options['gcm_redact_ads_data'] ?? true); ?> />
                                <?php esc_html_e('Redact advertising data when marketing consent is denied', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Consent Types', 'cookienod'); ?></h3>
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Type', 'cookienod'); ?></th>
                            <th><?php esc_html_e('Category', 'cookienod'); ?></th>
                            <th><?php esc_html_e('Description', 'cookienod'); ?></th>
                            <th><?php esc_html_e('v2 Only', 'cookienod'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cookienod_gcm->get_consent_types_documentation() as $cookienod_doc) : ?>
                            <tr>
                                <td><code><?php echo esc_html($cookienod_doc['consent_type']); ?></code></td>
                                <td><?php echo esc_html(ucfirst($cookienod_doc['category'])); ?></td>
                                <td><?php echo esc_html($cookienod_doc['description']); ?></td>
                                <td><?php echo $cookienod_doc['v2_only'] ? esc_html('✓') : ''; ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php submit_button(); ?>
    </form>
</div>