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
const ReplaceInFileWebpackPlugin = require('replace-in-file-webpack-plugin');
const path = require('path');
const fs = require('fs');

const dir_path = 'dev';
const cssViewsFolder = 'css';
const jsViewsFolder = 'js';

const jsAtomsFolder = 'js/components/atoms';
const jsMoleculesFolder = 'js/components/molecules';

const dirJsFinalPath = 'views/js/';
const dirViewsFinalPath = 'views/';

const congifuration = require('./composer.json');
const moduleVersion = congifuration.version;
const moduleName = congifuration.moduleName;

let entryFiles = {};

function _getAllFilesFromFolder(dir)
{
    if (!fs.existsSync(dir_path + '/' + dir)) {
        return;
    }

    fs.readdirSync(dir_path + '/' + dir).forEach(function (file) {
        var wpFile = '../' + dirViewsFinalPath + dir + '/' + path.parse(file).name;
        file = dir_path + '/' + dir + '/' + file;

        // compilation des fichiers .less
        if (path.extname(file).toLowerCase() == '.less') {
            var stat = fs.statSync(file);

            if (stat && stat.isDirectory()) {
                _getAllFilesFromFolder(file);
            } else {
                entryFiles[wpFile + '-v' + moduleVersion] = path.resolve(__dirname, file);
            }
        }

        // compilation des fichiers .js
        if (path.extname(file).toLowerCase() == '.js') {
            var stat = fs.statSync(file);

            if (stat && stat.isDirectory()) {
                _getAllFilesFromFolder(file);
            } else {
                switch (dir) {
                    // compilation des fichiers "components" .js
                    case jsAtomsFolder:
                    case jsMoleculesFolder:
                        if (typeof entryFiles['../' + dirJsFinalPath + 'components' + '-v' + moduleVersion] == 'undefined') {
                            entryFiles['../' + dirJsFinalPath + 'components' + '-v' + moduleVersion] = [];
                        }

                        entryFiles['../' + dirJsFinalPath + 'components' + '-v' + moduleVersion].push(path.resolve(__dirname, file));
                        break;

                    // compilation des fichiers .js
                    case jsViewsFolder:
                        entryFiles[wpFile + '-v' + moduleVersion] = path.resolve(__dirname, file);
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
    minimizer: [
    new CssMinimizerPlugin(), // todo: uncomment for prod compilation
    //new TerserPlugin(),
    ],
    //minimize: true,
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
            }
        ],
    },
    optimization: optimization,
    plugins: plugins
};
