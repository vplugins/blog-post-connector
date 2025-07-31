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
    }
}

// Initialize the plugin endpoints
EndpointRegistry::initialize();

/**
 * Show a warning in Network Admin if plugin is used in a Multisite environment.
 */
add_action('network_admin_notices', function () {
    if (is_multisite() && is_network_admin() && current_user_can('manage_network_plugins')) {
        $screen = get_current_screen();
        if ($screen && $screen->id === 'plugins-network') {
            echo '<div class="notice notice-warning is-dismissible">';
            echo '<p><strong>SM Blog Post Connector plugin should be activated individually on each subsite for full functionality.</strong></p>';
            echo '</div>';
        }
    }
});