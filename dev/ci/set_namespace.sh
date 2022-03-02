#!/bin/sh

echo "Set namespance PayPlugModule to PayLaterModule";

#grep -rl `basename PayPlugModule` . ':!dev/ci/set_namespace.sh' | xargs sed -i -e 's/'`basename PayPlugModule`'/'`basename PayLaterModule`'/g';
git grep -rl `basename PayPlugModule` . ':!dev/ci/set_namespace.sh' | xargs sed -i -e 's/'`basename PayPlugModule`'/'`basename PayLaterModule`'/g';

#rename the front controllers files

echo "Renaming the front controllers files to begin with bnpl"

git grep -rl 'class Payplug' ./controllers/front | xargs sed -i -e 's/class Payplug/class bnpl/g';

echo "Remove temporay file";
rm ./*.php-e ./*/*.php-e ./*/*/*.php-e ./*/*/*/*.php-e;

echo "End";