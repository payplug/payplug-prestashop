#!/bin/sh

## Auto index
"find ./vendor -type d -exec cp index.php {} \\;"

## Lib Phone Number
rm -rf vendor/giggsey/libphonenumberlight;
rm -rf vendor/giggsey/libphonenumber-for-php/src/carrier;
rm -rf vendor/giggsey/libphonenumber-for-php/src/geocoding;
rm -rf vendor/giggsey/libphonenumber-for-php/src/prefixmapper;
mv vendor/giggsey/libphonenumber-for-php vendor/giggsey/libphonenumberlight;
grep -rl 'libphonenumber' vendor/giggsey/libphonenumberlight/ | xargs sed -i '' -e 's/namespace libphonenumber;/namespace libphonenumberlight;/g';
grep -rl 'libphonenumber' vendor/giggsey/libphonenumberlight/ | xargs sed -i '' -e 's/use libphonenumber/use libphonenumberlight/g';

## Lib Payplug
apiRoute=vendor/payplug/payplug-php/lib/Payplug/Core/APIRoutes.php;

# Add tests routes if not already added
if ! grep -q "//APIRoutes::\$API_BASE_URL = " $apiRoute; then
grep -rl 'APIRoutes::\$API_BASE_URL' $apiRoute | xargs sed -i '' -e 's/APIRoutes::\$API_BASE_URL = /\/\/APIRoutes::\$API_BASE_URL = /g';

echo "if (isset(\$_SERVER['PAYPLUG_API_URL'])) {
    APIRoutes::\$API_BASE_URL = \$_SERVER['PAYPLUG_API_URL'];
} else {
    APIRoutes::\$API_BASE_URL = 'https://api.payplug.com';
}" >> $apiRoute;
fi;

# Generate optimized classes
composer dump-autoload -o;