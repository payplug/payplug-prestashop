#!/bin/sh


if [[ "$1" != "" ]]; then
    branch="$1"
    echo "Branch name: "$branch
else
    echo "No branch name given";
    exit;
fi

path="./../dist/$branch/"

echo "Copy composer.json"
echo "Copy composer.lock"
echo "Copy feature.json"