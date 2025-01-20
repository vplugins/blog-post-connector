<?php
/**
 * Plugin Name: Blog Post Connector
 * Description: A plugin to publish blogs to your WordPress website.
 * Version: 1.0.2
 * Author: Website Pro, a WordPress hosting platform.
 * Text Domain: blog-post-connector
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Autoload the classes using Composer
require_once __DIR__ . '/vendor/autoload.php';

use VPlugins\BlogPostConnector\Auth\Token;
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
        Token::class,
        GetTags::class,
        GetPost::class
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