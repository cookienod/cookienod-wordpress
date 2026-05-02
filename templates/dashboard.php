<?php
/**
 * Dashboard Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$cookienod_admin = new CookieNod_Admin();
$cookienod_stats = $cookienod_admin->get_dashboard_stats();
$cookienod_compliance = new CookieNod_Compliance();
?>

<div class="wrap cookienod-dashboard">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="cookienod-dashboard-grid">
        <!-- Status Card -->
        <div class="cookienod-card cookienod-status-card">
            <h2><?php esc_html_e('Status', 'cookienod'); ?></h2>
            <div class="cookienod-status-indicator status-<?php echo esc_attr($cookienod_stats['api_status']); ?>">
                <?php
                $cookienod_status_labels = array(
                    'connected' => esc_html__('Connected', 'cookienod'),
                    'not_configured' => esc_html__('Not Configured', 'cookienod'),
                    'invalid_key' => esc_html__('Invalid API Key', 'cookienod'),
                );
                echo esc_html($cookienod_status_labels[$cookienod_stats['api_status']] ?? $cookienod_stats['api_status']);
                ?>
            </div>

            <?php if ($cookienod_stats['api_status'] === 'connected') : ?>
                <p>
                    <strong><?php esc_html_e('Site:', 'cookienod'); ?></strong>
                    <?php echo esc_html($cookienod_stats['site_name']); ?>
                </p>
                <p>
                    <strong><?php esc_html_e('Plan:', 'cookienod'); ?></strong>
                    <span class="cookienod-plan-badge plan-<?php echo esc_attr($cookienod_stats['plan']); ?>">
                        <?php echo esc_html(ucfirst($cookienod_stats['plan'])); ?>
                    </span>
                </p>
            <?php else : ?>
                <p><?php esc_html_e('Please configure your API key in Settings.', 'cookienod'); ?></p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cookienod-settings')); ?>" class="button button-primary">
                    <?php esc_html_e('Configure Settings', 'cookienod'); ?>
                </a>
            <?php endif; ?>
        </div>

        <!-- Stats Card -->
        <div class="cookienod-card">
            <h2><?php esc_html_e('Statistics', 'cookienod'); ?></h2>
            <div class="cookienod-stats-grid">
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo esc_html(number_format($cookienod_stats['cookies_detected'])); ?></span>
                    <span class="cookienod-stat-label"><?php esc_html_e('Cookies Detected', 'cookienod'); ?></span>
                </div>
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo esc_html(number_format($cookienod_stats['total_consents'])); ?></span>
                    <span class="cookienod-stat-label"><?php esc_html_e('Total Consents', 'cookienod'); ?></span>
                </div>
                <div class="cookienod-stat">
                    <span class="cookienod-stat-number"><?php echo esc_html(number_format($cookienod_stats['recent_consents'])); ?></span>
                    <span class="cookienod-stat-label"><?php esc_html_e('Last 7 Days', 'cookienod'); ?></span>
                </div>
            </div>
        </div>

        <!-- Compliance Card -->
        <div class="cookienod-card">
            <h2><?php esc_html_e('Compliance', 'cookienod'); ?></h2>
            <p><?php esc_html_e('CookieNod supports the following regulations:', 'cookienod'); ?></p>

            <ul class="cookienod-compliance-list">
                <?php foreach ($cookienod_compliance->get_all_regulations() as $cookienod_key => $cookienod_reg) : ?>
                    <li>
                        <strong><?php echo esc_html($cookienod_reg['name']); ?></strong>
                        - <?php echo esc_html($cookienod_reg['full_name']); ?>
                        <span class="cookienod-regions">(<?php echo esc_html(implode(', ', $cookienod_reg['regions'])); ?>)</span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <!-- Quick Actions Card -->
        <div class="cookienod-card">
            <h2><?php esc_html_e('Quick Actions', 'cookienod'); ?></h2>

            <div class="cookienod-actions">
                <a href="<?php echo esc_url(admin_url('admin.php?page=cookienod-settings')); ?>" class="button">
                    <?php esc_html_e('Settings', 'cookienod'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cookienod-cookies')); ?>" class="button">
                    <?php esc_html_e('Cookie Manager', 'cookienod'); ?>
                </a>
                <a href="<?php echo esc_url(admin_url('admin.php?page=cookienod-consent-log')); ?>" class="button">
                    <?php esc_html_e('Consent Log', 'cookienod'); ?>
                </a>
            </div>
        </div>
    </div>

    <!-- Integration Guide -->
    <div class="cookienod-card cookienod-guide-card">
        <h2><?php esc_html_e('Integration Guide', 'cookienod'); ?></h2>

        <ol>
            <li>
                <strong><?php esc_html_e('Get an API Key', 'cookienod'); ?></strong><br>
                <?php esc_html_e('Sign up at', 'cookienod'); ?> <a href="https://cookienod.com" target="_blank">cookienod.com</a>
                <?php esc_html_e('to get your free API key.', 'cookienod'); ?>
            </li>
            <li>
                <strong><?php esc_html_e('Configure Settings', 'cookienod'); ?></strong><br>
                <?php esc_html_e('Enter your API key in the Settings page and choose your preferred blocking mode.', 'cookienod'); ?>
            </li>
            <li>
                <strong><?php esc_html_e('Customize Banner', 'cookienod'); ?></strong><br>
                <?php esc_html_e('Select your preferred banner position and theme to match your site design.', 'cookienod'); ?>
            </li>
            <li>
                <strong><?php esc_html_e('Enable Google Consent Mode (Optional)', 'cookienod'); ?></strong><br>
                <?php esc_html_e('If you use Google Analytics or Google Tag Manager, enable Google Consent Mode for proper integration.', 'cookienod'); ?>
            </li>
        </ol>
    </div>
</div>