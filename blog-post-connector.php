<?php
/**
 * Plugin Name: Blog Post Connector
 * Description: A plugin to publish blogs to your WordPress website.
 * Version: 1.0.6
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

// Add activation hook to check for multisite before allowing activation
register_activation_hook(__FILE__, 'blog_post_connector_check_multisite_on_activation');

/**
 * Check if multisite is enabled during plugin activation and set notice flag
 */
function blog_post_connector_check_multisite_on_activation() {
    if (is_multisite()) {
        // Set a transient to show admin notice after activation
        set_transient('blog_post_connector_multisite_notice', true, 30);
    }
}

// Add admin notice hooks for multisite warning (always register to catch transient notices)
add_action('admin_notices', 'blog_post_connector_multisite_notice');
add_action('network_admin_notices', 'blog_post_connector_multisite_notice');

// Check if this is a multisite installation and prevent further execution
if (is_multisite()) {
    // Deactivate the plugin if it's active
    add_action('admin_init', 'blog_post_connector_deactivate_on_multisite');
    
    // Prevent further execution
    return;
}

/**
 * Display admin notice warning about multisite compatibility
 */
function blog_post_connector_multisite_notice() {
    // Check if we should show the notice (either from transient or current multisite status)
    if (get_transient('blog_post_connector_multisite_notice') || is_multisite()) {
        $class = 'notice notice-error';
        $message = __('Blog Post Connector: This plugin is not compatible with WordPress multisite installations. Please deactivate it to avoid any issues.', 'blog-post-connector');
        printf('<div class="%1$s"><p>%2$s</p></div>', esc_attr($class), esc_html($message));
        
        // Delete the transient after showing the notice
        delete_transient('blog_post_connector_multisite_notice');
    }
}

/**
 * Deactivate plugin on multisite installations
 */
function blog_post_connector_deactivate_on_multisite() {
    if (is_plugin_active(plugin_basename(__FILE__))) {
        deactivate_plugins(plugin_basename(__FILE__));
        // Remove the activation notice to avoid confusion
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
}

// Load AdminNotices only on single site
if (!is_multisite()) {
    require_once plugin_dir_path(__FILE__) . 'includes/Admin/AdminNotices.php';

    add_action('plugins_loaded', function () {
        if (class_exists('\VPlugins\BlogPostConnector\Admin\AdminNotices')) {
            new \VPlugins\BlogPostConnector\Admin\AdminNotices();
        }
    });
}