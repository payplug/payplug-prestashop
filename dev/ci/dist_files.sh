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
modules="modules/$branch"
if [ -d "$modules" ]; then
  path="$PWD/${modules}/dev/dist/$branch"
else
  path="$PWD/dev/dist/$branch"
fi

echo "Dist files will be in: "$path
echo "------------------"
export distFile="composer.json composer.lock features.json package.json package-lock.json logo.gif logo.png"
for file in $distFile
  do
    echo -n "Copy $file "
    if [ -d "$modules" ]; then
      cp -v $path/${file} $PWD/${modules}/${file}
    else
      cp -v $path/${file} $PWD/${file}
    fi
  done

echo -n "Copy include.less "
  if [ -d "$modules" ]; then
    cp -v $path/include.less $PWD/${modules}/dev/css/less/include.less
  else
    cp -v $path/include.less $PWD/dev/css/less/include.less
  fi


echo "End script dist-files"
echo "------------------"