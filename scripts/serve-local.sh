#!/usr/bin/env bash
# Local development only: concurrent bootstrap reads must not queue behind one PHP worker.
set -euo pipefail
cd "$(dirname "$0")/.."
co_api_workers="${CO_API_WORKERS:-4}"
if ! [[ "$co_api_workers" =~ ^[0-9]+$ ]] || (( co_api_workers < 1 || co_api_workers > 16 )); then
  echo 'CO_API_WORKERS must be between 1 and 16.' >&2
  exit 2
fi
export PHP_CLI_SERVER_WORKERS="$co_api_workers"
exec "${CO_PHP_BINARY:-php8.5}" -S localhost:8000 -t public
