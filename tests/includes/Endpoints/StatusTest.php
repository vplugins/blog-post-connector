<?php

namespace VPlugins\BlogPostConnector\Tests\Endpoints;

use VPlugins\BlogPostConnector\Endpoints\Status;
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

}