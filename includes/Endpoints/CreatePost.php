<?php

namespace VPlugins\SMPostConnector\Endpoints;

use VPlugins\SMPostConnector\Helper\BasePost;

class CreatePost extends BasePost {
    public function register_routes() {
        register_rest_route('sm-connect/v1', '/create-post', [
            'methods' => 'POST',
            'callback' => [$this, 'create_post'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    public function create_post($request) {
        return $this->handle_post_request($request);
    }
}