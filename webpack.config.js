const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const path = require('path');

const lessFiles = ['front', 'admin', 'admin_order'];

let entryFiles = {};
entryFiles['payplug_front'] = ['./views/js/front.js','./views/js/embedded.js'];
entryFiles['payplug16_front'] = ['./views/js/front_1_6.js','./views/js/embedded.js'];
entryFiles['payplug_admin'] = ['./views/js/admin.js','./views/js/admin_order.js','./views/js/admin_order_popin.js'];

lessFiles.map((file) => {
    entryFiles[file] = path.resolve(__dirname, 'views/css/' + file + '.less');
});

module.exports = {
    mode: 'production',
    entry: entryFiles,
    output: {
        path: path.resolve(__dirname, 'public')
    },
    plugins: [
        new MiniCssExtractPlugin(),
    ],
    module: {
        rules: [
            {
                test: /\.less$/,
                use: [
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
                                relativeUrls: true,
                                sourceMap: true,
                            },
                        },
                    },
                ],
            },
            {
                test: /.s?css$/,
                use: [MiniCssExtractPlugin.loader, "css-loader", "less-loader"],
            },
        ],
    },
    optimization: {
        minimizer: [
            new CssMinimizerPlugin(),
        ],
        minimize: true,
    },
    plugins: [
        new RemoveEmptyScriptsPlugin(),
        new MiniCssExtractPlugin({
            filename: '[name].css',
            chunkFilename: '[id].css'
        })
    ]
};
