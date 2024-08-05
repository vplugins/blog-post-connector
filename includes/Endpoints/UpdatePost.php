<?php

namespace VPlugins\SMPostConnector\Endpoints;

class UpdatePost extends BasePost {
    public function register_routes() {
        register_rest_route('sm-connect/v1', '/update-post', [
            'methods' => 'POST',
            'callback' => [$this, 'update_post'],
            'permission_callback' => [$this->auth_middleware, 'permissions_check']
        ]);
    }

    public function update_post($request) {
        return $this->handle_post_request($request, true);
    }
}