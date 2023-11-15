#!/bin/sh

name="name"
commit="commit"

# Process all options supplied on the command line
while getopts "n:c:" flag
do
  case $flag in
    n) name=$OPTARG;;
    c) commit=$OPTARG;;
    *) eval echo "Unrecognized arg \$${OPTARG}"; usage; exit ;;
  esac
done

echo "Create tmp zip"
composer archive --file tmp --format zip

echo "Unzip tmp.zip..."
unzip tmp.zip -d tmp

echo "... then create empty log files for installation..."
echo "" > tmp/log/install-log.csv

echo "... then add composer.json to the temporary folder..."
cp composer.json tmp/composer.json

echo "... then add .htaccess to the temporary folder..."
cp ./.htaccess ./tmp/.htaccess

echo "... then list the file contained"
php dev/ci/list_module_files.php tmp

echo "move the file list"
cp tmp/module_files.csv module_files.csv

echo "Remove tmp files and folder"
rm -rf tmp tmp.zip

echo "Create repo for the module " ${name}
mkdir ${name}

echo "Then move file in created folder"
mv * ${name}
mv ./.htaccess ./${name}/.htaccess

echo "Retrieve composer.json file to create archive"
cp ./${name}/composer.json ./composer.json

echo "Create prod zip for archive: " ${name}_${commit}
composer archive --file ${name}_${commit} --format zip

echo "Create qa zip for archive: " ${name}_qa_${commit}
sed -i 's/api.payplug.com/api-qa.payplug.com/' ./${name}/vendor/payplug/payplug-php/lib/Payplug/Core/APIRoutes.php
composer archive --file ${name}_qa_${commit} --format zip