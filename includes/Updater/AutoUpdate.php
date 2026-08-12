<?php

namespace VPlugins\BlogPostConnector\Updater;

use VPlugins\BlogPostConnector\Helper\Globals;

/**
 * Class AutoUpdate
 *
 * Handles auto-updates for the plugin by enabling auto-updates and toggling the auto-update status.
 */
class AutoUpdate {

    private $plugin_slug;
    private $plugin_file;

    public static function init() {
        new self(); // Initialize the class
    }

    public function __construct() {
        $this->plugin_slug = Globals::get_plugin_slug();
        $this->plugin_file = Globals::get_plugin_file();

        add_filter('auto_update_plugin', [$this, 'enableAutoUpdate'], 10, 2);
        add_action('admin_init', [$this, 'handleAutoUpdateToggle']);
        add_filter('plugin_auto_update_setting_html', [__CLASS__, 'modifyAutoUpdateUI'], 10, 2);
    }

    /**
     * Enable auto-update for the plugin.
     *
     * @param bool   $update Whether to auto-update the plugin.
     * @param object $item   Plugin update data.
     * @return bool
     */
    public function enableAutoUpdate($update, $item) {
        if (isset($item->slug) && $item->slug === $this->plugin_slug) {
            // Get the list of plugins with auto-updates enabled
            $auto_update_plugins = get_option('auto_update_plugins', []);
            // Enable auto-update if this plugin is in the list
            return in_array($this->plugin_slug, $auto_update_plugins, true);
        }
        return $update;
    }

    /**
     * Handles toggling auto-updates via the admin panel.
     */
    public function handleAutoUpdateToggle() {
        if (!is_admin() || !current_user_can('manage_options')) {
            return;
        }

        // Check if the request is valid
        if (!isset($_GET['action'], $_GET['plugin']) || !in_array($_GET['action'], ['enable-auto-update', 'disable-auto-update'])) {
            return;
        }

        check_admin_referer('toggle_auto_update_' . $this->plugin_slug);

        $plugin_slug = sanitize_text_field($_GET['plugin']);
        // Get the current list of plugins with auto-updates enabled
        $auto_update_plugins = get_option('auto_update_plugins', []);

        // Toggle auto-update status for this plugin
        if ($plugin_slug === $this->plugin_slug) {
            if (in_array($plugin_slug, $auto_update_plugins, true)) {
                // Remove from auto-update list
                $auto_update_plugins = array_filter($auto_update_plugins, function ($plugin) use ($plugin_slug) {
                    return $plugin !== $plugin_slug;
                });
            } else {
                // Add to auto-update list
                $auto_update_plugins[] = $plugin_slug;
            }

            // Update the option with the new list
            update_option('auto_update_plugins', array_values($auto_update_plugins));
        }

        wp_safe_redirect(admin_url('plugins.php'));
        exit;
    }

    /**
     * Retrieves the auto-update status for the plugin.
     *
     * @return bool
     */
    public function isAutoUpdateEnabled() {
        // Get the list of plugins with auto-updates enabled
        $auto_update_plugins = get_option('auto_update_plugins', []);
        return in_array($this->plugin_slug, $auto_update_plugins, true);
    }

    /**
     * Modify the Auto-Update UI button in the Plugins page.
     */
    public static function modifyAutoUpdateUI($html, $plugin_file) {
        if ($plugin_file === 'blog-post-connector/blog-post-connector.php') {
            // Fetch the current list of plugins with auto-updates enabled
            $auto_update_plugins = get_option('auto_update_plugins', []);
            $status = in_array('blog-post-connector', $auto_update_plugins, true) ? 'enabled' : 'disabled';

            // Determine the action and label based on the current status
            $action = ($status === 'enabled') ? 'disable' : 'enable';
            $label = ($status === 'enabled') ? 'Disable auto-updates' : 'Enable auto-updates';

            // Generate nonce and URL for toggling auto-updates
            $nonce = wp_create_nonce('toggle_auto_update_' . 'blog-post-connector');
            $url = admin_url("plugins.php?action={$action}-auto-update&plugin={$plugin_file}&_wpnonce={$nonce}");

            // Return the modified action link HTML
            return "<a href='{$url}' class='toggle-auto-update' data-wp-action='{$action}' role='button'>
                        <span class='dashicons dashicons-update spin hidden' aria-hidden='true'></span>
                        <span class='label'>{$label}</span>
                    </a>";
        }
        return $html;
    }
}
