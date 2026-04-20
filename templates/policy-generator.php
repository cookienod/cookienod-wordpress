<?php
/**
 * Policy Generator Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$policy_generator = new CookieNod_Policy_Generator();
$templates = $policy_generator->get_templates();
$policy_page_id = get_option('cookienod_policy_page_id');
$policy_page = $policy_page_id ? get_post($policy_page_id) : null;

// Get detected cookies
$admin = new CookieNod_Admin();
$cookies = $admin->get_detected_cookies();
$categorized = $admin->categorize_cookies($cookies);
?>

<div class="wrap cookienod-policy-generator">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if ($policy_page) : ?>
        <div class="notice notice-success">
            <p>
                <?php _e('Your Cookie Policy page is live:', 'cookienod'); ?>
                <a href="<?php echo esc_url(get_permalink($policy_page)); ?>" target="_blank">
                    <?php echo esc_html($policy_page->post_title); ?>
                </a>
            </p>
            <p>
                <a href="<?php echo esc_url(get_edit_post_link($policy_page)); ?>" class="button">
                    <?php _e('Edit Page', 'cookienod'); ?>
                </a>
            </p>
        </div>
    <?php endif; ?>

    <div class="cookienod-card">
        <div class="nav-tab-wrapper">
            <a href="#generate" class="nav-tab nav-tab-active"><?php _e('Generate', 'cookienod'); ?></a>
            <a href="#detected-cookies" class="nav-tab"><?php _e('Detected Cookies', 'cookienod'); ?></a>
            <a href="#settings" class="nav-tab"><?php _e('Settings', 'cookienod'); ?></a>
        </div>

        <!-- Generate Tab -->
        <div id="generate" class="cookienod-tab-content">
            <h2><?php _e('Generate Cookie Policy', 'cookienod'); ?></h2>

            <p><?php _e('Select a template to generate your cookie policy:', 'cookienod'); ?></p>

            <div class="policy-templates">
                <?php foreach ($templates as $key => $name) : ?>
                    <div class="policy-template" data-template="<?php echo esc_attr($key); ?>">
                        <div class="template-header">
                            <input type="radio" name="policy_template" value="<?php echo esc_attr($key); ?>"
                                   id="template-<?php echo esc_attr($key); ?>" <?php checked($key, 'combined'); ?> />
                            <label for="template-<?php echo esc_attr($key); ?>">
                                <strong><?php echo esc_html($name); ?></strong>
                            </label>
                        </div>

                        <div class="template-description">
                            <?php
                            $descriptions = array(
                                'gdpr' => __('Best for businesses targeting EU customers. Includes detailed GDPR compliance sections.', 'cookienod'),
                                'ccpa' => __('Best for businesses targeting California customers. Includes CCPA-specific sections.', 'cookienod'),
                                'combined' => __('Best for international businesses. Covers both GDPR and CCPA requirements.', 'cookienod'),
                                'simple' => __('Best for smaller businesses. A concise, general-purpose policy.', 'cookienod'),
                            );
                            echo esc_html($descriptions[$key] ?? '');
                            ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <p>
                <button type="button" class="button button-primary" id="generate-policy"
                        <?php disabled(empty($cookies)); ?>>
                    <?php _e('Generate Policy', 'cookienod'); ?>
                </button>
            </p>

            <?php if (empty($cookies)) : ?>
                <div class="notice notice-warning">
                    <p><?php _e('Please visit your website to detect cookies first. The policy will be generated based on detected cookies.', 'cookienod'); ?></p>
                    <p>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=cookienod-cookies')); ?>" class="button">
                            <?php _e('Go to Cookie Manager', 'cookienod'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <div id="policy-preview-container" style="display:none;">
                <h3><?php _e('Preview', 'cookienod'); ?></h3>
                <div id="policy-preview" class="policy-preview-box"></div>

                <p>
                    <?php if ($policy_page) : ?>
                        <button type="button" class="button button-primary" id="update-policy-page"
                                data-page-id="<?php echo esc_attr($policy_page_id); ?>">
                            <?php _e('Update Policy Page', 'cookienod'); ?>
                        </button>
                    <?php else : ?>
                        <button type="button" class="button button-primary" id="create-policy-page">
                            <?php _e('Create Policy Page', 'cookienod'); ?>
                        </button>
                    <?php endif; ?>
                </p>
            </div>
        </div>

        <!-- Detected Cookies Tab -->
        <div id="detected-cookies" class="cookienod-tab-content" style="display:none;">
            <h2><?php _e('Detected Cookies', 'cookienod'); ?></h2>

            <?php
            $counts = array(
                'necessary' => count($categorized['necessary']),
                'functional' => count($categorized['functional']),
                'analytics' => count($categorized['analytics']),
                'marketing' => count($categorized['marketing']),
                'uncategorized' => count($categorized['uncategorized']),
            );
            $total = array_sum($counts);
            ?>

            <p>
                <?php printf(__('Total cookies detected: %s', 'cookienod'), number_format($total)); ?>
            </p>

            <div class="cookie-dashboard-grid">
                <?php foreach ($counts as $cat => $count) : ?>
                    <?php if ($count > 0) : ?>
                        <div class="cookienod-card">
                            <h3><?php echo esc_html(ucfirst($cat)); ?></h3>
                            <p class="cookie-count"><?php echo number_format($count); ?></p>

                            <?php if (!empty($categorized[$cat])) : ?>
                                <ul class="cookie-list">
                                    <?php foreach (array_slice($categorized[$cat], 0, 5) as $cookie) : ?>
                                        <li><code><?php echo esc_html($cookie['name']); ?></code></li>
                                    <?php endforeach; ?>
                                    <?php if (count($categorized[$cat]) > 5) : ?>
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
        <?php $options = get_option('cookienod_wp_options', array()); ?>
        <div id="settings" class="cookienod-tab-content" style="display:none;">
            <form method="post" action="options.php">
                <?php settings_fields('cookienod_wp_options'); ?>

                <h2><?php _e('Policy Settings', 'cookienod'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><?php _e('Auto-Update Policy', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[policy_auto_update]"
                                       value="1" <?php checked($options['policy_auto_update'] ?? true); ?> />
                                <?php _e('Automatically update policy page when new cookies are detected', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><?php _e('Show Last Updated', 'cookienod'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="cookienod_wp_options[policy_show_updated]"
                                       value="1" <?php checked($options['policy_show_updated'] ?? true); ?> />
                                <?php _e('Display "Last Updated" date on policy page', 'cookienod'); ?>
                            </label>
                        </td>
                    </tr>

                    <tr>
                        <th><?php _e('Company Name', 'cookienod'); ?></th>
                        <td>
                            <input type="text" name="cookienod_wp_options[policy_company_name]"
                                   value="<?php echo esc_attr($options['policy_company_name'] ?? get_bloginfo('name')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>

                    <tr>
                        <th><?php _e('Contact Email', 'cookienod'); ?></th>
                        <td>
                            <input type="email" name="cookienod_wp_options[policy_contact_email]"
                                   value="<?php echo esc_attr($options['policy_contact_email'] ?? get_option('admin_email')); ?>"
                                   class="regular-text" />
                        </td>
                    </tr>
                </table>

                <?php submit_button(); ?>
            </form>

            <hr>

            <h3><?php _e('Shortcodes', 'cookienod'); ?></h3>

            <table class="widefat striped">
                <thead>
                    <tr>
                        <th><?php _e('Shortcode', 'cookienod'); ?></th>
                        <th><?php _e('Description', 'cookienod'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[cookienod_policy template="combined"]</code></td>
                        <td><?php _e('Display the full cookie policy', 'cookienod'); ?></td>
                    </tr>
                    <tr>
                        <td><code>[cookienod_cookie_table category="analytics"]</code></td>
                        <td><?php _e('Display a table of cookies (optional category filter)', 'cookienod'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
.policy-templates {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.policy-template {
    border: 2px solid #c3c4c7;
    border-radius: 4px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.2s;
}

.policy-template:hover,
.policy-template.selected {
    border-color: #2271b1;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
}

.policy-template.selected {
    background: #f0f6fc;
}

.template-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 10px;
}

.template-description {
    color: #646970;
    font-size: 0.95em;
}

.policy-preview-box {
    background: #fff;
    border: 1px solid #c3c4c7;
    border-radius: 4px;
    padding: 20px;
    max-height: 500px;
    overflow-y: auto;
}

.cookie-count {
    font-size: 2em;
    font-weight: 600;
    color: #2271b1;
    margin: 10px 0;
}

.cookie-list {
    margin: 0;
    padding-left: 20px;
}

.cookie-list li {
    margin: 5px 0;
}
</style>
