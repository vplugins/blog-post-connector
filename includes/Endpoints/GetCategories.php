<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Globals;
use VPlugins\SMPostConnector\Helper\Response;

class GetCategories {
    protected $auth_middleware;

    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('sm-connect/v1', '/categories', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categories'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    public function get_categories(WP_REST_Request $request) {
        $categories = Globals::get_categories();
        $formattedCategories = [];
        $categoryCount = 1;

        foreach ($categories as $category) {
            $formattedCategories[$categoryCount] = [
                'name' => $category->name,
                'id' => $category->term_id,
                'num_posts' => $category->count
            ];
            $categoryCount++;
        }

        return Response::success(
            Globals::get_success_message('categories_retrieved'), 
            [
                'categories' => $formattedCategories
            ]
        );
    }
}