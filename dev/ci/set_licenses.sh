

jsPath="./views/js/"
export jsFile="components"

licenseText="/**
  * 2013 - 2022 PayPlug SAS
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
  *  @copyright 2013 - 2022 PayPlug SAS
  *  @license   https://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
  *  International Registered Trademark & Property of PayPlug SAS
  */
  ";

for file in $jsFile
  do
    echo "Adding license tag to ${jsFile}.js"
    { echo "${licenseText}"; cat ${jsPath}${jsFile}.js; } > ${jsPath}${jsFile}.tmp.js
    rm ${jsPath}${jsFile}.js
    mv ${jsPath}${jsFile}.tmp.js ${jsPath}${jsFile}.js
  done
