/**
 * @typedef {import('@roots/bud').Bud} Bud
 *
 * @param {Bud} bud
 */
module.exports = async bud => {
	bud.externals({
		jQuery: 'window.jquery',
		wp: 'window.wp',
	})
	bud.runtime('single')

	await bud
		.setPath('@dist', '../assets/admin')
		.entry({
			chart: 'chart.js',
			bulk: 'bulk.js',
		})
		//.when( bud.isProduction, () => bud.splitChunks().minimize() )
}
