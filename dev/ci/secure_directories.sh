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

echo "All directories secured"