<?php

namespace Outstand\WP\Forms\Blocks;

use Outstand\WP\Forms\AbstractModule;

abstract class AbstractBlock extends AbstractModule {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	abstract public function get_name(): string;
}
