<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Globals;

class Status {
    protected $auth_middleware;

    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('sm-connect/v1', '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_status'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    public function get_status(WP_REST_Request $request) {
        
        $version = Globals::get_version();

        
    
        return new \WP_REST_Response([
            'status' => 200,
            'data' => [
                'version' => $version,
            ]
        ], 200);
    }
    
}
