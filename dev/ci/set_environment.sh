#!/bin/bash

# Usage: dev/ci/set_environment.sh <qa|prod>
# Applies the build-time settings of the target environment to the module sources.

set -euo pipefail

if [ "$#" -ne 1 ] || [ -z "$1" ]; then
  echo "Usage: $0 <qa|prod>" >&2
  exit 1
fi

target_env=$(printf '%s' "$1" | tr '[:upper:]' '[:lower:]')
module_root=$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)
widget_action="${module_root}/src/actions/ScalapayWidgetAction.php"
placeholder="const WIDGET_ENVIRONMENT = '';"
qa_value="const WIDGET_ENVIRONMENT = 'integration';"

count_occurrences() {
  { grep -oF "$1" "$2" || true; } | wc -l | tr -d ' '
}

case "$target_env" in
  qa)
    if [ ! -f "$widget_action" ]; then
      echo "Error: ${widget_action} not found" >&2
      exit 1
    fi

    found=$(count_occurrences "$placeholder" "$widget_action")
    if [ "$found" -ne 1 ]; then
      echo "Error: expected \"${placeholder}\" once in ${widget_action}, found ${found} times" >&2
      exit 1
    fi

    tmp_file=$(mktemp)
    trap 'rm -f "$tmp_file"' EXIT
    sed "s/${placeholder}/${qa_value}/" "$widget_action" > "$tmp_file"
    cat "$tmp_file" > "$widget_action"

    if [ "$(count_occurrences "$qa_value" "$widget_action")" -ne 1 ] \
      || [ "$(count_occurrences "$placeholder" "$widget_action")" -ne 0 ]; then
      echo "Error: the Scalapay widget environment was not set to integration in ${widget_action}" >&2
      exit 1
    fi

    echo "qa: Scalapay widget environment set to integration"
    ;;
  prod)
    echo "prod: no environment setting to apply"
    ;;
  *)
    echo "Error: unknown environment \"$1\", expected qa or prod" >&2
    exit 1
    ;;
esac
