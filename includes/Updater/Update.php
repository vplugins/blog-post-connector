<?php

namespace VPlugins\BlogPostConnector\Updater;

use VPlugins\BlogPostConnector\Helper\Globals;

/**
 * Class Update
 *
 * Handles plugin updates by checking for new versions, providing plugin information, and managing post-installation actions.
 */
class Update {

    private $plugin_slug;
    private $plugin_file;
    private $github_user;
    private $github_repo;
    private $github_api_url;

    /**
     * Update constructor.
     *
     * Initializes the Update class and sets up necessary properties. Adds filters for plugin update checks,
     * plugin information, and post-installation actions.
     */
    public function __construct() {
        $this->plugin_slug = Globals::get_plugin_slug();
        $this->plugin_file = Globals::get_plugin_file();
        $this->github_user = Globals::get_github_user();
        $this->github_repo = Globals::get_github_repo();
        $this->github_api_url = Globals::get_github_api_url();

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update'], 10, 1);
        add_filter('plugins_api', [$this, 'plugins_api_handler'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
    }

    /**
     * Checks for updates by comparing the current version with the latest version available on GitHub.
     *
     * @param object $transient The transient object containing plugin update information.
     * @return object The updated transient object with the new version information if available.
     */
    public function check_for_update($transient) {
        if (empty($transient->checked)) {
            return $transient;
        }

        $remote_version = $this->get_latest_version();
        if (!$remote_version) {
            return $transient;
        }

        $current_version = Globals::get_version();

        if (version_compare($current_version, $remote_version, '<')) {
            $transient->response[$this->plugin_file] = (object) [
                'slug' => $this->plugin_slug,
                'plugin' => $this->plugin_file,
                'new_version' => $remote_version,
                'package' => $this->get_latest_zip_url(),
                'tested' => get_bloginfo('version'),
                'compatibility' => new \stdClass(),
            ];
        }

        return $transient;
    }

    /**
     * Provides plugin information for the plugin details page in the admin area.
     *
     * @param object $result The current plugin information result.
     * @param string $action The action being performed (e.g., 'plugin_information').
     * @param object $args Arguments passed to the API call.
     * @return object The updated plugin information.
     */
    public function plugins_api_handler($result, $action, $args) {
        if ($action !== 'plugin_information' || $args->slug !== $this->plugin_slug) {
            return $result;
        }

        $response = new \stdClass();
        $response->name = ucfirst($this->plugin_slug);
        $response->slug = $this->plugin_slug;
        $response->version = $this->get_latest_version();
        $response->tested = get_bloginfo('version');
        $response->requires = '5.0';
        $response->download_link = $this->get_latest_zip_url();

        return $response;
    }

    /**
     * Handles post-installation tasks, such as moving the plugin folder and activating the plugin.
     *
     * @param array $response The response from the installation process.
     * @param array $hook_extra Extra data provided by the upgrader.
     * @param array $result The result of the installation process.
     * @return array The updated result array.
     */
    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($this->plugin_file);
        $wp_filesystem->move($result['destination'], $plugin_folder);
        $result['destination'] = $plugin_folder;

        activate_plugin($this->plugin_file);

        return $result;
    }

    /**
     * Retrieves the latest plugin version from GitHub.
     *
     * @return string|false The latest version number or false if an error occurred.
     */
    private function get_latest_version() {
        $request = wp_remote_get($this->github_api_url);
        if (is_wp_error($request)) {
            return false;
        }

        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);

        return $data && isset($data->tag_name) ? ltrim($data->tag_name, 'v') : false;
    }

    /**
     * Retrieves the URL for the latest plugin zip file from GitHub.
     *
     * @return string|false The URL of the latest zip file or false if an error occurred.
     */
    private function get_latest_zip_url() {
        $request = wp_remote_get($this->github_api_url);
        if (is_wp_error($request)) {
            return false;
        }

        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);

        $latest_asset = $data->assets;
        $latest_zip = $latest_asset[0]->browser_download_url;

        return $data && isset($latest_zip) ? $latest_zip : false;
    }
}