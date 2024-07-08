<?php
/**
 * Plugin Name: SM Post Connector
 * Description: A plugin to connect WordPress with the Social Marketing tool.
 * Version: 1.0.0
 * Author: Website Pro WordPress Team
 * Text Domain: sm-post-connector
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Autoload the classes using Composer
require_once __DIR__ . '/vendor/autoload.php';

use VPlugins\SMPostConnector\Endpoints\CreatePost;
use VPlugins\SMPostConnector\Endpoints\GetAuthors;
use VPlugins\SMPostConnector\Endpoints\GetCategories;
use VPlugins\SMPostConnector\Endpoints\Status;
use VPlugins\SMPostConnector\Auth\Token;

// Initialize the plugin endpoints
new CreatePost();
new GetAuthors();
new GetCategories();
new Status();
new Token();
