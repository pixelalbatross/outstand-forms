<?php

namespace Outstand\WP\Forms\Components;

/**
 * Marks a control that renders several inputs under one field name.
 *
 * A group labels itself through `aria-labelledby` pointing at the field's
 * label, so the label must not also claim a single control with `for`.
 * {@see \Outstand\WP\Forms\Fields\Field::initialize_components()} builds the
 * control first and asks this question before building the label.
 */
interface GroupComponentInterface extends ComponentInterface {}
