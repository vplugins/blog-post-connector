<?php

// First we need to load the composer autoloader so we can use WP Mock
require_once __DIR__ . '/../vendor/autoload.php';

// Now call the bootstrap method of WP Mock
WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

define( 'SM_PLUGIN_BASENAME', basename( __DIR__ . '/../blog-post-connector.php' ) );

if ( defined( 'WP_TESTS_MULTISITE' ) ) {
	// Tells the plugin it is network active.
	define( 'SM_IS_NETWORK', true );
} else {
	define( 'SM_IS_NETWORK', false );
}

/**
 * WP_Mock ships no WordPress classes, so stub the few the plugin constructs
 * directly. Only classes are stubbed here, never functions -- functions must
 * stay undefined so tests can mock them via WP_Mock::userFunction().
 */
if ( ! class_exists( 'WP_REST_Request' ) ) {
	class WP_REST_Request {}
}

if ( ! class_exists( 'WP_REST_Response' ) ) {
	class WP_REST_Response {
		protected $data;
		protected $status;

		public function __construct( $data = null, $status = 200 ) {
			$this->data   = $data;
			$this->status = $status;
		}

		public function get_data() {
			return $this->data;
		}

		public function get_status() {
			return $this->status;
		}
	}
}

/**
 * The plugin's classes are all reachable through Composer's PSR-4 autoloader,
 * so the main plugin file is deliberately not required here: it calls
 * register_activation_hook() and other WordPress functions at the top level,
 * which WP_Mock does not define, and requiring it aborts the whole run.
 */
