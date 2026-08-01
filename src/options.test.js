/* eslint-env jest */

/**
 * `options.js` computes its exported option arrays at module-evaluation time
 * from `osfSettings`, which is localized by PHP before the editor bundle
 * runs. To exercise different `osfSettings` shapes we have to set the global
 * and re-require the module inside `jest.isolateModules`, rather than
 * `import`ing it once at the top of the file.
 */
describe('options', () => {
	afterEach(() => {
		delete global.osfSettings;
		jest.resetModules();
	});

	it('maps known label positions to their labels', () => {
		global.osfSettings = { labelPositions: ['top', 'left', 'right'] };

		jest.isolateModules(() => {
			const { labelPositionOptions } = require('./options');

			expect(labelPositionOptions).toEqual([
				{ value: 'top', label: 'Top' },
				{ value: 'left', label: 'Left' },
				{ value: 'right', label: 'Right' },
			]);
		});
	});

	it('falls back to the raw value for an unknown label position', () => {
		global.osfSettings = { labelPositions: ['diagonal'] };

		jest.isolateModules(() => {
			const { labelPositionOptions } = require('./options');

			expect(labelPositionOptions).toEqual([{ value: 'diagonal', label: 'diagonal' }]);
		});
	});

	it('returns an empty array of label options when osfSettings is undefined', () => {
		jest.isolateModules(() => {
			const { labelPositionOptions } = require('./options');

			expect(labelPositionOptions).toEqual([]);
		});
	});

	it('maps known help text positions to their labels', () => {
		global.osfSettings = { helpTextPositions: ['bottom', 'top'] };

		jest.isolateModules(() => {
			const { helpTextPositionOptions } = require('./options');

			expect(helpTextPositionOptions).toEqual([
				{ value: 'bottom', label: 'Bottom' },
				{ value: 'top', label: 'Top' },
			]);
		});
	});

	it('falls back to the raw value for an unknown help text position', () => {
		global.osfSettings = { helpTextPositions: ['diagonal'] };

		jest.isolateModules(() => {
			const { helpTextPositionOptions } = require('./options');

			expect(helpTextPositionOptions).toEqual([{ value: 'diagonal', label: 'diagonal' }]);
		});
	});

	it('returns an empty array of help text options when osfSettings is undefined', () => {
		jest.isolateModules(() => {
			const { helpTextPositionOptions } = require('./options');

			expect(helpTextPositionOptions).toEqual([]);
		});
	});

	it('lists the full set of autocomplete options with a blank default', () => {
		jest.isolateModules(() => {
			const { autocompleteOptions } = require('./options');

			expect(autocompleteOptions[0]).toEqual({ label: '', value: '' });
			expect(autocompleteOptions).toEqual(
				expect.arrayContaining([
					{ label: 'E-mail address', value: 'email' },
					{ label: 'Website URL', value: 'url' },
				]),
			);
			expect(autocompleteOptions).toHaveLength(30);
		});
	});
});
