#!/usr/bin/env bash
# Run a command inside the Laradock workspace container (PHP 8.5), in the given host directory.
# Usage: run.sh <host-dir> <command...>     e.g. run.sh .worktrees/users-01 composer check
set -euo pipefail
HOST_ROOT=/home/kp/laravel/laradock8_5
dir=$(cd "$1" && pwd); shift
[[ "$dir" == "$HOST_ROOT"/* ]] || { echo "run.sh: $dir is outside $HOST_ROOT" >&2; exit 2; }
docker compose -f "$HOST_ROOT/laradock/docker-compose.yml" --project-directory "$HOST_ROOT/laradock" \
  run --rm --no-deps -T --user laradock -w "/var/www${dir#$HOST_ROOT}" workspace bash -lc "$*"
