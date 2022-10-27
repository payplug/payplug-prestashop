#!/bin/sh

export languageCodes="de pt nl"

currDir=${PWD##*/}

if [ $currDir = "ci" ]; then
    racine=".."
  else
    racine="."
fi

for lang in $languageCodes
  do
    cp $racine/translations/en.php $racine/translations/${lang}.php
  done

echo "Created fake translations"
