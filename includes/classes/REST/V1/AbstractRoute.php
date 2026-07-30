<?php

namespace Outstand\WP\Forms\REST\V1;

use Outstand\WP\Forms\AbstractModule;

abstract class AbstractRoute extends AbstractModule {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected string $namespace = 'osf/v1';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected string $rest_base = '';

	/**
	 * {@inheritDoc}
	 */
	public function register(): void {
		add_action( 'rest_api_init', [ $this, 'register_routes' ] );
	}

	/**
	 * Registers the REST API routes.
	 *
	 * @return void
	 */
	abstract public function register_routes(): void;
}
