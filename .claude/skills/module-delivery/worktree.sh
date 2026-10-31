#!/usr/bin/env bash
# Manage one worktree per issue.
#   worktree.sh create <module> <NN>   → .worktrees/<module>-<NN> on branch <module>/<NN>, from HEAD, with vendor and .env
#   worktree.sh remove <module> <NN>   → removes the worktree and its branch (after merge)
set -euo pipefail
ROOT=$(git rev-parse --show-toplevel)
cmd=$1 module=$2 nn=$3
wt="$ROOT/.worktrees/$module-$nn" branch="$module/$nn"
case "$cmd" in
  create)
    git -C "$ROOT" worktree add -q "$wt" -b "$branch"
    cp -a "$ROOT/vendor" "$wt/vendor"
    cp "$ROOT/.env" "$wt/.env"
    "$(dirname "$0")/run.sh" "$wt" "composer dump-autoload -q"
    echo "$wt"
    ;;
  remove)
    git -C "$ROOT" worktree remove --force "$wt"
    git -C "$ROOT" branch -D "$branch"
    ;;
  *) echo "usage: worktree.sh create|remove <module> <NN>" >&2; exit 2 ;;
esac
