#!/bin/sh

export folderList="src vendor log controllers"

for folder in $folderList
  do
    if [ "$folder" = "vendor" ]; then
      echo "copying index.php recursively into vendor folders"
      find ./vendor -type d -exec cp index.php {} \;
    fi
    echo "copying htaccess recursively into folder "${folder}
    find ./${folder} -type d -exec cp ./dev/ci/.htaccess {} \;
  done

echo "All directories secured"