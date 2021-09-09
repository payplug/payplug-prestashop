#!/bin/sh

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
      # git grep -rl `basename ${file}` . ':!upgrade/*.php' ':!assets.sh'
      git grep -rl `basename ${file}` . ':!upgrade/*.php' ':!ci/assets.sh' | xargs sed -i -e 's/'`basename ${file}`'/'`basename ${file%%.*}`'-v'$version'.'$extension'/g';
  done
}

echo "Looking for tag in payplug.php..."
tag=`grep '$this->version =' payplug.php | sed -n "s/.*= '//p" | sed -n "s/';//p"`

echo "Starting files compilation"
export lessFiles="admin admin_order front front_1_6"
for file in $lessFiles
  do
    lessc ./views/css/${file}.less ./views/css/${file}.css
  done
echo "***** DONE *****"

echo "Starting versioning JS file with" $tag "tag..."
versionning_assets 'js' $tag
echo "***** DONE *****"

echo "Starting versioning CSS file with '" $tag "' tag..."
versionning_assets 'css' $tag
echo "***** DONE *****"

echo "Moving file in build dir"
mkdir ./views/build
mkdir ./views/build/css
mkdir ./views/build/js
mv ./views/css/*.css ./views/build/css
mv ./views/js/* ./views/build/js
echo "***** DONE *****"

echo " FINISH !!!"
ls -l ./views/build/css/*.css
ls -l ./views/build/js/*.js
