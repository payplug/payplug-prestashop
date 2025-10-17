#!/bin/sh

echo "Set translation";

mode="normal"
mod="payplug"
newmod="pspaylater"
# Process all options supplied on the command line
while getopts m: flag;
do
    case "${flag}" in
        m) mode=${OPTARG};;
        *) eval echo "Unrecognized arg \$${OPTARG}"; usage; exit ;;
    esac
done

if  [ "$mode" = "reverse" ]; then
  echo "Current mode is ${mode}, convert translation mod back to payplug"
  mod="pspaylater"
  newmod="payplug"
else
  if [ "$mode" = "normal" ]; then
  echo "Default mode (${mode}), convert mod to pspaylater"
  fi
fi

#grep -rl `basename mod=\'payplug\'` ./views/templates/ | xargs sed -i -e 's/'`basename mod=\'payplug\'`'/'`basename mod=\'pspaylater\'`'/g';
git grep -rl `basename mod=\'${mod}\'` ./views/templates/ | xargs sed -i -e 's/'`basename mod=\'${mod}\'`'/'`basename mod=\'${newmod}\'`'/g';

echo "Remove temporay file";
rm ./*.tpl-e ./*/*.tpl-e ./**/*.tpl-e;

echo "End";