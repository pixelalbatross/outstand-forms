<?php

namespace Outstand\WP\Forms\Blocks;

use Outstand\WP\Forms\BaseModule;

abstract class AbstractBlock extends BaseModule {

	/**
	 * The block name.
	 *
	 * @var string
	 */
	abstract public function get_name(): string;
}
