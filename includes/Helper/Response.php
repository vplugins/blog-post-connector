<?php

namespace VPlugins\SMPostConnector\Helper;

use WP_REST_Response;
use VPlugins\SMPostConnector\Helper\Globals;

class Response {
    private static function create_response($status_code, $message = '', $data = []) {
        return new WP_REST_Response([
            'status' => $status_code,
            'message' => $message,
            'data' => $data
        ], $status_code);
    }

    public static function success($key = '', $data = []) {
        $message = Globals::get_success_message($key);
        return self::create_response(200, $message, $data);
    }

    public static function created($key = '', $data = []) {
        $message = Globals::get_success_message($key);
        return self::create_response(201, $message, $data);
    }

    public static function accepted($key = '', $data = []) {
        $message = Globals::get_success_message($key);
        return self::create_response(202, $message, $data);
    }

    public static function no_content($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(204, $message);
    }

    public static function bad_request($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(400, $message);
    }

    public static function unauthorized($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(401, $message);
    }

    public static function forbidden($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(403, $message);
    }

    public static function not_found($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(404, $message);
    }

    public static function conflict($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(409, $message);
    }

    public static function internal_server_error($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(500, $message);
    }

    public static function not_implemented($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(501, $message);
    }

    public static function error($key = '') {
        $message = Globals::get_success_message('error');
        return self::create_response(500, $message);
    }
}