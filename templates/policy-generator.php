<?php
/**
 * Policy Generator Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$cookienod_policy_generator = new CookieNod_Policy_Generator();
$cookienod_templates = $cookienod_policy_generator->get_templates();
$cookienod_policy_page_id = get_option('cookienod_policy_page_id');
$cookienod_policy_page = $cookienod_policy_page_id ? get_post($cookienod_policy_page_id) : null;

// Get detected cookies
$cookienod_admin = new CookieNod_Admin();
$cookienod_detected_cookies = $cookienod_admin->get_detected_cookies();
$cookienod_categorized = $cookienod_admin->categorize_cookies($cookienod_detected_cookies);
?>

<div class="wrap cookienod-policy-generator">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if ($cookienod_policy_page) : ?>
        <div class="notice notice-success">
            <p>
                <?php esc_html_e('Your Cookie Policy page is live:', 'cookienod'); ?>
                <a href="<?php echo esc_url(get_permalink($cookienod_policy_page)); ?>" target="_blank">
                    <?php echo esc_html($cookienod_policy_page->post_title); ?>
                </a>
            </p>
            <p>
                <a href="<?php echo esc_url(get_edit_post_link($cookienod_policy_page)); ?>" class="button">
                    <?php esc_html_e('Edit Page', 'cookienod'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <div class="cookienod-card">
        <div class="nav-tab-wrapper">
            <a href="#generate" class="nav-tab nav-tab-active"><?php esc_html_e('Generate', 'cookienod'); ?></a>
            <a href="#detected-cookies" class="nav-tab"><?php esc_html_e('Detected Cookies', 'cookienod'); ?></a>
            <a href="#settings" class="nav-tab"><?php esc_html_e('Settings', 'cookienod'); ?></a>
        </div>

        <!-- Generate Tab -->
        <div id="generate" class="cookienod-tab-content">
            <h2><?php esc_html_e('Generate Cookie Policy', 'cookienod'); ?></h2>

            <p><?php esc_html_e('Select a template to generate your cookie policy:', 'cookienod'); ?></p>

            <div class="policy-templates">
                <?php foreach ($cookienod_templates as $cookienod_key => $cookienod_name) : ?>
                    <div class="policy-template" data-template="<?php echo esc_attr($cookienod_key); ?>">
                        <div class="template-header">
                            <input type="radio" name="policy_template" value="<?php echo esc_attr($cookienod_key); ?>"
                                   id="template-<?php echo esc_attr($cookienod_key); ?>" <?php checked($cookienod_key, 'combined'); ?> />
                            <label for="template-<?php echo esc_attr($cookienod_key); ?>">
                                <strong><?php echo esc_html($cookienod_name); ?></strong>
                            </label>
                        </div>

                        <div class="template-description">
                            <?php
                            $cookienod_descriptions = array(
                                'gdpr' => esc_html__('Best for businesses targeting EU customers. Includes detailed GDPR compliance sections.', 'cookienod'),
                                'ccpa' => esc_html__('Best for businesses targeting California customers. Includes CCPA-specific sections.', 'cookienod'),
                                'combined' => esc_html__('Best for international businesses. Covers both GDPR and CCPA requirements.', 'cookienod'),
                                'simple' => esc_html__('Best for smaller businesses. A concise, general-purpose policy.', 'cookienod'),
                            );
                            echo esc_html($cookienod_descriptions[$cookienod_key] ?? '');
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p>
                <button type="button" class="button button-primary" id="generate-policy"
                        <?php disabled(empty($cookienod_detected_cookies)); ?>>
                    <?php esc_html_e('Generate Policy', 'cookienod'); ?>
                </button>
            </p>

            <?php if (empty($cookienod_detected_cookies)) : ?>
                <div class="notice notice-warning">
                    <p><?php esc_html_e('Please visit your website to detect cookies first. The policy will be generated based on detected cookies.', 'cookienod'); ?></p>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=cookienod-cookies')); ?>" class="button">
                            <?php esc_html_e('Go to Cookie Manager', 'cookienod'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <div id="policy-preview-container" style="display:none;">
                <h3><?php esc_html_e('Preview', 'cookienod'); ?></h3>
                <div id="policy-preview" class="policy-preview-box"></div>

                <p>
                    <?php if ($cookienod_policy_page) : ?>
                        <button type="button" class="button button-primary" id="update-policy-page"
                                data-page-id="<?php echo esc_attr($cookienod_policy_page_id); ?>">
                            <?php esc_html_e('Update Policy Page', 'cookienod'); ?>
                        </button>
                    <?php else : ?>
                        <button type="button" class="button button-primary" id="create-policy-page">
                            <?php esc_html_e('Create Policy Page', 'cookienod'); ?>
                        </button>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Detected Cookies Tab -->
        <div id="detected-cookies" class="cookienod-tab-content" style="display:none;">
            <h2><?php esc_html_e('Detected Cookies', 'cookienod'); ?></h2>

            <?php
            $cookienod_counts = array(
                'necessary' => count($cookienod_categorized['necessary']),
                'functional' => count($cookienod_categorized['functional']),
                'analytics' => count($cookienod_categorized['analytics']),
                'marketing' => count($cookienod_categorized['marketing']),
                'uncategorized' => count($cookienod_categorized['uncategorized']),
            );
            $cookienod_total = array_sum($cookienod_counts);
            ?>

            <p>
                <?php
                printf(
                    esc_html(
                        sprintf(
                            /* translators: %s: Number of cookies */
                            __('Total cookies detected: %s', 'cookienod'),
                            number_format_i18n($cookienod_total)
                        )
                    )
                );
                ?>
            </p>

            <div class="cookie-dashboard-grid">
                <?php foreach ($cookienod_counts as $cookienod_cat => $cookienod_count) : ?>
                    <?php if ($cookienod_count > 0) : ?>
                        <div class="cookienod-card">
                            <h3><?php echo esc_html(ucfirst($cookienod_cat)); ?></h3>
                            <p class="cookie-count"><?php echo esc_html(number_format($cookienod_count)); ?></p>

                            <?php if (!empty($cookienod_categorized[$cookienod_cat])) : ?>
                                <ul class="cookie-list">
                                    <?php foreach (array_slice($cookienod_categorized[$cookienod_cat], 0, 5) as $cookienod_cookie) : ?>
                                        <li><code><?php echo esc_html($cookienod_cookie['name']); ?></code></li>
                                    <?php endforeach; ?>
                                    <?php if (count($cookienod_categorized[$cookienod_cat]) > 5) : ?>
                                        <li>...</li>
                                    <?php endif; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Settings Tab -->
        <?php $cookienod_options = get_option('cookienod_wp_options', array()); ?>
        <div id="settings" class="cookienod-tab-content" style="display:none;">
            <form method="post" action="options.php">
                <?php settings_fields('cookienod_wp_options'); ?>

                <h2><?php esc_html_e('Policy Settings', 'cookienod'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><?php esc_html_e('Auto-Update Policy', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[policy_auto_update]"
                                       value="1" <?php checked($cookienod_options['policy_auto_update'] ?? true); ?> />
                                <?php esc_html_e('Automatically update policy page when new cookies are detected', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e('Show Last Updated', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[policy_show_updated]"
                                       value="1" <?php checked($cookienod_options['policy_show_updated'] ?? true); ?> />
                                <?php esc_html_e('Display "Last Updated" date on policy page', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e('Company Name', 'cookienod'); ?></th>
                        <td>
                            <input type="text" name="cookienod_wp_options[policy_company_name]"
                                   value="<?php echo esc_attr($cookienod_options['policy_company_name'] ?? get_bloginfo('name')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th><?php esc_html_e('Contact Email', 'cookienod'); ?></th>
                        <td>
                            <input type="email" name="cookienod_wp_options[policy_contact_email]"
                                   value="<?php echo esc_attr($cookienod_options['policy_contact_email'] ?? get_option('admin_email')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h3><?php esc_html_e('Shortcodes', 'cookienod'); ?></h3>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Shortcode', 'cookienod'); ?></th>
                        <th><?php esc_html_e('Description', 'cookienod'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[cookienod_policy template="combined"]</code></td>
                        <td><?php esc_html_e('Display the full cookie policy', 'cookienod'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[cookienod_cookie_table category="analytics"]</code></td>
                        <td><?php esc_html_e('Display a table of cookies (optional category filter)', 'cookienod'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Enqueue policy generator styles
wp_enqueue_style(
    'cookienod-policy-generator',
    COOKIENOD_PLUGIN_URL . 'assets/css/policy-generator.css',
    array(),
    COOKIENOD_VERSION
);
?>
