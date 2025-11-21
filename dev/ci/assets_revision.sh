#!/bin/sh

branch="payplug"
# Process all options supplied on the command line

echo "Moving file in views dir"$
ls -la ./views/css/
rm -rf ./views/js ./views/css
cp -r ./views/build/css ./views/
cp -r ./views/build/js ./views/
cp -r ./views/build/img ./views/
echo "***** DONE *****"

echo " FINISH !!!"
ls -l ./views/css/*.css
ls -l ./views/js/*.js
