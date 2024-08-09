<?php

namespace VPlugins\SMPostConnector\Helper;

class Globals {
    const PLUGIN_VERSION = '1.0.0'; 

    public static function get_version() {
        return self::PLUGIN_VERSION;
    }

    public static function get_categories() {
        return get_categories([
            'hide_empty' => false
        ]);
    }

    public static function get_authors() {
        $args = [
            'role__in' => ['Author', 'Editor', 'Administrator'],
            'orderby' => 'display_name',
            'order' => 'ASC'
        ];
        return get_users($args);
    }

    public static function get_success_message($key) {
        $messages = [
            'status_retrieved' => __('Version information retrieved successfully', 'sm-post-connector'),
            'post_created' => __('Post created successfully', 'sm-post-connector'),
            'post_updated' => __('Post updated successfully', 'sm-post-connector'),
            'post_deleted' => __('Post deleted successfully', 'sm-post-connector'),
            'categories_retrieved' => __('Categories retrieved successfully', 'sm-post-connector'),
            'authors_retrieved' => __('Authors retrieved successfully', 'sm-post-connector'),
            'post_id_required' => __('Post ID is required', 'sm-post-connector'),
            'post_not_found' => __('Post not found', 'sm-post-connector'),
            'post_moved_to_trash' => __('Post moved to trash successfully', 'sm-post-connector'),
            'post_permanently_deleted' => __('Post permanently deleted successfully', 'sm-post-connector'),
            'failed_to_delete_post' => __('Failed to delete post', 'sm-post-connector'),
            'missing_required_parameters' => __('Missing required parameters', 'sm-post-connector'),
            'invalid_post_status' => __('Invalid post status', 'sm-post-connector'),
            'date_required_for_future_posts' => __('Date is required for future posts', 'sm-post-connector'),
            'date_for_publish_status_must_be_past' => __('Date for publish status must be in the past', 'sm-post-connector'),
            'post_with_title_exists' => __('A post with the same title already exists', 'sm-post-connector'),
            'post_updated_successfully' => __('Post updated successfully', 'sm-post-connector'),
            'post_created_successfully' => __('Post created successfully', 'sm-post-connector'),
            'failed_to_update_post' => __('Failed to update post', 'sm-post-connector'),
            'failed_to_create_post' => __('Failed to create post', 'sm-post-connector'),
            'error' => __('An error occurred', 'sm-post-connector')
        ];

        return $messages[$key] ?? __('Operation successful', 'sm-post-connector');
    }
}