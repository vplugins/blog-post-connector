<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Globals;
use VPlugins\SMPostConnector\Helper\Response;

/**
 * Class Status
 *
 * Registers a REST API endpoint for retrieving the status of the plugin.
 *
 * @package VPlugins\SMPostConnector\Endpoints
 */
class Status {
    /**
     * @var AuthMiddleware
     */
    protected $auth_middleware;

    /**
     * Status constructor.
     *
     * Initializes the AuthMiddleware instance and registers the REST API routes.
     */
    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    /**
     * Registers the REST API route for retrieving the plugin status.
     *
     * Adds a route for retrieving the plugin status using a GET request.
     * The route is registered under the namespace 'sm-connect/v1' and the endpoint '/status'.
     */
    public function register_routes() {
        register_rest_route('sm-connect/v1', '/status', [
            'methods' => 'GET',
            'callback' => [$this, 'get_status'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    /**
     * Handles the request to retrieve the plugin status.
     *
     * Retrieves the current version of the plugin and returns it in the response.
     *
     * @param WP_REST_Request $request The request object for retrieving the status.
     * 
     * @return \WP_REST_Response The response object containing the plugin version and a success message.
     */
    public function get_status(WP_REST_Request $request) {
        $version = Globals::get_version();
        $success_message = Globals::get_success_message('status_retrieved');
        
        // Use the Response helper for a standard format
        return Response::success($success_message, [
            'version' => $version,
        ]);
    }
}