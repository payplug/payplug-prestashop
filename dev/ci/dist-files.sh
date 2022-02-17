#!/bin/sh


if [[ "$1" != "" ]]; then
    branch="$1"
else
    branch="payplug"
fi

if [[ "$2" != "" ]]; then
    file="$2"
else
    file=""
fi

echo "Branch name: "$branch
echo "------------------"

path="./dev/dist/$branch"

echo "Dist files will be in: "$path
echo "------------------"

if [[ "$2" != "" ]]; then
    file="$2"

    if [[ "$file" == "less" ]]; then
        echo "Copy var.less"
        cp $path/var.less ./views/css/less/var.less
    else
        echo "Copy $file"
        cp $path/${file} ./${file}
    fi
else
    export distFile="composer.json composer.lock features.json"
    for file in $distFile
      do
        echo "Copy $file"
        cp $path/${file} ./${file}
      done

    echo "Copy var.less"
    cp $path/var.less ./views/css/less/var.less
fi

echo "End script dist-files"