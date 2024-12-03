<?php

namespace VPlugins\SMPostConnector\Tests\Endpoints;

use VPlugins\SMPostConnector\Endpoints\GetAuthors;
use WP_Mock\Tools\TestCase;
use WP_REST_Request;
use WP_REST_Response;

class GetAuthorsTest extends TestCase {

    /**
     * @var GetAuthors
     */
    private $getAuthors;

    /**
     * Set up the test environment.
     */
    public function setUp(): void {
        \WP_Mock::setUp();
        $this->getAuthors = new GetAuthors();
    }

    /**
     * Tear down the test environment.
     */
    public function tearDown(): void {
        \WP_Mock::tearDown();
        parent::tearDown();
    }

    /**
     * Test if the REST route for retrieving authors is registered.
     */
    public function test_register_routes() {
        \WP_Mock::userFunction('register_rest_route', [
            'times' => 1,
            'args' => [
                'sm-connect/v1',
                '/authors',
                [
                    'methods' => 'GET',
                    'callback' => [$this->getAuthors, 'get_authors'],
                    'permission_callback' => [true, 'permissions_check']
                ]
            ],
        ]);

        $this->getAuthors->register_routes();

        \WP_Mock::assertHooksAdded();
    }
}