#!/usr/bin/env bash
set -euo pipefail

case "${1:-snapshot}" in
  check)
    php artisan openapi:snapshot --check
    ;;
  snapshot|update)
    php artisan openapi:snapshot
    ;;
  *)
    echo "Usage: bin/openapi.sh [snapshot|update|check]" >&2
    exit 64
    ;;
esac
