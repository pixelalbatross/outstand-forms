<?php
/**
 * Plugin Name: Outstand Forms E2E Helpers
 * Description: Test-environment-only helpers for the Playwright E2E suite. Mapped into the wp-env tests environment via .wp-env.json.
 */

namespace Outstand\WP\Forms\E2E;

// Disable the per-IP rate limit so repeated E2E submissions never trip 429.
add_filter( 'outstand_forms_rate_limit', '__return_zero' );
