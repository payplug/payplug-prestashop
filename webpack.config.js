const MiniCssExtractPlugin = require("mini-css-extract-plugin");
const path = require('path');

const lessFiles = ['front', 'admin', 'admin_order'];

let entryFiles = {};

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
        ],
    },
};
