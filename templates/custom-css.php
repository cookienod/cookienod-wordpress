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
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file, variables scoped to file
$cookienod_css_editor = new CookieNod_Custom_CSS();
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file, variables scoped to file
$cookienod_themes = $cookienod_css_editor->get_themes();
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file, variables scoped to file
$cookienod_options = get_option('cookienod_wp_options', array());
// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- Template file, variables scoped to file
$cookienod_current_css = $cookienod_options['custom_css'] ?? '';
?>

<div class="wrap cookienod-custom-css" data-banner-theme="<?php echo esc_attr($cookienod_options['banner_theme'] ?? 'light'); ?>" data-banner-position="<?php echo esc_attr($cookienod_options['banner_position'] ?? 'bottom'); ?>">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <?php if (defined('WP_DEBUG') && WP_DEBUG) : ?>
    <!-- Debug: Custom CSS page loaded -->
    <?php endif; ?>

    <div class="cookienod-css-editor">
        <div class="cookienod-css-sidebar">
            <div class="cookienod-card">
                <h2><?php esc_html_e('Style Presets', 'cookienod'); ?></h2>

                <p><?php esc_html_e('Current banner theme (from Settings):', 'cookienod'); ?></p>
                <p>
                    <select id="current-banner-theme" disabled style="background:#f0f0f1;">
                        <option value="light" <?php selected($cookienod_options['banner_theme'] ?? 'light', 'light'); ?>>
                            <?php esc_html_e('Light', 'cookienod'); ?>
                        </option>
                        <option value="dark" <?php selected($cookienod_options['banner_theme'] ?? '', 'dark'); ?>>
                            <?php esc_html_e('Dark', 'cookienod'); ?>
                        </option>
                    </select>
                    <small class="description"><?php esc_html_e('Change in Settings page', 'cookienod'); ?></small>
                </p>

                <hr>

                <p><?php esc_html_e('Load preset CSS style:', 'cookienod'); ?></p>

                <select id="theme-selector">
                    <option value=""><?php esc_html_e('-- Select a style preset --', 'cookienod'); ?></option>
                    <?php foreach ($cookienod_themes as $cookienod_key => $cookienod_name) : ?>
                        <option value="<?php echo esc_attr($cookienod_key); ?>"><?php echo esc_html($cookienod_name); ?></option>
                    <?php endforeach; ?>
                </select>

                <p>
                    <button type="button" class="button" id="load-theme">
                        <?php esc_html_e('Load Preset', 'cookienod'); ?>
                    </button>
                </p>

                <hr>

                <h3><?php esc_html_e('Safe CSS Properties', 'cookienod'); ?></h3>

                <p class="description">
                    <?php esc_html_e('You can use these CSS properties:', 'cookienod'); ?>
                </p>

                <div class="safe-properties">
                    <code>background, color, font, margin, padding, border, border-radius,
                        box-shadow, opacity, width, height, position, display, flex, grid,
                        transform, transition, outline, text-align, cursor</code>
                </div>

                <hr>

                <h3><?php esc_html_e('Safe Selectors', 'cookienod'); ?></h3>

                <div class="safe-selectors">
                    <code>.cs-banner, .cs-banner.cs-dark, .cs-banner.cs-light,
.cs-settings, .cs-settings.cs-dark, .cs-settings.cs-light,
#cs-consent-banner, #cs-detailed-settings,
#cs-reject-btn, #cs-customize-btn, #cs-accept-btn, #cs-save-prefs,
#cs-close-banner, #cs-close-settings, #cs-retrigger-btn,
#cs-cat-necessary, #cs-cat-functional, #cs-cat-analytics, #cs-cat-marketing,
.cs-banner-title, .cs-banner-description, .cs-settings-title</code>
                </div>
            </div>

            <div class="cookienod-card">
                <h2><?php esc_html_e('Actions', 'cookienod'); ?></h2>

                <p>
                    <button type="button" class="button" id="preview-css">
                        <?php esc_html_e('Preview Changes', 'cookienod'); ?>
                    </button>
                </p>

                <p>
                    <button type="button" class="button" id="validate-css">
                        <?php esc_html_e('Validate CSS', 'cookienod'); ?>
                    </button>
                </p>

                <p>
                    <button type="button" class="button button-link-delete" id="reset-css">
                        <?php esc_html_e('Reset to Default', 'cookienod'); ?>
                    </button>
                </p>
            </div>
        </div>

        <div class="cookienod-css-main">
            <div class="cookienod-card">
                <h2><?php esc_html_e('Custom CSS', 'cookienod'); ?></h2>

                <form method="post" action="options.php">
                    <?php settings_fields('cookienod_wp_options'); ?>

                    <div class="css-editor-container">
                        <textarea name="cookienod_wp_options[custom_css]" id="custom-css-editor"
                                  class="large-text code" rows="25"
                                  placeholder="/* Add your custom CSS here */"><?php echo esc_textarea($cookienod_current_css); ?></textarea>
                    </div>

                    <p class="description">
                        <?php esc_html_e('Only safe CSS properties are allowed. JavaScript and potentially dangerous CSS will be automatically removed.', 'cookienod'); ?>
                    </p>

                    <p id="css-validation-status"></p>

                    <?php submit_button(__('Save CSS', 'cookienod')); ?>
                </form>
            </div>

            <div class="cookienod-card">
                <h2><?php esc_html_e('Live Preview', 'cookienod'); ?></h2>

                <p><?php esc_html_e('Preview how your banner will look:', 'cookienod'); ?></p>
                <p class="description"><?php printf(
                    /* translators: %s: Banner theme name */
                    esc_html__('Base banner theme from Settings: %s. Custom CSS presets are applied as overrides on top of that theme.', 'cookienod'),
                    esc_html(ucfirst($cookienod_options['banner_theme'] ?? 'light'))
                ); ?></p>

                <iframe id="css-preview-frame" src=""></iframe>
            </div>
        </div>
    </div>
</div>

<?php
// Enqueue custom CSS editor styles
wp_enqueue_style(
    'cookienod-custom-css-editor',
    COOKIENOD_PLUGIN_URL . 'assets/css/custom-css-editor.css',
    array(),
    COOKIENOD_VERSION
);
?>
