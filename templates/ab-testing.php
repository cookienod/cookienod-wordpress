<?php
/**
 * A/B Testing Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$cookienod_ab_testing = new CookieNod_AB_Testing();
$cookienod_tests = $cookienod_ab_testing->get_all_tests();
$cookienod_active_test = null;

foreach ($cookienod_tests as $cookienod_test) {
    if ($cookienod_test['status'] === 'active') {
        $cookienod_active_test = $cookienod_test;
        break;
    }
}
?>

<div class="wrap cookienod-ab-testing">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="cookienod-card">
        <div class="nav-tab-wrapper">
            <a href="#active-tests" class="nav-tab nav-tab-active"><?php esc_html_e('Active Test', 'cookienod'); ?></a>
            <a href="#all-tests" class="nav-tab"><?php esc_html_e('All Tests', 'cookienod'); ?></a>
            <a href="#create-test" class="nav-tab"><?php esc_html_e('Create Test', 'cookienod'); ?></a>
        </div>

        <!-- Active Test -->
        <div id="active-tests" class="cookienod-tab-content" data-test-id="<?php echo $cookienod_active_test ? esc_attr($cookienod_active_test['id']) : ''; ?>">
            <?php if ($cookienod_active_test) : ?>
                <?php $cookienod_stats = $cookienod_ab_testing->get_test_stats($cookienod_active_test['id']); ?>
                <h2><?php echo esc_html($cookienod_active_test['name']); ?></h2>

                <div class="cookienod-ab-stats" data-test-id="<?php echo esc_attr($cookienod_active_test['id']); ?>">
                    <?php foreach (json_decode($cookienod_active_test['variants'], true) as $cookienod_variant) : ?>
                        <?php $cookienod_variant_stats = $cookienod_stats[$cookienod_variant['id']] ?? array(
                            'impressions' => 0,
                            'accept_all' => 0,
                            'reject_all' => 0,
                            'accept_rate' => 0,
                        ); ?>

                        <div class="cookienod-ab-variant">
                            <h3><?php echo esc_html($cookienod_variant['name']); ?></h3>
                            <div class="cookienod-ab-metrics">
                                <div class="metric">
                                    <span class="metric-value"><?php echo esc_html(number_format($cookienod_variant_stats['impressions'])); ?></span>
                                    <span class="metric-label"><?php esc_html_e('Impressions', 'cookienod'); ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-value"><?php echo esc_html(number_format($cookienod_variant_stats['accept_all'])); ?></span>
                                    <span class="metric-label"><?php esc_html_e('Accepts', 'cookienod'); ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-value"><?php echo esc_html(number_format($cookienod_variant_stats['accept_rate'])); ?>%</span>
                                    <span class="metric-label"><?php esc_html_e('Accept Rate', 'cookienod'); ?></span>
                                </div>
                            </div>

                            <div class="cookienod-ab-actions">
                                <button class="button button-primary set-winner" data-variant="<?php echo esc_attr($cookienod_variant['id']); ?>">
                                    <?php esc_html_e('Set as Winner', 'cookienod'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p>
                    <strong><?php esc_html_e('Start Date:', 'cookienod'); ?></strong>
                    <?php echo esc_html($cookienod_active_test['start_date']); ?>
                </p>

                <button class="button" id="stop-test"><?php esc_html_e('Stop Test', 'cookienod'); ?></button>

            <?php else : ?>
                <p><?php esc_html_e('No active A/B test. Create a new test to start optimizing your consent banner.', 'cookienod'); ?></p>
                <a href="#create-test" class="button button-primary"><?php esc_html_e('Create Test', 'cookienod'); ?></a>
            <?php endif; ?>
        </div>

        <!-- All Tests -->
        <div id="all-tests" class="cookienod-tab-content" style="display:none;">
            <?php if ($cookienod_tests) : ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Name', 'cookienod'); ?></th>
                            <th><?php esc_html_e('Status', 'cookienod'); ?></th>
                            <th><?php esc_html_e('Variants', 'cookienod'); ?></th>
                            <th><?php esc_html_e('Start Date', 'cookienod'); ?></th>
                            <th><?php esc_html_e('Winner', 'cookienod'); ?></th>
                            <th><?php esc_html_e('Actions', 'cookienod'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cookienod_tests as $cookienod_test) : ?>
                            <?php $cookienod_variants = json_decode($cookienod_test['variants'], true); ?>
                            <tr>
                                <td><?php echo esc_html($cookienod_test['name']); ?></td>
                                <td><span class="status-badge status-<?php echo esc_attr($cookienod_test['status']); ?>">
                                    <?php echo esc_html(ucfirst($cookienod_test['status'])); ?></span></td>
                                <td><?php echo count($cookienod_variants); ?></td>
                                <td><?php echo $cookienod_test['start_date'] ? esc_html($cookienod_test['start_date']) : '-'; ?></td>
                                <td>
                                    <?php if ($cookienod_test['winner']) : ?>
                                        <?php foreach ($cookienod_variants as $cookienod_v) {
                                            if ($cookienod_v['id'] == $cookienod_test['winner']) {
                                                echo esc_html($cookienod_v['name']);
                                                break;
                                            }
                                        } ?>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($cookienod_test['status'] === 'draft') : ?>
                                        <button class="button button-primary start-test-btn" data-test="<?php echo esc_attr($cookienod_test['id']); ?>">
                                            <?php esc_html_e('Start Test', 'cookienod'); ?>
                                        </button>
                                    <?php elseif ($cookienod_test['status'] === 'active') : ?>
                                        <button class="button stop-test-btn" data-test="<?php echo esc_attr($cookienod_test['id']); ?>">
                                            <?php esc_html_e('Stop', 'cookienod'); ?>
                                        </button>
                                    <?php else : ?>
                                        <span class="button button-secondary" disabled><?php esc_html_e('Completed', 'cookienod'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php esc_html_e('No tests created yet.', 'cookienod'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Create Test -->
        <div id="create-test" class="cookienod-tab-content" style="display:none;">
            <h2><?php esc_html_e('Create New A/B Test', 'cookienod'); ?></h2>

            <form id="create-test-form">
                <table class="form-table">
                    <tr>
                        <th><label for="test-name"><?php esc_html_e('Test Name', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="test-name" class="regular-text" required />
                            <p class="description"><?php esc_html_e('Give your test a descriptive name', 'cookienod'); ?></p>
                        </td>
                    </tr>
                </table>

                <h3><?php esc_html_e('Variants', 'cookienod'); ?></h3>

                <div id="test-variants">
                    <div class="test-variant" data-id="1">
                        <h4><?php esc_html_e('Variant A (Control)', 'cookienod'); ?></h4>
                        <label><?php esc_html_e('Name', 'cookienod'); ?></label>
                        <input type="text" class="variant-name" value="<?php echo esc_html_e('Original', 'cookienod'); ?>" />

                        <label><?php esc_html_e('Banner Position', 'cookienod'); ?></label>
                        <select class="variant-position">
                            <option value="bottom"><?php esc_html_e('Bottom', 'cookienod'); ?></option>
                            <option value="top"><?php esc_html_e('Top', 'cookienod'); ?></option>
                            <option value="center"><?php esc_html_e('Center', 'cookienod'); ?></option>
                        </select>

                        <label><?php esc_html_e('Accept Button Color', 'cookienod'); ?></label>
                        <input type="color" class="variant-primary-color" value="#10b981" />
                    </div>

                    <div class="test-variant" data-id="2">
                        <h4><?php esc_html_e('Variant B', 'cookienod'); ?></h4>

                        <label><?php esc_html_e('Name', 'cookienod'); ?></label>
                        <input type="text" class="variant-name" value="<?php echo esc_html_e('Test Variant', 'cookienod'); ?>" />

                        <label><?php esc_html_e('Banner Position', 'cookienod'); ?></label>
                        <select class="variant-position">
                            <option value="bottom"><?php esc_html_e('Bottom', 'cookienod'); ?></option>
                            <option value="top"><?php esc_html_e('Top', 'cookienod'); ?></option>
                            <option value="center" selected><?php esc_html_e('Center', 'cookienod'); ?></option>
                        </select>

                        <label><?php esc_html_e('Accept Button Color', 'cookienod'); ?></label>
                        <input type="color" class="variant-primary-color" value="#3b82f6" />
                    </div>
                </div>

                <p>
                    <button type="button" class="button" id="add-variant"><?php esc_html_e('Add Variant', 'cookienod'); ?></button>
                </p>

                <h3><?php esc_html_e('Traffic Split', 'cookienod'); ?></h3>

                <div id="traffic-split">
                    <input type="range" min="0" max="100" value="50" class="split-slider" />
                    <span class="split-display">50% / 50%</span>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php esc_html_e('Create Test', 'cookienod'); ?></button>
                </p>
            </form>
        </div>
    </div>
</div>

<style>
.cookienod-ab-variant {
    background: #f5f5f5;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 4px;
}

.cookienod-ab-metrics {
    display: flex;
    gap: 30px;
    margin: 20px 0;
}

.metric {
    text-align: center;
}

.metric-value {
    display: block;
    font-size: 2em;
    font-weight: 600;
    color: #2271b1;
}

.metric-label {
    display: block;
    color: #646970;
    margin-top: 5px;
}

.test-variant {
    background: #f9f9f9;
    padding: 20px;
    margin-bottom: 15px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.test-variant label {
    display: block;
    margin: 15px 0 5px;
    font-weight: 500;
}

.test-variant input,
.test-variant select {
    width: 100%;
}

#traffic-split {
    display: flex;
    align-items: center;
    gap: 20px;
    margin: 20px 0;
}

.split-slider {
    flex: 1;
}

.split-display {
    font-weight: 600;
    font-size: 1.2em;
}

.status-badge {
    display: inline-block;
    padding: 4px 12px;
    border-radius: 12px;
    font-size: 0.85em;
}

.status-badge.status-active {
    background: #d4edda;
    color: #155724;
}

.status-badge.status-draft {
    background: #e2e3e5;
    color: #383d41;
}

.status-badge.status-completed {
    background: #cce5ff;
    color: #004085;
}
</style>
