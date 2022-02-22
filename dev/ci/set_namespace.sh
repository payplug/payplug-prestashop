#!/bin/sh

echo "Set namespance PayPlugModule to PayLaterModule";

#grep -rl `basename PayPlugModule` . ':!dev/ci/set_namespace.sh' | xargs sed -i -e 's/'`basename PayPlugModule`'/'`basename PayLaterModule`'/g';
git grep -rl `basename PayPlugModule` . ':!dev/ci/set_namespace.sh' | xargs sed -i -e 's/'`basename PayPlugModule`'/'`basename PayLaterModule`'/g';

echo "Remove temporay file";
rm ./*.php-e ./*/*.php-e ./*/*/*.php-e ./*/*/*/*.php-e;

echo "End";