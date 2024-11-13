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
        // Current plugin version
        $current_version = Globals::get_version(); // Replace with actual plugin version or static value like '1.0.0'

        // Get the latest version from GitHub
        $latest_version = $this->fetch_latest_version_from_github();
        
        // Site details
        $site_name = get_bloginfo('name');
        $site_description = get_bloginfo('description');
        $site_version = get_bloginfo('version');
        
        // Check for custom logo in wp_options table
        $custom_logo = get_option('sm_post_connector_logo');
    
        // Fallback to site icon URL if custom logo is empty
        if (empty($custom_logo)) {
            $logo_url = get_site_icon_url(); // Site icon fallback
        } else {
            $logo_url = $custom_logo;
        }
    
        $data = [
            'site_details' => [
                'name' => $site_name,
                'description' => $site_description,
                'logo' => $logo_url,
                'version' => $site_version,
                'plugin_version' => [
                    'current' => $current_version,
                    'latest' => $latest_version,
                ],
            ],
        ];
    
        $success_message = 'Status retrieved successfully.'; // Custom message
    
        // Use the Response helper for a standard format
        return Response::success($success_message, $data);
    }

    /**
     * Fetches the latest version of the plugin from the GitHub repository.
     *
     * @return string|null The latest version tag or null if unable to retrieve.
     */
    protected function fetch_latest_version_from_github() {
        // GitHub URL for the specific repository
        $url = "https://api.github.com/repos/vplugins/sm-post-connector/releases/latest";

        $response = wp_remote_get($url, [
            'headers' => [
                'User-Agent' => 'WordPress/' . get_bloginfo('version'),
            ]
        ]);

        if (is_wp_error($response)) {
            return null; // Handle error appropriately
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        return isset($data['tag_name']) ? $data['tag_name'] : null;
    }
}