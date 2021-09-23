const { VueLoaderPlugin } = require('vue-loader');

module.exports = {
	mode: process.env.NODE_ENV === 'production' ? 'production' : 'development',
	entry: {
		'gp-populate-anything': './js/src/frontend.ts',
		'gp-populate-anything-admin': './js/src/admin.ts'
	},
	output: {
		filename: '[name].js',
		path: __dirname + '/js/built'
	},
	module: {
		rules: [
			{
				test: /\.vue$/,
				loader: 'vue-loader',
			},
			{
				test: /\.tsx?$/,
				loader: 'ts-loader',
				exclude: /node_modules/,
				options: {
					appendTsSuffixTo: [/\.vue$/],
					transpileOnly: true,
				}
			},
		]
	},
	resolve: {
		extensions: ['.ts', '.js', '.vue', '.json'],
	},
	devServer: {
		historyApiFallback: true,
		noInfo: true
	},
	optimization: {
		minimize: true
	},
	plugins: [
		new VueLoaderPlugin()
	],
	devtool: process.env.NODE_ENV === 'production' ? 'source-map' : 'eval-source-map',
};
