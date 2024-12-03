<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Globals;
use VPlugins\SMPostConnector\Helper\Response;

/**
 * Class GetAuthors
 *
 * Registers a REST API endpoint for retrieving authors.
 *
 * @package VPlugins\SMPostConnector\Endpoints
 */
class GetAuthors {
    /**
     * @var AuthMiddleware
     */
    protected $auth_middleware;

    /**
     * GetAuthors constructor.
     *
     * Initializes the AuthMiddleware instance and registers the REST API routes.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the REST API route for retrieving authors.
     *
     * Adds a route for retrieving authors using a GET request.
     * The route is registered under the namespace 'sm-connect/v1' and the endpoint '/authors'.
     */
    public function register_routes() {
        register_rest_route('sm-connect/v1', '/authors', [
            'methods' => 'GET',
            'callback' => [$this, 'get_authors'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    /**
     * Handles the request to retrieve authors.
     *
     * Retrieves a list of authors, including their display name, ID, and number of posts.
     *
     * @param WP_REST_Request $request The request object for retrieving authors.
     * 
     * @return \WP_REST_Response The response object containing the list of authors.
     */
    public function get_authors(WP_REST_Request $request) {
        $authors = Globals::get_authors();
        $formattedAuthors = [];
        $authorCount = 1;

        foreach ($authors as $author) {
            $formattedAuthors[$authorCount] = [
                'name' => $author->display_name,
                'id' => $author->ID,
                'num_posts' => count_user_posts($author->ID)
            ];
            $authorCount++;
        }

        return Response::success(
            Globals::get_success_message('authors_retrieved'), 
            [
                'authors' => $formattedAuthors
            ]
        );
    }
}