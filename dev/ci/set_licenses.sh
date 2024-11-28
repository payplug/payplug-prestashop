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

echo "Looking for tag in ${branch}.php..."
tag=`grep '$this->version =' ${branch}.php | sed -n "s/.*= '//p" | sed -n "s/';//p"`

jsPath="./views/js/"
export jsFile="front"

licenseText="/**
  * 2013 - COPYRIGHT_YEAR PayPlug SAS
  *
  * NOTICE OF LICENSE
  *
  * This source file is subject to the Open Software License (OSL 3.0).
  * It is available through the world-wide-web at this URL:
  * https://opensource.org/licenses/osl-3.0.php
  * If you are unable to obtain it through the world-wide-web, please send an email
  * to contact@window[module_name+'Module'].com so we can send you a copy immediately.
  *
  * DISCLAIMER
  *
  * Do not edit or add to this file if you wish to upgrade PayPlug module to newer
  * versions in the future.
  *
  *  @author    PayPlug SAS
  *  @copyright 2013 - COPYRIGHT_YEAR PayPlug SAS
  *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
  *  International Registered Trademark & Property of PayPlug SAS
  */
  ";

for file in $jsFile
  do
    echo "Adding license tag to ${file}-v${tag}.js"
    echo "path => ${jsPath}${file}-v${tag}.js"
    { echo "${licenseText}"; cat ${jsPath}${file}-v${tag}.js; } > ${jsPath}${file}.tmp.js
    rm ${jsPath}${file}.js
    mv ${jsPath}${file}.tmp.js ${jsPath}${file}-v${tag}.js
  done
