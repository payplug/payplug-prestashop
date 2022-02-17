#!/bin/sh

branch="payplug"
# Process all options supplied on the command line
while getopts b: flag;
do
    case "${flag}" in
        b) branch=${OPTARG};;
        *) eval echo "Unrecognized arg \$${OPTARG}"; usage; exit ;;
    esac
done

echo "Branch name: "$branch
echo "------------------"

path="./dev/dist/$branch"

echo "Dist files will be in: "$path
echo "------------------"

export distFile="composer.json composer.lock features.json"
for file in $distFile
  do
    echo "Copy $file"
    cp $path/${file} ./${file}
  done

echo "Copy var.less"
cp $path/var.less ./views/css/less/var.less

echo "End script dist-files"
echo "------------------"