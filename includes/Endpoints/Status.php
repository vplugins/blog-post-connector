<?php

namespace VPlugins\SMPostConnector\Endpoints;

class Status {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('sm-connect/v1', '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_status'],
            'permission_callback' => [$this, 'permissions_check']
        ]);
    }

    public function get_status($request) {
        // Handle the status check
    }

    public function permissions_check($request) {
        // Check user permissions
    }
}
