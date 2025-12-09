#!/bin/bash

# Exit if any command fails
set -e

echo "get version from package.json..."
version=$(grep '"version"' ./package.json | head -1 | awk -F: '{ print $2 }' | sed 's/[", ]//g')

# Run the build
echo "Installing dependencies..."
npm install --no-save

echo "Copying assets..."
cp ./node_modules/payplug-ui-plugins-bo/js/app.js.map ./views/js/app-${version}.js.map
cp ./node_modules/payplug-ui-plugins-bo/js/app.js ./views/js/app-${version}.js
cp ./node_modules/payplug-ui-plugins-bo/js/chunk-vendors.js.map ./views/js/chunk-vendors-${version}.js.map
cp ./node_modules/payplug-ui-plugins-bo/js/chunk-vendors.js ./views/js/chunk-vendors-${version}.js
cp ./node_modules/payplug-ui-plugins-bo/css/app.css ./views/css/app-${version}.css
cp ./node_modules/payplug-ui-plugins-bo/img/*.svg ./views/img/
#
echo "Done."
