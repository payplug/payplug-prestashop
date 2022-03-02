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

echo "Starting files compilation"
export lessFiles="admin admin_order front front_1_6"
for file in $lessFiles
  do
    lessc $PWD/modules/$branch//views/css/${file}.less $PWD/modules/$branch//views/css/${file}.css
  done
echo "***** DONE *****"
