#!/usr/bin/env bash

set -euo pipefail

if [ ! -x "./vendor/bin/sail" ]; then
    echo "Laravel Sail nao encontrado em ./vendor/bin/sail. Rode 'composer install' primeiro." >&2
    exit 1
fi

if [ "$#" -eq 0 ]; then
    exec ./vendor/bin/sail
fi

case "$1" in
    up|down|start|stop|restart|build|ps|logs|shell|bash|root-shell|share|open|php|composer|artisan|npm|pnpm|yarn|node|mysql|mariadb|pgsql|redis|test)
        exec ./vendor/bin/sail "$@"
        ;;
    *)
        exec ./vendor/bin/sail artisan "$@"
        ;;
esac
