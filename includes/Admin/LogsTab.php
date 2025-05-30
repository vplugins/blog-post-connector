<?php

namespace VPlugins\BlogPostConnector\Admin;

class LogsTab {

    public function get_title() {
        return __('Logs', 'blog-post-connector');
    }

    public function render_settings_fields() {
        // No standard settings fields for this tab
    }

    public function maybe_render_extra_content() {
        global $wpdb;
        $table_name = $wpdb->prefix . 'sm_post_connector_logs';

        $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT 50");

        echo '<h2>' . esc_html(__('Activity Logs', 'blog-post-connector')) . '</h2>';

        if (empty($logs)) {
            echo '<p>' . __('No logs available.', 'blog-post-connector') . '</p>';
            return;
        }

        echo '<table class="widefat fixed striped">';
        echo '<thead>
                <tr>
                    <th width="20%">' . __('Date/Time', 'blog-post-connector') . '</th>
                    <th width="10%">' . __('Method', 'blog-post-connector') . '</th>
                    <th width="10%">' . __('Route', 'blog-post-connector') . '</th>
                    <th width="50%">' . __('Response', 'blog-post-connector') . '</th>
                </tr>
              </thead>';
        echo '<tbody>';

        foreach ($logs as $log) {
            $route = $this->get_route_segment($log->route);
            $response = $this->format_response($log->response);

            echo '<tr>';
            echo '<td>' . esc_html(date('Y-m-d H:i:s', strtotime($log->timestamp))) . '</td>';
            echo '<td>' . esc_html($log->method) . '</td>';
            echo '<td>' . esc_html($route) . '</td>';
            echo '<td>' . $response . '</td>';
            echo '</tr>';
        }

        echo '</tbody>';
        echo '</table>';
    }

    private function get_route_segment($route) {
        $parts = explode('/', trim($route, '/'));
        return end($parts);
    }

    private function format_response($response_json) {
        $html = '';

        $data = json_decode($response_json, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
            $status = $data['status'] ?? '';
            $message = $data['message'] ?? '';
            $tags = $data['data']['tags'] ?? [];

            $html .= '<strong>Status:</strong> ' . esc_html($status) . '<br>';
            $html .= '<strong>Message:</strong> ' . esc_html($message) . '<br>';

            if (!empty($tags)) {
                $html .= '<details><summary><strong>Tags:</strong> ' . count($tags) . ' items</summary><ul style="margin-left:1em;">';
                foreach ($tags as $tag) {
                    $html .= '<li>' . esc_html($tag['name']) . ' (ID: ' . esc_html($tag['id']) . ', Posts: ' . esc_html($tag['num_posts']) . ')</li>';
                }
                $html .= '</ul></details>';
            }

        } else {
            $html = '<code>' . esc_html(wp_trim_words($response_json, 20, '...')) . '</code>';
        }

        return $html;
    }

    public function has_settings_fields() {
        return false;
    }

}