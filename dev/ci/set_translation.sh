#!/bin/sh

echo "Set translation module_name to " . branch;

#grep -rl `basename mod=\'payplug\'` ./views/templates/ | xargs sed -i -e 's/'`basename mod=\'payplug\'`'/'`basename mod=\'pspaylater\'`'/g';
git grep -rl `basename mod=\'payplug\'` ./views/templates/ | xargs sed -i -e 's/'`basename mod=\'payplug\'`'/'`basename mod=\'pspaylater\'`'/g';

echo "Remove temporay file";
rm ./*.tpl-e ./*/*.tpl-e ./**/*.tpl-e;

echo "End";