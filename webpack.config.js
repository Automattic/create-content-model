const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

var config = {
	...defaultConfig,
	entry: {
		...defaultConfig.entry(),
		'./manager/fields-ui': './includes/manager/fields-ui.js',
	},
};

// Return Configuration
module.exports = config;
