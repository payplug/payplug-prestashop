#!/bin/sh

export languageCodes="es de pt nl"

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