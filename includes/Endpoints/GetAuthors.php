<?php

namespace VPlugins\SMPostConnector\Endpoints;

use WP_REST_Request;
use VPlugins\SMPostConnector\Middleware\AuthMiddleware;
use VPlugins\SMPostConnector\Helper\Globals;
use VPlugins\SMPostConnector\Helper\Response;

class GetAuthors {
    protected $auth_middleware;

    public function __construct() {
        $this->auth_middleware = new AuthMiddleware();
        add_action('rest_api_init', [$this, 'register_routes']);
    }

    public function register_routes() {
        register_rest_route('sm-connect/v1', '/authors', [
            'methods' => 'GET',
            'callback' => [$this, 'get_authors'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

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