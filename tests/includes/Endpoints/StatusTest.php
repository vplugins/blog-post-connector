<?php

namespace VPlugins\BlogPostConnector\Tests\Endpoints;

use VPlugins\BlogPostConnector\Endpoints\Status;
use VPlugins\BlogPostConnector\Helper\Globals;
use WP_Mock\Tools\TestCase;
use WP_REST_Request;
use WP_REST_Response;

class StatusTest extends TestCase {

    /**
     * @var Status
     */
    private $status;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();
        $this->status = new Status();
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test if the REST route for retrieving status is registered.
     */
    public function test_register_routes() {
        \WP_Mock::userFunction('register_rest_route', [
            'times' => 1,
            'args' => [
                'sm-connect/v1',
                '/status',
                [
                    'methods' => 'GET',
                    'callback' => [$this->status, 'get_status'],
                    'permission_callback' => [true, 'permissions_check']
                ]
            ],
        ]);

        $this->status->register_routes();

        \WP_Mock::assertHooksAdded();
    }

    /**
     * Test that the saved Default Author is returned in the same shape as the authors entries.
     */
    public function test_get_status_returns_saved_default_author() {
        $this->mock_site([15 => $this->make_user(15, 'Jane Author'), 13 => $this->make_user(13, 'Sam Editor')], '15');

        $site_details = $this->get_site_details();

        $this->assertArrayHasKey('default_author', $site_details);
        $this->assertSame(['ID' => 15, 'data' => ['display_name' => 'Jane Author']], $site_details['default_author']);
    }

    /**
     * Test that default_author is null when no valid default is saved, never a guessed user.
     *
     * User 1 exists here, as it does on most sites, so falling back to it the way
     * create-post does would fail this test.
     *
     * @dataProvider no_valid_default_author_provider
     *
     * @param string|null $stored The stored option value, or null when the option was never saved.
     */
    public function test_get_status_default_author_is_null_without_a_valid_saved_default($stored) {
        $this->mock_site([1 => $this->make_user(1, 'Site Admin'), 15 => $this->make_user(15, 'Jane Author')], $stored);

        $site_details = $this->get_site_details();

        $this->assertArrayHasKey('default_author', $site_details);
        $this->assertNull($site_details['default_author']);
    }

    /**
     * Stored Default Author values that do not point at an existing user.
     */
    public function no_valid_default_author_provider() {
        return [
            'option never saved'     => [null],
            'saved as empty'         => [''],
            'saved user was deleted' => ['99'],
        ];
    }

    /**
     * Test that authors carry only the ID and display name, never the password hash or email.
     */
    public function test_get_status_authors_expose_only_id_and_display_name() {
        $this->mock_site([15 => $this->make_user(15, 'Jane Author'), 13 => $this->make_user(13, 'Sam Editor')], null);

        $this->assertSame(
            [
                ['ID' => 15, 'data' => ['display_name' => 'Jane Author']],
                ['ID' => 13, 'data' => ['display_name' => 'Sam Editor']],
            ],
            $this->get_site_details()['authors']
        );
    }

    /**
     * Builds a user carrying every field a real WP_User has.
     *
     * @param int    $id           The user ID.
     * @param string $display_name The display name.
     * @return \WP_User
     */
    private function make_user($id, $display_name) {
        return new \WP_User([
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
    }

    /**
     * Mocks the WordPress calls get_status() makes.
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

                if ($name === Globals::WEBHOOK_ENABLED_OPTION) {
                    return '1';
                }

                return false; // No custom logo, and keeps LoggerMiddleware inert.
            },
        ]);
        \WP_Mock::userFunction('get_users', ['return' => array_values($users)]);
        \WP_Mock::userFunction('get_user_by', [
            'return' => function ($field, $value) use ($users) {
                return $field === 'ID' && isset($users[(int) $value]) ? $users[(int) $value] : false;
            },
        ]);
        \WP_Mock::userFunction('get_bloginfo', ['return' => 'Test Site']);
        \WP_Mock::userFunction('get_site_icon_url', ['return' => '']);
        \WP_Mock::userFunction('get_categories', ['return' => []]);
        \WP_Mock::userFunction('get_tags', ['return' => []]);
        \WP_Mock::userFunction('wp_remote_get', ['return' => ['body' => '{"tag_name":"v1.0.6"}']]);
        \WP_Mock::userFunction('is_wp_error', ['return' => false]);
        \WP_Mock::userFunction('wp_remote_retrieve_body', ['return' => '{"tag_name":"v1.0.6"}']);
    }

    /**
     * Calls the status endpoint and returns its site_details block.
     *
     * @return array
     */
    private function get_site_details() {
        $response = $this->status->get_status(\Mockery::mock(WP_REST_Request::class));

        return $response->get_data()['data']['site_details'];
    }

}