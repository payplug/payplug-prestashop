#!/bin/sh

export folderList="src vendor log controllers"

if [ -d "./vendor" ]; then
  echo "copying index.php recursively into vendor folders"
  find ./vendor -type d -exec cp -v index.php {} \;
fi

if [ -d "./dist" ]; then
  echo "copying index.php recursively into dist folders"
  find ./dist -type d -exec cp -v index.php {} \;
fi

for folder in $folderList
  do
    echo "copying htaccess recursively into folder "${folder}
    find ./${folder} -type d -not -path "*.github*" -exec cp -v ./dev/ci/.htaccess {} \;
  done

echo "All directories secured"