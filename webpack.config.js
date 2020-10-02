const webpack = require('webpack');
const path = require('path');
const CompressionPlugin = require('compression-webpack-plugin');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
//const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

module.exports = {
	entry: {
		main: './js/main.ts',
		error500: './js/error500.js',
	},
	output: {
		filename: '[name].bundle.js',
		path: path.resolve(__dirname, 'www/dist'),
		publicPath: '/dist/',
	},
	module: {
		rules: [
			{
				test: /\.vue$/,
				loader: 'vue-loader'
			},
			{
				test: /\.tsx?$/,
				loader: 'ts-loader',
				exclude: /node_modules/,
				options: {
					appendTsSuffixTo: [/\.vue$/],
				},
			},
			{
				enforce: 'pre',
				test: /\.js$/,
				exclude: /node_modules/,
				loader: 'eslint-loader',
			},
			{
				test: /\.js$/,
				exclude: /node_modules/,
				loader: 'babel-loader',
				options: {
					cacheDirectory: true,
					'presets': [
						[
							'@babel/preset-env', {
								'targets': '> 0.5%, not dead'
							}
						]
					]
				},
			},
			{
				test: /\.s[ac]ss$/,
				use: [
					'vue-style-loader',
					MiniCssExtractPlugin.loader,
					'css-loader',
					'sass-loader',
				],
			},
			{
				test: /\.css$/,
				use: [
					'vue-style-loader',
					MiniCssExtractPlugin.loader,
					'css-loader',
				],
			},
			{
				test: /\.(ttf|eot|svg|woff(|2))(\?[\s\S]+)?$/,
				use: 'file-loader?name=fonts/[name].[ext]',
			},
			{
				test: /\.(jpe?g|png|gif|svg)$/i,
				use: [
					'file-loader?name=images/[name].[ext]',
				]
			},
		]
	},
	plugins: [
		new webpack.ProvidePlugin({
			Popper: ['popper.js', 'default'],
		}),
		new MiniCssExtractPlugin({
			filename: '[name].bundle.css',
		}),
		new VueLoaderPlugin(),
		new CompressionPlugin(),
		//new BundleAnalyzerPlugin(),
	],
	optimization: {
		minimize: true,
		minimizer: [
			new TerserPlugin({
				sourceMap: true,
			}),
			new OptimizeCSSAssetsPlugin({})
		],
		splitChunks: {
			chunks: 'all',
		}
	},
	resolve: {
		alias: {
			'vue$': 'vue/dist/vue.esm.js'
		},
		extensions: ['*', '.js', '.vue', '.json', '.ts', '.tsx']
	},
	devtool: 'source-map',
};

if (process.env.NODE_ENV === 'production') {
	module.exports.devtool = 'source-map';
	// http://vue-loader.vuejs.org/en/workflow/production.html
	module.exports.plugins = (module.exports.plugins || []).concat([
		new webpack.DefinePlugin({
			'process.env': {
				NODE_ENV: '"production"',
			},
		}),
		new webpack.optimize.UglifyJsPlugin({
			sourceMap: true,
			compress: {
				warnings: false,
			},
		}),
		new webpack.LoaderOptionsPlugin({
			minimize: true,
		}),
	]);
}
