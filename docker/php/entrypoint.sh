#!/bin/sh
set -e

# Same image is used for the app (php-fpm), queue worker and scheduler
# containers — compose.yaml selects the role via `command:`. This entrypoint
# intentionally does nothing environment-specific; it exists as a single
# place to add startup checks later without touching all three services.

exec "$@"
