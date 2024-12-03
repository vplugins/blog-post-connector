<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Globals;
use VPlugins\SMPostConnector\Helper\Response;

/**
 * Class GetCategories
 *
 * Registers a REST API endpoint for retrieving categories.
 *
 * @package VPlugins\SMPostConnector\Endpoints
 */
class GetCategories {
    /**
     * @var AuthMiddleware
     */
    protected $auth_middleware;

    /**
     * GetCategories constructor.
     *
     * Initializes the AuthMiddleware instance and registers the REST API routes.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the REST API route for retrieving categories.
     *
     * Adds a route for retrieving categories using a GET request.
     * The route is registered under the namespace 'sm-connect/v1' and the endpoint '/categories'.
     */
    public function register_routes() {
        register_rest_route('sm-connect/v1', '/categories', [
            'methods' => 'GET',
            'callback' => [$this, 'get_categories'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    /**
     * Handles the request to retrieve categories.
     *
     * Retrieves a list of categories, including their name, ID, and number of posts.
     *
     * @param WP_REST_Request $request The request object for retrieving categories.
     * 
     * @return \WP_REST_Response The response object containing the list of categories.
     */
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