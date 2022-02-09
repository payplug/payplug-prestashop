#!/bin/sh

echo "Starting files compilation"
export lessFiles="admin admin_order front front_1_6"
for file in $lessFiles
  do
    lessc ./views/css/${file}.less ./views/css/${file}.css
  done
echo "***** DONE *****"
