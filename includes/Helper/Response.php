<?php

namespace VPlugins\SMPostConnector\Helper;

use WP_REST_Response;
use VPlugins\SMPostConnector\Helper\Globals;

/**
 * Class Response
 *
 * This class provides static methods for creating standardized REST API responses.
 * It utilizes HTTP status codes and messages defined in the Globals class.
 */
class Response {
    /**
     * Creates a WP_REST_Response object.
     *
     * @param int $status_code The HTTP status code for the response.
     * @param string $message The message to include in the response.
     * @param array $data Additional data to include in the response.
     * @return WP_REST_Response The WP_REST_Response object.
     */
    private static function create_response($status_code, $message = '', $data = []) {
        return new WP_REST_Response([
            'status' => $status_code,
            'message' => $message,
            'data' => $data
        ], $status_code);
    }

    /**
     * Returns a success response with a 200 OK status code.
     *
     * @param string $key The key for the success message from Globals.
     * @param array $data Optional additional data to include in the response.
     * @return WP_REST_Response The response object with a 200 status code.
     */
    public static function success($key = '', $data = []) {
        $message = Globals::get_success_message($key);
        return self::create_response(200, $message, $data);
    }

    /**
     * Returns a success response with a 201 Created status code.
     *
     * @param string $key The key for the success message from Globals.
     * @param array $data Optional additional data to include in the response.
     * @return WP_REST_Response The response object with a 201 status code.
     */
    public static function created($key = '', $data = []) {
        $message = Globals::get_success_message($key);
        return self::create_response(201, $message, $data);
    }

    /**
     * Returns a success response with a 202 Accepted status code.
     *
     * @param string $key The key for the success message from Globals.
     * @param array $data Optional additional data to include in the response.
     * @return WP_REST_Response The response object with a 202 status code.
     */
    public static function accepted($key = '', $data = []) {
        $message = Globals::get_success_message($key);
        return self::create_response(202, $message, $data);
    }

    /**
     * Returns a success response with a 204 No Content status code.
     *
     * @param string $key The key for the success message from Globals.
     * @return WP_REST_Response The response object with a 204 status code.
     */
    public static function no_content($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(204, $message);
    }

    /**
     * Returns a client error response with a 400 Bad Request status code.
     *
     * @param string $key The key for the error message from Globals.
     * @return WP_REST_Response The response object with a 400 status code.
     */
    public static function bad_request($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(400, $message);
    }

    /**
     * Returns a client error response with a 401 Unauthorized status code.
     *
     * @param string $key The key for the error message from Globals.
     * @return WP_REST_Response The response object with a 401 status code.
     */
    public static function unauthorized($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(401, $message);
    }

    /**
     * Returns a client error response with a 403 Forbidden status code.
     *
     * @param string $key The key for the error message from Globals.
     * @return WP_REST_Response The response object with a 403 status code.
     */
    public static function forbidden($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(403, $message);
    }

    /**
     * Returns a client error response with a 404 Not Found status code.
     *
     * @param string $key The key for the error message from Globals.
     * @return WP_REST_Response The response object with a 404 status code.
     */
    public static function not_found($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(404, $message);
    }

    /**
     * Returns a client error response with a 409 Conflict status code.
     *
     * @param string $key The key for the error message from Globals.
     * @return WP_REST_Response The response object with a 409 status code.
     */
    public static function conflict($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(409, $message);
    }

    /**
     * Returns a server error response with a 500 Internal Server Error status code.
     *
     * @param string $key The key for the error message from Globals.
     * @return WP_REST_Response The response object with a 500 status code.
     */
    public static function internal_server_error($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(500, $message);
    }

    /**
     * Returns a server error response with a 501 Not Implemented status code.
     *
     * @param string $key The key for the error message from Globals.
     * @return WP_REST_Response The response object with a 501 status code.
     */
    public static function not_implemented($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(501, $message);
    }

    /**
     * Returns a generic server error response with a 500 Internal Server Error status code.
     *
     * @param string $key Optional key for the error message. Defaults to a generic error message.
     * @return WP_REST_Response The response object with a 500 status code.
     */
    public static function error($key = '') {
        $message = Globals::get_success_message($key);
        return self::create_response(500, $message);
    }
}