/**
 * 2013 - 2022 PayPlug SAS
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0).
 * It is available through the world-wide-web at this URL:
 * https://opensource.org/licenses/osl-3.0.php
 * If you are unable to obtain it through the world-wide-web, please send an email
 * to contact@payplug.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
 * versions in the future.
 *
 *  @author    PayPlug SAS
 *  @copyright 2013 - 2022 PayPlug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PayPlug SAS
 */

const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const TerserPlugin = require('terser-webpack-plugin');
const path = require('path');
const fs = require('fs');

const dir_path = 'views';
const lessFiles = ['front', 'front_1_6', 'admin', 'admin_order'];
const jsFiles = ['accordion', 'button'];

let entryFiles = {};
lessFiles.map((file) => {
    if (fs.existsSync(path.resolve(__dirname, dir_path + '/css/' + file + '.less'))) {
        entryFiles['css/' + file] = path.resolve(__dirname, dir_path + '/css/' + file + '.less');
    }
});

jsFiles.map((file) => {
    if (fs.existsSync(path.resolve(__dirname, dir_path + '/js/components/atoms/' + file + '.js'))) {
        if (typeof entryFiles['js/components/atoms/components'] == 'undefined') {
            entryFiles['js/components/atoms/components'] = [];
        }
        entryFiles['js/components/atoms/components'].push(path.resolve(__dirname, dir_path + '/js/components/atoms/' + file + '.js'));
    }
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
        new TerserPlugin(),
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
        path: path.resolve(__dirname, dir_path)
    },
    module: {
        rules: [
            {
                test: /\.less$/,
                use: loaders,
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
            },
        ],
    },
    optimization: optimization,
    plugins: plugins
};
