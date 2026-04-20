<?php
/**
 * Custom CSS Template
 *
 * @package Cookienod
 */

if (!defined('ABSPATH')) {
    exit;
}

// CSS editor instance is already created in init_components()
$css_editor = new CookieNod_Custom_CSS();
$themes = $css_editor->get_themes();
$options = get_option('cookienod_wp_options', array());
$current_css = $options['custom_css'] ?? '';
?>

<div class="wrap cookienod-custom-css" data-banner-theme="<?php echo esc_attr($options['banner_theme'] ?? 'light'); ?>">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if (defined('WP_DEBUG') && WP_DEBUG) : ?>
    <!-- Debug: Custom CSS page loaded -->
    <?php endif; ?>

    <div class="cookienod-css-editor">
        <div class="cookienod-css-sidebar">
            <div class="cookienod-card">
                <h2><?php _e('Style Presets', 'cookienod'); ?></h2>

                <p><?php _e('Load preset CSS to customize your current banner theme:', 'cookienod'); ?></p>

                <select id="theme-selector">
                    <option value=""><?php _e('-- Select a theme --', 'cookienod'); ?></option>
                    <?php foreach ($themes as $key => $name) : ?>
                        <option value="<?php echo esc_attr($key); ?>"><?php echo esc_html($name); ?></option>
                    <?php endforeach; ?>
                </select>

                <p>
                    <button type="button" class="button" id="load-theme">
                        <?php _e('Load Preset', 'cookienod'); ?>
                    </button>
                </p>

                <hr>

                <h3><?php _e('Safe CSS Properties', 'cookienod'); ?></h3>

                <p class="description">
                    <?php _e('You can use these CSS properties:', 'cookienod'); ?>
                </p>

                <div class="safe-properties">
                    <code>background, color, font, margin, padding, border, border-radius,
                        box-shadow, opacity, width, height, position, display, flex, grid,
                        transform, transition, outline, text-align, cursor</code>
                </div>

                <hr>

                <h3><?php _e('Safe Selectors', 'cookienod'); ?></h3>

                <div class="safe-selectors">
                    <code>#cs-consent-banner, #cs-detailed-settings, #cs-retrigger-btn,
                        .cs-banner-content, .cs-banner-actions, .cs-btn, .cs-btn-primary</code>
                </div>
            </div>

            <div class="cookienod-card">
                <h2><?php _e('Actions', 'cookienod'); ?></h2>

                <p>
                    <button type="button" class="button" id="preview-css">
                        <?php _e('Preview Changes', 'cookienod'); ?>
                    </button>
                </p>

                <p>
                    <button type="button" class="button" id="validate-css">
                        <?php _e('Validate CSS', 'cookienod'); ?>
                    </button>
                </p>

                <p>
                    <button type="button" class="button button-link-delete" id="reset-css">
                        <?php _e('Reset to Default', 'cookienod'); ?>
                    </button>
                </p>
            </div>
        </div>

        <div class="cookienod-css-main">
            <div class="cookienod-card">
                <h2><?php _e('Custom CSS', 'cookienod'); ?></h2>

                <form method="post" action="options.php">
                    <?php settings_fields('cookienod_wp_options'); ?>

                    <div class="css-editor-container">
                        <textarea name="cookienod_wp_options[custom_css]" id="custom-css-editor"
                                  class="large-text code" rows="25"
                                  placeholder="/* Add your custom CSS here */"><?php echo esc_textarea($current_css); ?></textarea>
                    </div>

                    <p class="description">
                        <?php _e('Only safe CSS properties are allowed. JavaScript and potentially dangerous CSS will be automatically removed.', 'cookienod'); ?>
                    </p>

                    <p id="css-validation-status"></p>

                    <?php submit_button(__('Save CSS', 'cookienod')); ?>
                </form>
            </div>

            <div class="cookienod-card">
                <h2><?php _e('Live Preview', 'cookienod'); ?></h2>

                <p><?php _e('Preview how your banner will look:', 'cookienod'); ?></p>
                <p class="description"><?php printf(esc_html__('Base banner theme from Settings: %s. Custom CSS presets are applied as overrides on top of that theme.', 'cookienod'), esc_html(ucfirst($options['banner_theme'] ?? 'light'))); ?></p>

                <iframe id="css-preview-frame" src=""></iframe>
            </div>
        </div>
    </div>
</div>

<style>
.cookienod-custom-css {
    --cookienod-surface: #ffffff;
    --cookienod-surface-muted: #f0f0f1;
    --cookienod-border: #c3c4c7;
    --cookienod-text: #1d2327;
    --cookienod-text-muted: #646970;
    --cookienod-success-bg: #d4edda;
    --cookienod-success-text: #155724;
    --cookienod-error-bg: #f8d7da;
    --cookienod-error-text: #721c24;
}

body.admin-color-midnight .cookienod-custom-css,
body.admin-color-ectoplasm .cookienod-custom-css {
    --cookienod-surface: #23282d;
    --cookienod-surface-muted: #2c3338;
    --cookienod-border: #3c434a;
    --cookienod-text: #f0f0f1;
    --cookienod-text-muted: #c3c4c7;
    --cookienod-success-bg: #153b2a;
    --cookienod-success-text: #9ee2b8;
    --cookienod-error-bg: #4a1f27;
    --cookienod-error-text: #ffb3bf;
}

.cookienod-css-editor {
    display: grid;
    grid-template-columns: 300px 1fr;
    gap: 20px;
    margin-top: 20px;
}

.cookienod-custom-css .cookienod-card {
    background: var(--cookienod-surface);
    border-color: var(--cookienod-border);
    color: var(--cookienod-text);
}

.cookienod-custom-css .cookienod-card h2,
.cookienod-custom-css .cookienod-card h3,
.cookienod-custom-css .cookienod-card p,
.cookienod-custom-css .cookienod-card .description,
.cookienod-custom-css label {
    color: var(--cookienod-text);
}

.cookienod-custom-css .cookienod-card hr {
    border-color: var(--cookienod-border);
}

.cookienod-custom-css select,
.cookienod-custom-css textarea {
    background: var(--cookienod-surface);
    border-color: var(--cookienod-border);
    color: var(--cookienod-text);
}

.cookienod-custom-css select::placeholder,
.cookienod-custom-css textarea::placeholder,
.cookienod-custom-css .description {
    color: var(--cookienod-text-muted);
}

.cookienod-css-sidebar .cookienod-card {
    margin-bottom: 20px;
}

.safe-properties,
.safe-selectors {
    background: var(--cookienod-surface-muted);
    padding: 10px;
    border-radius: 4px;
    font-size: 0.9em;
    word-break: break-word;
    color: var(--cookienod-text);
}

.css-editor-container {
    position: relative;
}

#custom-css-editor {
    font-family: 'Courier New', Consolas, Monaco, monospace;
    font-size: 14px;
    line-height: 1.5;
}

#css-preview-frame {
    width: 100%;
    height: 600px;
    border: 1px solid var(--cookienod-border);
    border-radius: 4px;
    background: var(--cookienod-surface);
}

#css-validation-status {
    margin: 10px 0;
    padding: 10px;
    border-radius: 4px;
    display: none;
}

#css-validation-status.success {
    display: block;
    background: var(--cookienod-success-bg);
    color: var(--cookienod-success-text);
}

#css-validation-status.error {
    display: block;
    background: var(--cookienod-error-bg);
    color: var(--cookienod-error-text);
}

@media screen and (max-width: 782px) {
    .cookienod-css-editor {
        grid-template-columns: 1fr;
    }
}
</style>
