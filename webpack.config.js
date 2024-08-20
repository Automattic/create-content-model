const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

var config = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry(),
		'./manager/fields-ui': './includes/manager/fields-ui.js',
		'./runtime/fields-ui': './includes/runtime/fields-ui.js',
	},
};

// Return Configuration
module.exports = config;
