/**
 * Manual Jest mock for `@wordpress/interactivity`.
 *
 * The real package only publishes an ESM `exports` entry with a top-level
 * `await`, which Jest's CommonJS runtime cannot execute even after a Babel
 * transform. This mock reimplements just the `store()` contract that
 * `src/validation.js` and its tests rely on: a namespace-keyed registry that
 * deep-merges each call's config into the same object reference, so multiple
 * calls (including ones from third-party registrations in tests) accumulate
 * into one live object instead of replacing it.
 *
 * @see https://github.com/WordPress/gutenberg/tree/trunk/packages/interactivity
 */

const registry = {};

function isPlainObject(value) {
	return typeof value === 'object' && value !== null && !Array.isArray(value);
}

function deepMerge(target, source) {
	for (const key of Object.keys(source)) {
		target[key] = isPlainObject(source[key])
			? deepMerge(isPlainObject(target[key]) ? target[key] : {}, source[key])
			: source[key];
	}

	return target;
}

export function store(namespace, config = {}) {
	if (!registry[namespace]) {
		registry[namespace] = {};
	}

	deepMerge(registry[namespace], config);

	return registry[namespace];
}
