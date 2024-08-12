<?php

namespace VPlugins\SMPostConnector\Updater;

use VPlugins\SMPostConnector\Helper\Globals;

class Update {

    private $plugin_slug;
    private $plugin_file;
    private $github_user;
    private $github_repo;
    private $github_api_url;

    public function __construct() {
        $this->plugin_slug = Globals::get_plugin_slug();
        $this->plugin_file = Globals::get_plugin_file();
        $this->github_user = Globals::get_github_user();
        $this->github_repo = Globals::get_github_repo();
        $this->github_api_url = Globals::get_github_api_url();

        add_filter('pre_set_site_transient_update_plugins', [$this, 'check_for_update']);
        add_filter('plugins_api', [$this, 'plugins_api_handler'], 10, 3);
        add_filter('upgrader_post_install', [$this, 'after_install'], 10, 3);
    }

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

    public function after_install($response, $hook_extra, $result) {
        global $wp_filesystem;

        $plugin_folder = WP_PLUGIN_DIR . '/' . dirname($this->plugin_file);
        $wp_filesystem->move($result['destination'], $plugin_folder);
        $result['destination'] = $plugin_folder;

        activate_plugin($this->plugin_file);

        return $result;
    }

    private function get_latest_version() {
        $request = wp_remote_get($this->github_api_url);
        if (is_wp_error($request)) {
            return false;
        }

        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);

        return $data && isset($data->tag_name) ? ltrim($data->tag_name, 'v') : false;
    }

    private function get_latest_zip_url() {
        $request = wp_remote_get($this->github_api_url);
        if (is_wp_error($request)) {
            return false;
        }

        $body = wp_remote_retrieve_body($request);
        $data = json_decode($body);

        return $data && isset($data->zipball_url) ? $data->zipball_url : false;
    }
}