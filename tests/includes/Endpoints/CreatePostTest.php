<?php

namespace VPlugins\BlogPostConnector\Tests\Endpoints;

use VPlugins\BlogPostConnector\Endpoints\CreatePost;
use WP_Mock\Tools\TestCase;

class CreatePostTest extends TestCase {

    /**
     * @var CreatePost
     */
    private $createPost;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();
        $this->createPost = new CreatePost();
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test if the REST route for creating a post is registered.
     */
    public function test_register_routes() {
        \WP_Mock::userFunction('register_rest_route', [
            'times' => 1,
            'args' => [
                'sm-connect/v1',
                '/create-post',
                [
                    'methods' => 'POST',
                    'callback' => [$this->createPost, 'create_post'],
                    'permission_callback' => [true, 'permissions_check']
                ]
            ],
        ]);

        $this->createPost->register_routes();

        \WP_Mock::assertHooksAdded();
    }

    /**
     * Test that a request without an author is assigned to the lowest-ID administrator when no
     * Default Author is saved. User 1 exists as an Editor, so the old hardcoded fallback to user 1
     * would pass validation and fail this test.
     */
    public function test_create_post_without_author_uses_the_first_administrator_when_nothing_is_saved() {
        $this->mock_site([
            1  => $this->make_user(1, 'Site Editor', 'editor'),
            15 => $this->make_user(15, 'Amy Admin', 'administrator'),
            13 => $this->make_user(13, 'Zed Admin', 'administrator'),
        ], null);
        $inserted = $this->expect_insert();

        $response = $this->createPost->create_post($this->request(['title' => 'Hello', 'content' => 'Body', 'status' => 'draft']));

        $this->assertSame(200, $response->get_status());
        $this->assertSame(13, $inserted()['post_author']);
    }

    /**
     * Test that a saved Default Author is used for a request without an author, even when a
     * lower-ID administrator exists.
     */
    public function test_create_post_without_author_uses_the_saved_default_author() {
        $this->mock_site([
            13 => $this->make_user(13, 'Zed Admin', 'administrator'),
            15 => $this->make_user(15, 'Jane Author', 'author'),
        ], '15');
        $inserted = $this->expect_insert();

        $response = $this->createPost->create_post($this->request(['title' => 'Hello', 'content' => 'Body', 'status' => 'draft']));

        $this->assertSame(200, $response->get_status());
        $this->assertSame(15, $inserted()['post_author']);
    }

    /**
     * Test that an explicit author in the request is kept, whatever the default would be.
     */
    public function test_create_post_keeps_an_explicit_author() {
        $this->mock_site([
            13 => $this->make_user(13, 'Zed Admin', 'administrator'),
            15 => $this->make_user(15, 'Jane Author', 'author'),
        ], '13');
        $inserted = $this->expect_insert();

        $response = $this->createPost->create_post($this->request(['title' => 'Hello', 'content' => 'Body', 'status' => 'draft', 'author' => 15]));

        $this->assertSame(200, $response->get_status());
        $this->assertSame(15, $inserted()['post_author']);
    }

    /**
     * Test that a request without an author is rejected with invalid_author_id, and nothing is
     * inserted, when no Default Author is saved and the site has no administrator.
     */
    public function test_create_post_without_author_fails_when_the_site_has_no_administrator() {
        $this->mock_site([1 => $this->make_user(1, 'Site Editor', 'editor'), 15 => $this->make_user(15, 'Jane Author', 'author')], null);
        \WP_Mock::userFunction('wp_insert_post', ['times' => 0]);

        $response = $this->createPost->create_post($this->request(['title' => 'Hello', 'content' => 'Body', 'status' => 'draft']));

        $this->assertSame(400, $response->get_status());
        $this->assertSame('Invalid author ID', $response->get_data()['message']);
    }

    /**
     * Builds a user carrying every field a real WP_User has.
     *
     * @param int    $id           The user ID.
     * @param string $display_name The display name.
     * @param string $role         The user's single role, as WordPress stores it (lowercase key).
     * @return \WP_User
     */
    private function make_user($id, $display_name, $role = 'administrator') {
        $user = new \WP_User([
            'ID'                  => (string) $id,
            'user_login'          => 'user' . $id,
            'user_pass'           => '$P$Bnotarealhash' . $id,
            'user_nicename'       => 'user' . $id,
            'user_email'          => 'user' . $id . '@example.com',
            'user_url'            => '',
            'user_registered'     => '2025-01-16 13:06:36',
            'user_activation_key' => '',
            'user_status'         => '0',
            'display_name'        => $display_name,
        ]);
        $user->roles = [$role];

        return $user;
    }

    /**
     * Mocks the WordPress calls handle_post_request() makes up to and including the insert.
     *
     * @param array       $users          Users keyed by ID, as get_users() and get_user_by() see them.
     * @param string|null $default_author The stored Default Author option, or null when it was never saved.
     */
    private function mock_site($users, $default_author) {
        \WP_Mock::userFunction('get_option', [
            'return' => function ($name, $default = false) use ($default_author) {
                if ($name === 'sm_post_connector_default_author') {
                    return $default_author === null ? $default : $default_author;
                }
                if ($name === 'sm_post_connector_default_category') {
                    return '1';
                }

                return false; // Logging off, so LoggerMiddleware writes nothing.
            },
        ]);
        \WP_Mock::userFunction('get_users', [
            'return' => function ($args = []) use ($users) {
                $list = array_values($users);
                if (isset($args['role'])) {
                    $list = array_values(array_filter($list, function ($user) use ($args) {
                        return in_array($args['role'], $user->roles, true);
                    }));
                }
                if (isset($args['orderby']) && $args['orderby'] === 'ID') {
                    usort($list, function ($a, $b) {
                        return $a->ID <=> $b->ID;
                    });
                    if (isset($args['order']) && strtoupper($args['order']) === 'DESC') {
                        $list = array_reverse($list);
                    }
                }
                if (!empty($args['number'])) {
                    $list = array_slice($list, 0, (int) $args['number']);
                }

                return $list;
            },
        ]);
        \WP_Mock::userFunction('get_user_by', [
            'return' => function ($field, $value) use ($users) {
                return $field === 'ID' && isset($users[(int) $value]) ? $users[(int) $value] : false;
            },
        ]);
        \WP_Mock::userFunction('get_post_stati', ['return' => ['publish', 'draft', 'future']]);
        \WP_Mock::userFunction('sanitize_text_field', ['return' => function ($text) { return $text; }]);
        \WP_Mock::userFunction('wp_kses_post', ['return' => function ($html) { return $html; }]);
        \WP_Mock::userFunction('current_time', ['return' => '2026-10-01 10:00:00']);
        \WP_Mock::userFunction('get_permalink', ['return' => 'https://example.com/hello/']);
    }

    /**
     * Expects exactly one wp_insert_post() call and returns a reader for the data it received.
     *
     * @return callable Returns the $post_data array passed to wp_insert_post().
     */
    private function expect_insert() {
        $captured = [];
        \WP_Mock::userFunction('wp_insert_post', [
            'times'  => 1,
            'return' => function ($post_data) use (&$captured) {
                $captured = $post_data;

                return 123;
            },
        ]);

        return function () use (&$captured) {
            return $captured;
        };
    }

    /**
     * Builds a create-post request whose get_param() answers from the given parameters.
     *
     * @param array $params The request parameters; anything missing reads as null.
     * @return \WP_REST_Request
     */
    private function request($params) {
        $request = \Mockery::mock(\WP_REST_Request::class);
        $request->shouldReceive('get_param')->andReturnUsing(function ($key) use ($params) {
            return $params[$key] ?? null;
        });
        $request->shouldReceive('get_headers')->andReturn([]);
        $request->shouldReceive('get_json_params')->andReturn($params);

        return $request;
    }

}