#!/bin/sh

# source ~/.bash_profile

## Lib Phone Number
rm -rf ./vendor/giggsey/libphonenumberlight;
rm -rf ./vendor/giggsey/libphonenumber-for-php/src/carrier;
rm -rf ./vendor/giggsey/libphonenumber-for-php/src/geocoding;
rm -rf ./vendor/giggsey/libphonenumber-for-php/src/prefixmapper;
mv ./vendor/giggsey/libphonenumber-for-php ./vendor/giggsey/libphonenumberlight;
grep -rl 'libphonenumber' ./vendor/giggsey/libphonenumberlight/ | xargs sed -i '' -e 's/namespace libphonenumber;/namespace libphonenumberlight;/g';
grep -rl 'libphonenumber' ./vendor/giggsey/libphonenumberlight/ | xargs sed -i '' -e 's/use libphonenumber/use libphonenumberlight/g';

# Generate optimized classes
composer dump-autoload -o;

# Install webpack
echo "installing webpack"
nvm install
npm install --save-dev webpack
npm install --save-dev webpack-cli
