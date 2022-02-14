#!/bin/sh

for entry in `find . -name "*.php"`; do
    php -l $entry
done