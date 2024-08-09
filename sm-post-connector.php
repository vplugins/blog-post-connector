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
use VPlugins\SMPostConnector\Endpoints\{
    CreatePost,
    DeletePost,
    UpdatePost,
    GetAuthors,
    GetCategories,
    Status
};

// Endpoint Registry Class
class EndpointRegistry {
    private static $endpoints = [
        CreatePost::class,
        DeletePost::class,
        UpdatePost::class,
        GetAuthors::class,
        GetCategories::class,
        Status::class,
        Token::class,
    ];

    public static function initialize() {
        foreach (self::$endpoints as $endpoint) {
            new $endpoint();
        }
        new Update();
    }
}

// Initialize the plugin endpoints
EndpointRegistry::initialize();