#!/bin/sh

# source ~/.bash_profile

composer install;

## Lib Payplug
apiRoute=./vendor/payplug/payplug-php/lib/Payplug/Core/APIRoutes.php;

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
