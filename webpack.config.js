const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const path = require('path');


const css_path = 'views/css';
const lessFiles = ['front', 'front_1_6', 'admin', 'admin_order'];

let entryFiles = {};
lessFiles.map((file) => {
    entryFiles[file] = path.resolve(__dirname, css_path + '/' + file + '.less');
});

const loaders = [
    MiniCssExtractPlugin.loader,
    {
        loader: "css-loader",
        options: {
            url: false,
        }
    },
    {
        loader: "less-loader", // compiles Less to CSS
        options: {
            lessOptions: {
                relativeUrls: false,
                sourceMap: true,
            },
        },
    },
];
const optimization = {
    minimizer: [
        new CssMinimizerPlugin(),
    ],
    minimize: true,
};
const plugins = [
    new RemoveEmptyScriptsPlugin(),
    new MiniCssExtractPlugin({
        filename: '[name].css',
        chunkFilename: '[id].css'
    })
];

module.exports = {
    mode: 'production',
    entry: entryFiles,
    output: {
        path: path.resolve(__dirname, css_path)
    },
    module: {
        rules: [
            {
                test: /\.less$/,
                use: loaders,
            },
        ],
    },
    optimization: optimization,
    plugins: plugins
};
