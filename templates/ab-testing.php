<?php
/**
 * A/B Testing Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

$ab_testing = new CookieNod_AB_Testing();
$tests = $ab_testing->get_all_tests();
$active_test = null;

foreach ($tests as $test) {
    if ($test['status'] === 'active') {
        $active_test = $test;
        break;
    }
}
?>

<div class="wrap cookienod-ab-testing">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <div class="cookienod-card">
        <div class="nav-tab-wrapper">
            <a href="#active-tests" class="nav-tab nav-tab-active"><?php _e('Active Test', 'cookienod'); ?></a>
            <a href="#all-tests" class="nav-tab"><?php _e('All Tests', 'cookienod'); ?></a>
            <a href="#create-test" class="nav-tab"><?php _e('Create Test', 'cookienod'); ?></a>
        </div>

        <!-- Active Test -->
        <div id="active-tests" class="cookienod-tab-content" data-test-id="<?php echo $active_test ? esc_attr($active_test['id']) : ''; ?>">
            <?php if ($active_test) : ?>
                <?php $stats = $ab_testing->get_test_stats($active_test['id']); ?>
                <h2><?php echo esc_html($active_test['name']); ?></h2>

                <div class="cookienod-ab-stats" data-test-id="<?php echo esc_attr($active_test['id']); ?>">
                    <?php foreach (json_decode($active_test['variants'], true) as $variant) : ?>
                        <?php $variant_stats = $stats[$variant['id']] ?? array(
                            'impressions' => 0,
                            'accept_all' => 0,
                            'reject_all' => 0,
                            'accept_rate' => 0,
                        ); ?>

                        <div class="cookienod-ab-variant">
                            <h3><?php echo esc_html($variant['name']); ?></h3>
                            <div class="cookienod-ab-metrics">
                                <div class="metric">
                                    <span class="metric-value"><?php echo number_format($variant_stats['impressions']); ?></span>
                                    <span class="metric-label"><?php _e('Impressions', 'cookienod'); ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-value"><?php echo number_format($variant_stats['accept_all']); ?></span>
                                    <span class="metric-label"><?php _e('Accepts', 'cookienod'); ?></span>
                                </div>
                                <div class="metric">
                                    <span class="metric-value"><?php echo $variant_stats['accept_rate']; ?>%</span>
                                    <span class="metric-label"><?php _e('Accept Rate', 'cookienod'); ?></span>
                                </div>
                            </div>

                            <div class="cookienod-ab-actions">
                                <button class="button button-primary set-winner" data-variant="<?php echo esc_attr($variant['id']); ?>">
                                    <?php _e('Set as Winner', 'cookienod'); ?>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <p>
                    <strong><?php _e('Start Date:', 'cookienod'); ?></strong>
                    <?php echo esc_html($active_test['start_date']); ?>
                </p>

                <button class="button" id="stop-test"><?php _e('Stop Test', 'cookienod'); ?></button>

            <?php else : ?>
                <p><?php _e('No active A/B test. Create a new test to start optimizing your consent banner.', 'cookienod'); ?></p>
                <a href="#create-test" class="button button-primary"><?php _e('Create Test', 'cookienod'); ?></a>
            <?php endif; ?>
        </div>

        <!-- All Tests -->
        <div id="all-tests" class="cookienod-tab-content" style="display:none;">
            <?php if ($tests) : ?>
                <table class="wp-list-table widefat striped">
                    <thead>
                        <tr>
                            <th><?php _e('Name', 'cookienod'); ?></th>
                            <th><?php _e('Status', 'cookienod'); ?></th>
                            <th><?php _e('Variants', 'cookienod'); ?></th>
                            <th><?php _e('Start Date', 'cookienod'); ?></th>
                            <th><?php _e('Winner', 'cookienod'); ?></th>
                            <th><?php _e('Actions', 'cookienod'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tests as $test) : ?>
                            <?php $variants = json_decode($test['variants'], true); ?>
                            <tr>
                                <td><?php echo esc_html($test['name']); ?></td>
                                <td><span class="status-badge status-<?php echo esc_attr($test['status']); ?>">
                                    <?php echo esc_html(ucfirst($test['status'])); ?></span></td>
                                <td><?php echo count($variants); ?></td>
                                <td><?php echo $test['start_date'] ? esc_html($test['start_date']) : '-'; ?></td>
                                <td>
                                    <?php if ($test['winner']) : ?>
                                        <?php foreach ($variants as $v) {
                                            if ($v['id'] == $test['winner']) {
                                                echo esc_html($v['name']);
                                                break;
                                            }
                                        } ?>
                                    <?php else : ?>
                                        -
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($test['status'] === 'draft') : ?>
                                        <button class="button button-primary start-test-btn" data-test="<?php echo esc_attr($test['id']); ?>">
                                            <?php _e('Start Test', 'cookienod'); ?>
                                        </button>
                                    <?php elseif ($test['status'] === 'active') : ?>
                                        <button class="button stop-test-btn" data-test="<?php echo esc_attr($test['id']); ?>">
                                            <?php _e('Stop', 'cookienod'); ?>
                                        </button>
                                    <?php else : ?>
                                        <span class="button button-secondary" disabled><?php _e('Completed', 'cookienod'); ?></span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p><?php _e('No tests created yet.', 'cookienod'); ?></p>
            <?php endif; ?>
        </div>

        <!-- Create Test -->
        <div id="create-test" class="cookienod-tab-content" style="display:none;">
            <h2><?php _e('Create New A/B Test', 'cookienod'); ?></h2>

            <form id="create-test-form">
                <table class="form-table">
                    <tr>
                        <th><label for="test-name"><?php _e('Test Name', 'cookienod'); ?></label></th>
                        <td>
                            <input type="text" id="test-name" class="regular-text" required />
                            <p class="description"><?php _e('Give your test a descriptive name', 'cookienod'); ?></p>
                        </td>
                    </tr>
                </table>

                <h3><?php _e('Variants', 'cookienod'); ?></h3>

                <div id="test-variants">
                    <div class="test-variant" data-id="1">
                        <h4><?php _e('Variant A (Control)', 'cookienod'); ?></h4>
                        <label><?php _e('Name', 'cookienod'); ?></label>
                        <input type="text" class="variant-name" value="<?php _e('Original', 'cookienod'); ?>" />

                        <label><?php _e('Banner Position', 'cookienod'); ?></label>
                        <select class="variant-position">
                            <option value="bottom"><?php _e('Bottom', 'cookienod'); ?></option>
                            <option value="top"><?php _e('Top', 'cookienod'); ?></option>
                            <option value="center"><?php _e('Center', 'cookienod'); ?></option>
                        </select>

                        <label><?php _e('Accept Button Color', 'cookienod'); ?></label>
                        <input type="color" class="variant-primary-color" value="#10b981" />
                    </div>

                    <div class="test-variant" data-id="2">
                        <h4><?php _e('Variant B', 'cookienod'); ?></h4>

                        <label><?php _e('Name', 'cookienod'); ?></label>
                        <input type="text" class="variant-name" value="<?php _e('Test Variant', 'cookienod'); ?>" />

                        <label><?php _e('Banner Position', 'cookienod'); ?></label>
                        <select class="variant-position">
                            <option value="bottom"><?php _e('Bottom', 'cookienod'); ?></option>
                            <option value="top"><?php _e('Top', 'cookienod'); ?></option>
                            <option value="center" selected><?php _e('Center', 'cookienod'); ?></option>
                        </select>

                        <label><?php _e('Accept Button Color', 'cookienod'); ?></label>
                        <input type="color" class="variant-primary-color" value="#3b82f6" />
                    </div>
                </div>

                <p>
                    <button type="button" class="button" id="add-variant"><?php _e('Add Variant', 'cookienod'); ?></button>
                </p>

                <h3><?php _e('Traffic Split', 'cookienod'); ?></h3>

                <div id="traffic-split">
                    <input type="range" min="0" max="100" value="50" class="split-slider" />
                    <span class="split-display">50% / 50%</span>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary"><?php _e('Create Test', 'cookienod'); ?></button>
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
