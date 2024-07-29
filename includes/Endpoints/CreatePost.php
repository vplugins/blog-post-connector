<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Globals;

class CreatePost {
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('sm-connect/v1', '/create-post', [
            'methods' => 'POST',
            'callback' => [$this, 'create_post'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    public function create_post($request) {
        // Handle the post creation
    }

}
