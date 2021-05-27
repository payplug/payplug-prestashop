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
      git grep -rl `basename ${file}` . ':!upgrade/*.php' ':!assets_revision.sh'
      git grep -rl `basename ${file}` . ':!upgrade/*.php' ':!assets_revision.sh' | xargs sed -i -e 's/'`basename ${file}`'/'`basename ${file%%.*}`'-v'$version'.'$extension'/g';
  done
}

echo "Looking for tag in payplug.php..."
tag=`grep '$this->version =' payplug.php | sed -n "s/.*= '//p" | sed -n "s/';//p"`

echo "Starting versioning JS file with" $tag "tag..."
versionning_assets 'js' $tag
echo "***** DONE *****"

echo "Starting versioning CSS file with '" $tag "' tag..."
versionning_assets 'css' $tag
echo "***** DONE *****"

echo " FINISH !!!"
ls -l views/js/*.js
ls -l views/css/*.css