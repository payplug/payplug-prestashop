#!/bin/sh

#Usage: dev/ci/rebase -b [target] -f [from]

target="develop"
from=$(git rev-parse --abbrev-ref HEAD)
current=$(git rev-parse --abbrev-ref HEAD)

# Process all options supplied on the command line
while getopts "b:f:" flag
do
  case $flag in
    b) target=$OPTARG;;
    f) from=$OPTARG;;
    *) eval echo "Unrecognized arg \$${OPTARG}"; usage; exit ;;
  esac
done

echo "🔄 Checkout and update: " ${target}
git checkout ${target} && git pull
if [ $? -ne 0 ]; then
    echo "❌ An error occured while updating the branch: " ${target}
    exit
fi

echo "Go to the branch to rebase and update: " ${from}
git checkout ${from} && git rebase origin/${target}
if [ $? -ne 0 ]; then
    echo "❌ An error occured during branch rebase, please merge conflict"
    exit
fi

echo "✅ Branch is successfully rebase"
git push -f

if [ $current != $from ]; then
  echo "👀 Go back to working branch"
  git checkout ${current}
fi


