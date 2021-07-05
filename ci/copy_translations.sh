#!/bin/sh

export languageCodes="es de pt nl"

for lang in $languageCodes
  do
    cp ../translations/en.php ../translations/${lang}.php
  done

echo "Created fake translations"
