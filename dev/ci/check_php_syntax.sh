#!/bin/sh

# Function to get all PHP files in a directory recursively
get_php_files() {
    path="$1"
    find "$path" -type f -name "*.php" ! -name "index.php"
}

# get the directory to scan
base_dir=$(dirname "$(dirname "$(dirname "$0")")")

# Get all PHP files
files=$(get_php_files "$base_dir")

# Check syntax of each PHP file
errors=""
need_error_return=false
for file in $files; do
    check=$(php -l "$file")

    if echo "$check" | grep -qv "No syntax errors detected"; then
        if echo "$check" | grep -q "Fatal error:"; then
            need_error_return=true
            errors="$errors\n$check"
        else
            errors="$errors\n$check"
        fi
    fi
done

# If errors are detected, print them and return appropriate code
if [ -n "$errors" ]; then
    echo -e "$errors"

    if [ "$need_error_return" = true ]; then
        exit 1
    fi

    exit 137
fi

# Return ok
exit 0