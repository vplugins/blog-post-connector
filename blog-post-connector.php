<?php
/**
 * Plugin Name: Blog Post Connector
 * Description: A plugin to publish blogs to your WordPress website.
 * Version: 1.0.3
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
use VPlugins\BlogPostConnector\Admin\AdminNotices;
use VPlugins\BlogPostConnector\Endpoints\{
    CreatePost,
    DeletePost,
    UpdatePost,
    GetPost,
    GetAuthors,
    GetCategories,
    GetTags,
    Status
};
use VPlugins\BlogPostConnector\Middleware\LoggerMiddleware;

// Register plugin activation hook to create logs table
register_activation_hook(__FILE__, ['VPlugins\BlogPostConnector\Middleware\LoggerMiddleware', 'install']);

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
        new AdminNotices();
    }
}

// Initialize the plugin endpoints
EndpointRegistry::initialize();

// Block runtime execution on multisite
add_action('admin_init', function () {
    if (is_multisite()) {
        // Show admin error
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error is-dismissible"><p><strong>SM Blog Post Connector:</strong> This plugin is not compatible with WordPress Multisite and has been deactivated.</p></div>';
        });

        // Deactivate plugin if active
        deactivate_plugins(plugin_basename(__FILE__));
    }
});

// Load plugin only if not multisite
if (!is_multisite()) {
    require_once plugin_dir_path(__FILE__) . 'includes/Admin/AdminNotices.php';
    new \VPlugins\BlogPostConnector\Admin\AdminNotices();
}