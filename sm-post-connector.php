<?php
/**
 * Plugin Name: SM Post Connector
 * Description: Connect WordPress with the Social Marketing tool to manage posts seamlessly.
 * Version: 0.0.2
 * Author: Website Pro WordPress Team
 * Text Domain: sm-post-connector
 */

// Prevent direct access to the file
if (!defined('ABSPATH')) {
    exit;
}

// Load dependencies via Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

use VPlugins\SMPostConnector\Auth\Token;
use VPlugins\SMPostConnector\Updater\Update;
use VPlugins\SMPostConnector\Webhook\Webhook;
use VPlugins\SMPostConnector\Endpoints\{
    CreatePost,
    DeletePost,
    UpdatePost,
    GetPost,
    GetAuthors,
    GetCategories,
    GetTags,
    Status
};
use VPlugins\SMPostConnector\Middleware\LogsMiddleware;

/**
 * Class SMPostConnector
 *
 * Handles initialization of endpoints, webhook, and plugin updates.
 */
class SMPostConnector {

    /**
     * List of endpoint classes to be registered.
     *
     * @var string[]
     */
    private static array $endpoints = [
        CreatePost::class,
        DeletePost::class,
        UpdatePost::class,
        GetPost::class,
        GetAuthors::class,
        GetCategories::class,
        GetTags::class,
        Status::class,
        Token::class,
    ];

    /**
     * Initializes the plugin components.
     */
    public static function initialize(): void {
        self::initializeEndpoints();
        self::initializeUpdater();
        self::initializeWebhook();
        self::initializeLogsMiddleware();
    }

    /**
     * Instantiates all registered endpoint classes.
     */
    private static function initializeEndpoints(): void {
        foreach (self::$endpoints as $endpoint) {
            if (class_exists($endpoint)) {
                new $endpoint();
            }
        }
    }

    /**
     * Initializes the plugin update mechanism.
     */
    private static function initializeUpdater(): void {
        if (class_exists(Update::class)) {
            new Update();
        }
    }

    /**
     * Sets up the webhook listener.
     */
    private static function initializeWebhook(): void {
        if (class_exists(Webhook::class)) {
            new Webhook();
        }
    }

    /**
     * Initializes the logs middleware for logging API requests.
     */
    private static function initializeLogsMiddleware(): void {
        if (class_exists(LogsMiddleware::class)) {
            new LogsMiddleware(); // Instantiate LogsMiddleware
        }
    }
}

// Initialize the plugin
add_action('plugins_loaded', [SMPostConnector::class, 'initialize']);

// Register activation hook for setting up necessary plugin components.
register_activation_hook(__FILE__, ['VPlugins\SMPostConnector\Middleware\LogsMiddleware', 'activate']);