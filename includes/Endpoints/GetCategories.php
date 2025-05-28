<?php

namespace VPlugins\BlogPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\BlogPostConnector\Middleware\AuthMiddleware;
use VPlugins\BlogPostConnector\Middleware\LoggerMiddleware;
use VPlugins\BlogPostConnector\Helper\Globals;
use VPlugins\BlogPostConnector\Helper\Response;

/**
 * Class GetCategories
 *
 * Registers a REST API endpoint for retrieving categories.
 */
class GetCategories {
    /**
     * @var AuthMiddleware
     */
    protected $auth_middleware;

    /**
     * @var LoggerMiddleware
     */
    protected $logger;

    /**
     * GetCategories constructor.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        $this->logger = new LoggerMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the REST API route for retrieving categories.
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
     * @param WP_REST_Request $request The request object.
     * @return \WP_REST_Response The response object.
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

        $response = Response::success(
            Globals::get_success_message('categories_retrieved'), 
            ['categories' => $formattedCategories]
        );

        // Log the request and response
        $this->logger->log($request, $response);

        return $response;
    }
}