#!/bin/sh

echo "get current year"

year=$(date +%Y)
echo "Year: " ${year}

echo "Replace date in php file"
find . -name "*.php" | xargs -n 1 sed -i -e "s|COPYRIGHT_YEAR|${year}|g"

echo "Replace date in js file"
find . -name "*.js" | xargs -n 1 sed -i -e "s|COPYRIGHT_YEAR|${year}|g"

echo "Replace date in less file"
find . -name "*.js" | xargs -n 1 sed -i -e "s|COPYRIGHT_YEAR|${year}|g"

echo "Copyright date setted"