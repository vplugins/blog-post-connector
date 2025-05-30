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
        if (!get_option('sm_post_connector_enable_logs')) {
            echo '<div class="notice notice-warning"><p><strong>' . esc_html__('Logging is currently disabled.', 'blog-post-connector') . '</strong> ' .
                 esc_html__('Enable it from Logs Settings to start recording API requests.', 'blog-post-connector') . '</p></div>';
        }

        global $wpdb;

        $table_name = $wpdb->prefix . 'sm_post_connector_logs';

        // Download button
        $download_url = add_query_arg(['action' => 'download_logs_json'], admin_url('admin-post.php'));

        // Pagination setup
        $current_page = max(1, intval($_GET['paged'] ?? 1));
        $per_page = max(5, min(100, intval($_GET['per_page'] ?? 20)));
        $offset = ($current_page - 1) * $per_page;

        $total_items = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
        $total_pages = ceil($total_items / $per_page);

        $logs = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM $table_name ORDER BY timestamp DESC LIMIT %d OFFSET %d",
                $per_page,
                $offset
            )
        );

        echo '<h2>' . esc_html(__('Activity Logs', 'blog-post-connector')) . '</h2>';

        if (empty($logs)) {
            echo '<p>' . __('No logs available.', 'blog-post-connector') . '</p>';
            return;
        }else{
            echo '<p><a href="' . esc_url($download_url) . '" class="button button-primary">' . __('Download All Logs (JSON)', 'blog-post-connector') . '</a></p>';
        }

        echo '<table class="widefat fixed striped">';
        echo '<thead>
                <tr>
                    <th width="20%">' . __('Date/Time', 'blog-post-connector') . '</th>
                    <th width="10%">' . __('Method', 'blog-post-connector') . '</th>
                    <th width="10%">' . __('Route', 'blog-post-connector') . '</th>
                    <th width="60%">' . __('Response', 'blog-post-connector') . '</th>
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

        $this->render_pagination_controls($current_page, $total_pages, $per_page);
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

    private function render_pagination_controls($current_page, $total_pages, $per_page) {
        if ($total_pages <= 1) return;

        $base_url = remove_query_arg(['paged', '_wpnonce']);
        $prev_page = $current_page > 1 ? $current_page - 1 : null;
        $next_page = $current_page < $total_pages ? $current_page + 1 : null;

        echo '<div style="margin-top: 20px;">';
        echo '<div style="display:flex; align-items:center; gap:12px;">';

        if ($prev_page) {
            echo '<a class="button" href="' . esc_url(add_query_arg(['paged' => $prev_page, 'per_page' => $per_page], $base_url)) . '">&laquo; ' . __('Previous', 'blog-post-connector') . '</a>';
        }

        echo '<span>' . sprintf(__('Page %d of %d', 'blog-post-connector'), $current_page, $total_pages) . '</span>';

        if ($next_page) {
            echo '<a class="button" href="' . esc_url(add_query_arg(['paged' => $next_page, 'per_page' => $per_page], $base_url)) . '">' . __('Next', 'blog-post-connector') . ' &raquo;</a>';
        }

        echo '</div>';
        echo '</div>';
    }

    public function has_settings_fields() {
        return false;
    }
}

// Download handler in your plugin bootstrap file
add_action('admin_post_download_logs_json', function () {
    if (!current_user_can('manage_options')) {
        wp_die(__('Unauthorized', 'blog-post-connector'));
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'sm_post_connector_logs';

    $logs = $wpdb->get_results("SELECT * FROM $table_name ORDER BY timestamp DESC", ARRAY_A);

    $filename = 'post-connector-logs-' . date('Y-m-d-H-i-s') . '.json';

    header('Content-Type: application/json');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');

    echo wp_json_encode($logs, JSON_PRETTY_PRINT);
    exit;
});