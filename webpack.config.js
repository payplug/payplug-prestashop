/**
 * 2013 - COPYRIGHT_YEAR Payplug SAS
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
 *  @author    Payplug SAS
 *  @copyright 2013 - COPYRIGHT_YEAR Payplug SAS
 *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of Payplug SAS
 */
const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const CssMinimizerPlugin = require("css-minimizer-webpack-plugin");
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');
const ReplaceInFileWebpackPlugin = require('replace-in-file-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');
const path = require('path');
const fs = require('fs');

const dir_path = path.join(__dirname, 'dev');
const cssViewsFolder = 'css';
const jsViewsFolder = 'js';

const jsAtomsFolder = 'js/components/atoms';
const jsMoleculesFolder = 'js/components/molecules';

const dirJsFinalPath = 'views/js/';
const dirViewsFinalPath = 'views/';

const configuration = require('./composer.json');
const moduleVersion = configuration.version;
const moduleName = configuration.moduleName;

let entryFiles = {};

function _getAllFilesFromFolder(dir)
{
    const joinedPath = path.join(dir_path, dir);
    const fullPath = path.normalize(joinedPath);

    if (!fullPath.startsWith(dir_path)) {
        console.log("Invalid path specified!");
        return;
    }

    if (!fs.existsSync(fullPath)) {
        return;
    }

    fs.readdirSync(fullPath).forEach(function (file) {
        var wpFile = '../' + dirViewsFinalPath + dir + '/' + path.parse(file).name;
        const fileJoinedPath = path.join(fullPath, file);
        const filePath = path.normalize(fileJoinedPath);

        if (!filePath.startsWith(fullPath)) {
            console.log("Invalid path specified!");
            return;
        }

        // compilation des fichiers .less
        if (path.extname(filePath).toLowerCase() == '.less') {
            var stat = fs.statSync(filePath);

            if (stat && stat.isDirectory()) {
                _getAllFilesFromFolder(filePath);
            } else {
                entryFiles[wpFile + '-v' + moduleVersion] = path.resolve(__dirname, filePath);
            }
        }

        // compilation des fichiers .js
        if (path.extname(filePath).toLowerCase() == '.js') {
            var stat = fs.statSync(filePath);

            if (stat && stat.isDirectory()) {
                _getAllFilesFromFolder(filePath);
            } else {
                switch (dir) {
                    // compilation des fichiers "components" .js
                    case jsAtomsFolder:
                    case jsMoleculesFolder:
                        if (typeof entryFiles['../' + dirJsFinalPath + 'components' + '-v' + moduleVersion] == 'undefined') {
                            entryFiles['../' + dirJsFinalPath + 'components' + '-v' + moduleVersion] = [];
                        }

                        entryFiles['../' + dirJsFinalPath + 'components' + '-v' + moduleVersion].push(path.resolve(__dirname, filePath));
                        break;

                    // compilation des fichiers .js
                    case jsViewsFolder:
                        entryFiles[wpFile + '-v' + moduleVersion] = path.resolve(__dirname, filePath);
                        break;
                }
            }
        }
    });
};

_getAllFilesFromFolder(cssViewsFolder);
_getAllFilesFromFolder(jsAtomsFolder);
_getAllFilesFromFolder(jsMoleculesFolder);
_getAllFilesFromFolder(jsViewsFolder);

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
    minimize: true,
    minimizer: [
        new CssMinimizerPlugin(), // todo: uncomment for prod compilation
        new TerserPlugin({
            parallel: 8,
            terserOptions: {
                format: {
                    comments: /^\**!|2013 - COPYRIGHT_YEAR Payplug SAS/i,
                },
            },
            extractComments:false,
        }),
    ],
    splitChunks: {
        // include all types of chunks
        chunks: 'all',
        maxInitialRequests: 30,
    },
    removeAvailableModules: false,
    removeEmptyChunks: false,
};
const plugins = [
    new RemoveEmptyScriptsPlugin(),
    new MiniCssExtractPlugin({
        filename: '[name].css',
        chunkFilename: '[id].css'
    }),
    new ReplaceInFileWebpackPlugin([{
        dir: dirViewsFinalPath,
        test: [/\.css$/, /\.js$/],
        rules: [{
            search: /__moduleName__/gi,
            replace: moduleName
        }]
    }])
];
module.exports = {
    cache: {
        type: 'filesystem',
        memoryCacheUnaffected: true,
        maxMemoryGenerations: Infinity,
        store: 'pack',
    },
    mode: 'production',
    entry: entryFiles,
    output: {
        path: dir_path
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
                include: path.resolve(__dirname, jsViewsFolder),
                loader: 'babel-loader',
            }
        ],
    },
    optimization: optimization,
    plugins: plugins
};
