<?php 

namespace VPlugins\SMPostConnector\Middleware;

use WP_REST_Request;
use WP_REST_Response;

class LogsMiddleware {

    /**
     * Constructor.
     *
     * Initializes the LogsMiddleware.
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_logs_middleware']);
    }

    /**
     * Register activation hook to initialize the logs table.
     */
    public static function activate() {
        self::create_sm_plugin_api_logs_table();
    }

    /**
     * Creates the `sm_plugin_api_logs` table for logging API calls.
     */
    public static function create_sm_plugin_api_logs_table() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sm_plugin_api_logs';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            request_method VARCHAR(10) NOT NULL,
            endpoint VARCHAR(255) NOT NULL,
            request_headers LONGTEXT,
            request_body LONGTEXT,
            response_code INT(5),
            response_body LONGTEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id)
        ) $charset_collate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Registers the logs middleware for logging API calls.
     */
    public function register_logs_middleware() {
        add_filter('rest_pre_dispatch', function($result, $server, $request) {
            add_action('rest_post_dispatch', function($response) use ($request) {
                self::log_request($request, $response);
            }, 10, 1);
            return $result;
        }, 10, 3);
    }

    /**
     * Log API calls.
     *
     * @param WP_REST_Request $request The incoming REST request.
     * @param mixed $response The API response to log.
     */
    public static function log_request(WP_REST_Request $request, $response = null) {
        global $wpdb;
    
        $table_name = $wpdb->prefix . 'sm_plugin_api_logs';
    
        // Prepare response body safely
        $response_body = is_wp_error($response)
            ? json_encode(['error' => $response->get_error_message()])
            : (method_exists($response, 'get_data') ? json_encode($response->get_data()) : json_encode($response));
    
        // Prepare data for insertion
        $data = [
            'request_method'  => $request->get_method(),
            'endpoint'        => $request->get_route(),
            'request_headers' => json_encode($request->get_headers()),
            'request_body'    => json_encode($request->get_params()),
            'response_code'   => is_wp_error($response) ? 500 : (method_exists($response, 'get_status') ? $response->get_status() : null),
            'response_body'   => $response_body,
        ];
    
        // Insert the data into the logs table
        $wpdb->insert($table_name, $data);
    }
}