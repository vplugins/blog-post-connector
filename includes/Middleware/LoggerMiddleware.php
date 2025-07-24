<?php

namespace VPlugins\BlogPostConnector\Middleware;

use WP_REST_Request;
use WP_Error;
use wpdb;

class LoggerMiddleware {
    private static bool $already_logged = false;

    public function __construct() {
        // Hook cleanup to admin_init (runs once per admin page load)
        add_action('admin_init', [$this, 'cleanup_old_logs']);
    }

    public function log(WP_REST_Request $request, $response) {
        if (self::$already_logged) {
            return; // Skip duplicate log
        }

        // Check if logging is enabled
        if (!get_option('sm_post_connector_enable_logs')) {
            return;
        }

        self::$already_logged = true;

        global $wpdb;

        $log_data = array(
            'method'    => $request->get_method(),
            'route'     => $request->get_route(),
            'headers'   => wp_json_encode($request->get_headers()),
            'body'      => wp_json_encode($request->get_json_params()),
            'response'  => wp_json_encode($this->get_response_data($response)),
            'timestamp' => current_time('mysql'),
        );

        $table_name = $wpdb->prefix . 'sm_post_connector_logs';
        $wpdb->insert($table_name, $log_data);
    }

    /**
     * Extracts serializable response data.
     */
    private function get_response_data($response) {
        if (is_wp_error($response)) {
            return [
                'error' => $response->get_error_message(),
                'code'  => $response->get_error_code(),
                'data'  => $response->get_error_data(),
            ];
        }

        if ($response instanceof \WP_REST_Response) {
            return $response->get_data();
        }

        return $response;
    }

    /**
     * Create log table.
     */
    public static function install() {
        global $wpdb;

        $table_name = $wpdb->prefix . 'sm_post_connector_logs';
        $charset_collate = $wpdb->get_charset_collate();

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            method VARCHAR(10) NOT NULL,
            route TEXT NOT NULL,
            headers LONGTEXT,
            body LONGTEXT,
            response LONGTEXT,
            timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        dbDelta($sql);
    }

    /**
     * Removes old logs based on retention days set in plugin settings.
     */
    public function cleanup_old_logs() {
        $retention_days = (int) get_option('sm_post_connector_log_retention_days', 30);

        if ($retention_days <= 0) {
            return; // Don't delete anything if retention is zero or invalid
        }

        global $wpdb;
        $table_name = $wpdb->prefix . 'sm_post_connector_logs';

        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM $table_name WHERE timestamp < NOW() - INTERVAL %d DAY",
                $retention_days
            )
        );
    }
}