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

versionning_assets() {
  extension=$1
  version=$2
  for file in views/$extension/*.$extension;do
      if [[ `basename ${file}` == *"-v3"* ]]; then
        echo "File" `basename ${file}` "already versioned: cannot perform script"
        exit 1
      fi
      echo `basename ${file}`
      mv ${file} ${file%%.*}'-v'$version'.'$extension
      # git grep -rl `basename ${file}` . ':!upgrade/*.php' ':!assets_revision.sh'
      git grep -rl `basename ${file}` . ':!upgrade/*.php' ':!dev/ci/assets_revision.sh' | xargs sed -i -e 's/'`basename ${file}`'/'`basename ${file%%.*}`'-v'$version'.'$extension'/g';
  done
}

echo "Looking for tag in ${branch}.php..."
tag=`grep '$this->version =' ${branch}.php | sed -n "s/.*= '//p" | sed -n "s/';//p"`

echo "Moving file in views dir"
rm -rf ./views/js ./views/css
cp ./views/templates/index.php ./views/css/index.php
cp -r ./views/build/css ./views/
cp -r ./views/build/js ./views/
echo "***** DONE *****"

echo "Starting versioning JS file with" $tag "tag..."
versionning_assets 'js' $tag
echo "***** DONE *****"

echo "Starting versioning CSS file with '" $tag "' tag..."
versionning_assets 'css' $tag
echo "***** DONE *****"

echo " FINISH !!!"
ls -l ./views/css/*.css
ls -l ./views/js/*.js
