const path = require("path");
const webpack = require("webpack");
const { merge } = require("webpack-merge");
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CopyPlugin = require("copy-webpack-plugin");
const WebpackBuildNotifierPlugin = require('webpack-build-notifier');

// if (typeof window !== `undefined`) {
// 	const netteAjax = require("nette.ajax.js");
// }

const devMode = process.env.NODE_ENV !== "production";
const ROOT_PATH = __dirname;
const MODULES = ROOT_PATH + "/node_modules";
const CORE_PATH = __dirname + "/app/CoreModule";
const CORE_ASSETS = CORE_PATH + "/assets";
const PLUGINS_PATH = CORE_ASSETS + "/js/plugins";
const DIST_PATH = path.join(ROOT_PATH, "www", "dist");
const MODULES_DIST = DIST_PATH + "/modules";

module.exports = {
	entry: {
		admin: path.join(CORE_PATH, "./assets/js/admin.js"),
		front: path.join(ROOT_PATH, "./app/assets/js/app.js"),
	},
	mode: devMode ? "development" : "production",
	output: {
		filename: 'js/[name].bundle.js',
		path: DIST_PATH
	},
	resolve: {
		alias: {
			CoreModules: path.resolve(MODULES),
			Core: CORE_PATH,
			CoreAssets: path.resolve(CORE_PATH, "./assets"),
			CoreSass: path.resolve(CORE_PATH, "./assets/scss"),
			CorePlugins: path.resolve(CORE_PATH, "./assets/js/plugins"),
			CoreJS: path.resolve(CORE_PATH, "./assets/js")
		},
		modules: ['node_modules']
	},
	plugins: [
		new webpack.ProvidePlugin({
			$: "jquery",
			jQuery: "jquery",
			"window.jQuery": "jquery",
			"window.$": "jquery",
			Nette: "nette-forms",
			naja: ["naja", "default"],
			imagesLoad: "imagesloaded/imagesloaded.pkgd.min.js",
			Utils: [PLUGINS_PATH + "/Monty/utils.js", "Utils"],
			loaders: PLUGINS_PATH + "/Monty/loaders.js",
			iziModal: "izimodal/js/iziModal.js",
			flashes: PLUGINS_PATH + "/Monty/flashes.js",
			gsap: ["gsap", "gsap"]
		}),
		new MiniCssExtractPlugin({
			filename: "css/[name].style.css"
		}),
		new CopyPlugin({
			patterns: [
				{ from: MODULES + '/tinymce-langs/langs/cs.js', to: MODULES_DIST + "/tinymce/langs" },
				{ from: CORE_ASSETS + "/images", to: DIST_PATH + "/images"}
			]
		}),
	    new WebpackBuildNotifierPlugin({
	      title: "My Webpack Project",
	      // logo: path.resolve("./img/favicon.png"),
	      // suppressSuccess: true, // don't spam success notifications
	      sound: true
	    })
	],
	module: {
		noParse: /^(vue|vue-router|vuex|vuex-router-sync)$/,
		rules: [
			{
				test: /\.(css|scss|sass)$/,
				use: [
					MiniCssExtractPlugin.loader,
					{
						loader: 'css-loader',
						options: {
							sourceMap: false,
							importLoaders: 2,
							modules: false
						}
					},
					{
						loader: "postcss-loader",
						options: {
							postcssOptions: {
								ident: "postcss",
								plugins: [require("autoprefixer")]	
							}
						}
					},
					"sass-loader"
				],
			}
		]
	},
	watch: true,
	devtool: 'source-map'
};


if (process.env.NODE_ENV === "production") {
	const production = {
		devtool: "none",
		// optimization: {
		// 	minimizer: [
		// 		new TerserPlugin({
		// 			test: /\.m?js(\?.*)?$/i,
		// 			warningsFilter: () => true,
		// 			extractComments: false,
		// 			sourceMap: true,
		// 			cache: true,
		// 			cacheKeys: defaultCacheKeys => defaultCacheKeys,
		// 			parallel: true,
		// 			include: undefined,
		// 			exclude: undefined,
		// 			minify: undefined,
		// 			terserOptions: {
		// 				output: {
		// 					comments: /^\**!|@preserve|@license|@cc_on/i
		// 				},
		// 				compress: {
		// 					arrows: false,
		// 					collapse_vars: false,
		// 					comparisons: false,
		// 					computed_props: false,
		// 					hoist_funs: false,
		// 					hoist_props: false,
		// 					hoist_vars: false,
		// 					inline: false,
		// 					loops: false,
		// 					negate_iife: false,
		// 					properties: false,
		// 					reduce_funcs: false,
		// 					reduce_vars: false,
		// 					switches: false,
		// 					toplevel: false,
		// 					typeofs: false,
		// 					booleans: true,
		// 					if_return: true,
		// 					sequences: true,
		// 					unused: true,
		// 					conditionals: true,
		// 					dead_code: true,
		// 					evaluate: true
		// 				},
		// 				mangle: {
		// 					safari10: true
		// 				}
		// 			}
		// 		})
		// 	],
		// },
		plugins: [
			// optimize CSS files
			new OptimizeCSSAssetsPlugin(),
		],
	};

	module.exports = merge(module.exports, production);
}