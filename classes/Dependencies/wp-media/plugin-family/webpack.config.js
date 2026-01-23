const defaultConfig = require('@wordpress/scripts/config/webpack.config');

module.exports = {
	...defaultConfig,
	output: {
		...defaultConfig.output,
		path: __dirname + '/src/assets/js',
	},
};