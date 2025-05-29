<?php

namespace VPlugins\BlogPostConnector\Admin;

class LogsTab {

    public function get_title() {
        return __('Logs', 'blog-post-connector');
    }

    public function render_settings_fields() {
        // No settings fields for Logs tab
    }

    public function maybe_render_extra_content() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sm_post_connector_logs';

        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 100");

        echo '<h2>' . esc_html(__('Activity Logs', 'blog-post-connector')) . '</h2>';

        if (empty($logs)) {
            echo '<p>' . __('No logs available.', 'blog-post-connector') . '</p>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>
                <th>' . __('Date/Time', 'blog-post-connector') . '</th>
                <th>' . __('Method', 'blog-post-connector') . '</th>
                <th>' . __('Route', 'blog-post-connector') . '</th>
                <th>' . __('Response (truncated)', 'blog-post-connector') . '</th>
            </tr></thead>';
        echo '<tbody>';

        foreach ($logs as $log) {
            echo '<tr>';
            echo '<td>' . esc_html(date('Y-m-d H:i:s', strtotime($log->timestamp))) . '</td>';
            echo '<td>' . esc_html($log->method) . '</td>';
            echo '<td>' . esc_html(wp_trim_words($log->route, 15, '...')) . '</td>';
            echo '<td>' . esc_html(wp_trim_words($log->response, 20, '...')) . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }
}