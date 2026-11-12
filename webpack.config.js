/**
 * Webpack configuration for the bundled admin scripts.
 *
 * Extends the @wordpress/scripts default config. Run it through `npm run build`
 * (the Grunt `bundle` task), not on its own, so all assets come from one build.
 *
 * Sources: src/  ->  Output: assets/admin/js/
 */
const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,
	entry: {
		chart: './src/chart.js',
		bulk:  './src/bulk.js',
	},
	output: {
		...defaultConfig.output,
		path: path.resolve( __dirname, 'assets/admin/js' ),
	},
	/*
	 * The bundles are registered by hand in Imagify_Assets, so the generated
	 * *.asset.php files would go unused. Drop the plugin until a bundle imports
	 * a @wordpress/* package and its asset file gets wired into the enqueue.
	 */
	plugins: defaultConfig.plugins.filter(
		( plugin ) => 'DependencyExtractionWebpackPlugin' !== plugin.constructor.name
	),
};
