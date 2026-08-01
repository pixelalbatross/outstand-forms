const defaultConfig = require('@wordpress/scripts/config/jest-unit.config.js');

module.exports = {
	...defaultConfig,
	transformIgnorePatterns: ['/node_modules/(?!(@wordpress/interactivity|@preact|preact)/)'],
};
