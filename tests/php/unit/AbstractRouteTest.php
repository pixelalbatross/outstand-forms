<?php

namespace Outstand\WP\Forms\Tests\Unit;

use Outstand\WP\Forms\REST\V1\AbstractRoute;

class AbstractRouteTest extends \WP_UnitTestCase {

	/**
	 * A subclass must inherit the shared namespace and an empty rest_base by default.
	 */
	public function test_defaults_expose_shared_namespace_and_empty_rest_base(): void {
		$route = new class() extends AbstractRoute {
			/**
			 * {@inheritDoc}
			 */
			public function register_routes(): void {}

			/**
			 * Expose the protected namespace for the assertion.
			 *
			 * @return string
			 */
			public function get_namespace(): string {
				return $this->namespace;
			}

			/**
			 * Expose the protected rest_base for the assertion.
			 *
			 * @return string
			 */
			public function get_rest_base(): string {
				return $this->rest_base;
			}
		};

		$this->assertSame( 'outstand-forms/v1', $route->get_namespace() );
		$this->assertSame( '', $route->get_rest_base() );
	}

	/**
	 * The register() method must hook register_routes() onto rest_api_init.
	 */
	public function test_register_hooks_register_routes_onto_rest_api_init(): void {
		$route = new class() extends AbstractRoute {
			/**
			 * Whether register_routes() ran.
			 *
			 * @var bool
			 */
			public bool $called = false;

			/**
			 * {@inheritDoc}
			 */
			public function register_routes(): void {
				$this->called = true;
			}
		};

		$route->register();

		$this->assertSame( 10, has_action( 'rest_api_init', [ $route, 'register_routes' ] ) );

		// Call the registered callback directly rather than firing
		// `rest_api_init` globally, which would also run every other
		// callback (core's and the plugin's own) attached to that hook.
		call_user_func( [ $route, 'register_routes' ] );

		$this->assertTrue( $route->called );
	}

	/**
	 * A concrete route registered onto rest_api_init must be reachable through the REST server.
	 */
	public function test_registered_route_is_dispatchable(): void {
		$route = new class() extends AbstractRoute {
			/**
			 * {@inheritDoc}
			 */
			protected string $rest_base = 'example';

			/**
			 * {@inheritDoc}
			 */
			public function register_routes(): void {
				register_rest_route(
					$this->namespace,
					'/' . $this->rest_base,
					[
						'methods'             => 'GET',
						'callback'            => [ $this, 'handle_request' ],
						'permission_callback' => '__return_true',
					]
				);
			}

			/**
			 * Return a canned success response.
			 *
			 * @return \WP_REST_Response
			 */
			public function handle_request(): \WP_REST_Response {
				return new \WP_REST_Response( [ 'ok' => true ], 200 );
			}
		};

		// Ensure the REST server exists, then register directly onto it so the
		// route is present even if `rest_api_init` already fired earlier in
		// the suite (rest_get_server() only fires it once, on first boot).
		rest_get_server();
		$route->register_routes();

		$request  = new \WP_REST_Request( 'GET', '/outstand-forms/v1/example' );
		$response = rest_get_server()->dispatch( $request );

		$this->assertSame( 200, $response->get_status() );
		$this->assertTrue( $response->get_data()['ok'] );
	}
}
