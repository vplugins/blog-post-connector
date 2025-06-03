<?php

namespace VPlugins\BlogPostConnector\Admin;

class LogSettingsTab {

    public function __construct() {
        add_action('admin_init', [$this, 'register_settings']);
    }

    public function get_title() {
        return __('Log Settings', 'blog-post-connector');
    }

    public function render_settings_fields() {
        settings_fields('sm_post_connector_settings_logs');
        do_settings_sections('blog-post-connector-logs-settings');
    }

    public function maybe_render_extra_content() {
        // No extra content for this tab
    }

    public function register_settings() {
        register_setting('sm_post_connector_settings_logs', 'sm_post_connector_enable_logs');
        register_setting('sm_post_connector_settings_logs', 'sm_post_connector_log_retention_days');

        add_settings_section(
            'sm_post_connector_logs_section',
            __('Logging Settings', 'blog-post-connector'),
            null,
            'blog-post-connector-logs-settings'
        );

        add_settings_field(
            'sm_post_connector_enable_logs',
            __('Enable Logging', 'blog-post-connector'),
            [$this, 'render_enable_logs_field'],
            'blog-post-connector-logs-settings',
            'sm_post_connector_logs_section'
        );

        add_settings_field(
            'sm_post_connector_log_retention_days',
            __('Retention Period (Days)', 'blog-post-connector'),
            [$this, 'render_log_retention_field'],
            'blog-post-connector-logs-settings',
            'sm_post_connector_logs_section'
        );
    }

    public function render_enable_logs_field() {
        $enabled = get_option('sm_post_connector_enable_logs', false);
        ?>
        <input type="checkbox" name="sm_post_connector_enable_logs" value="1" <?php checked($enabled, 1); ?>>
        <label for="sm_post_connector_enable_logs"><?php _e('Enable logging for all operations', 'blog-post-connector'); ?></label>
        <?php
    }

    public function render_log_retention_field() {
        $days = get_option('sm_post_connector_log_retention_days', 30);
        ?>
        <input type="number" name="sm_post_connector_log_retention_days" value="<?php echo esc_attr($days); ?>" min="1" class="small-text">
        <?php
    }
}