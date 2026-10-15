<?php
/**
 * Plugin Name: Blog Post Connector
 * Description: A plugin to publish blogs to your WordPress website.
 * Version: 1.0.9
 * Author: Website Pro, a WordPress hosting platform.
 * Text Domain: blog-post-connector
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Autoload the classes using Composer
require_once __DIR__ . '/vendor/autoload.php';

use VPlugins\BlogPostConnector\Auth\TokenManager;
use VPlugins\BlogPostConnector\Admin\SettingsPageController;
use VPlugins\BlogPostConnector\Updater\Update;
use VPlugins\BlogPostConnector\Webhook\Webhook;
use VPlugins\BlogPostConnector\Endpoints\{
    CreatePost,
    DeletePost,
    UpdatePost,
    GetPost,
    GetAuthors,
    GetCategories,
    GetTags,
    Status,
    WebhookControl
};
use VPlugins\BlogPostConnector\Middleware\LoggerMiddleware;
use VPlugins\BlogPostConnector\Helper\Globals;

// Registered before the subsite check below so activating on a subsite is refused, not silently allowed.
register_activation_hook(__FILE__, 'blog_post_connector_activate');

/**
 * Refuses activation outside the main site of a multisite network, then creates the logs table.
 *
 * @param bool $network_wide Whether the plugin is being network activated.
 */
function blog_post_connector_activate($network_wide = false) {
    $error = Globals::get_activation_error($network_wide);
    if ($error) {
        wp_die(esc_html($error), esc_html__('Plugin activation error', 'blog-post-connector'), ['back_link' => true]);
    }

    LoggerMiddleware::install();
}

// On a multisite network the plugin runs on the main site only.
if (!Globals::is_supported_site()) {
    return;
}

/**
 * Endpoint Registry Class
 * 
 * This class is responsible for initializing all endpoints
 * and setting up the plugin update mechanism.
 */
class EndpointRegistry {
    /**
     * List of all endpoint classes.
     *
     * @var array
     */
    private static $endpoints = [
        CreatePost::class,
        DeletePost::class,
        UpdatePost::class,
        GetAuthors::class,
        GetCategories::class,
        Status::class,
        TokenManager::class,
        SettingsPageController::class,
        GetTags::class,
        GetPost::class,
        WebhookControl::class,
    ];

    /**
     * Initialize all endpoints and the updater.
     *
     * This method loops through all endpoints and initializes them.
     * It also initializes the plugin updater class.
     */
    public static function initialize() {
        foreach (self::$endpoints as $endpoint) {
            new $endpoint();
        }
        new Update();
        new Webhook();
    }
}

// Initialize the plugin endpoints
EndpointRegistry::initialize();

require_once plugin_dir_path(__FILE__) . 'includes/Admin/AdminNotices.php';

add_action('plugins_loaded', function () {
    if (class_exists('\VPlugins\BlogPostConnector\Admin\AdminNotices')) {
        new \VPlugins\BlogPostConnector\Admin\AdminNotices();
    }
});
