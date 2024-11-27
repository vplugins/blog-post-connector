<?php
/**
 * Plugin Name: SM Post Connector
 * Description: A plugin to connect WordPress with the Social Marketing tool.
 * Version: 0.0.1
 * Author: Website Pro WordPress Team
 * Text Domain: sm-post-connector
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Autoload the classes using Composer
require_once __DIR__ . '/vendor/autoload.php';

use VPlugins\SMPostConnector\Auth\Token;
use VPlugins\SMPostConnector\Updater\Update;
use VPlugins\SMPostConnector\Webhook\Webhook;
use VPlugins\SMPostConnector\Endpoints\{
    CreatePost,
    DeletePost,
    UpdatePost,
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
        GetTags::class
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