const webpack = require( 'webpack' );
const glob = require( 'glob' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );
const path = require( 'path' );
const { CleanWebpackPlugin } = require( 'clean-webpack-plugin' );

const includesDir = path.resolve( process.cwd(), 'includes' );

const entries = glob
	.sync( '*/*.js', { cwd: includesDir } )
	.filter( ( entry ) => {
		return ! entry.split( '/' )[ 1 ].startsWith( '_' );
	} )
	.reduce( ( acc, entry ) => {
		const [ folder, name ] = entry.split( '/' );

		acc[
			`${ folder }/${ path.parse( name ).name }`
		] = `./includes/${ entry }`;

		return acc;
	}, {} );

/** @type {webpack.Configuration} */
const config = {
	...defaultConfig,
	entry: entries,
	output: {
		path: includesDir,
		filename: ( pathData ) => {
			const [ folder, name ] = pathData.chunk.name.split( '/' );

			return `${ folder }/dist/${ name }.js`;
		},
		clean: {
			keep: ( asset ) => {
				return ! asset.includes( 'dist' );
			},
		},
	},
	plugins: defaultConfig.plugins.filter(
		( plugin ) => ! ( plugin instanceof CleanWebpackPlugin )
	),
};

// Return Configuration
module.exports = config;
