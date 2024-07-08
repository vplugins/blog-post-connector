<?php

namespace VPlugins\SMPostConnector\Endpoints;

class GetCategories {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('sm-connect/v1', '/get-categories', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categories'],
            'permission_callback' => [$this, 'permissions_check']
        ]);
    }

    public function get_categories($request) {
        // Handle the categories retrieval
    }

    public function permissions_check($request) {
        // Check user permissions
    }
}
