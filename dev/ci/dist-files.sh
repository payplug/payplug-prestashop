#!/bin/sh


if [[ "$1" != "" ]]; then
    branch="$1"
else
    branch="payplug"
fi

echo "Branch name: "$branch
echo "------------------"

path="./dev/dist/$branch"
echo "Dist files will be in: "$path
echo "------------------"

echo "Copy composer.json"
cp $path/composer.json ./composer.json
echo "------------------"

echo "Copy composer.lock"
cp $path/composer.lock ./composer.lock
echo "------------------"

echo "Copy features.json"
cp $path/features.json ./features.json
echo "------------------"

echo "End script dist-files"