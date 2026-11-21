#!/usr/bin/env bash
# The quick check while working on one or more modules: `composer check:module offers` or `composer check:module offers,catalogue`.
# Pint on the modules' code and tests, deptrac on the whole app (module boundaries), phpstan on the modules' code, then phpunit on the modules'
# tests plus the architecture tests (boundary and test-presence scanners). The full `composer check` still decides
# whether a task is done, since a change in one module can break another module's tests.
set -euo pipefail

if [[ $# -lt 1 || -z "$1" ]]; then
    echo "Usage: composer check:module <module>[,<module>...]   e.g. composer check:module offers,catalogue" >&2
    exit 2
fi

pint_paths=()
stan_paths=()
test_paths=()

IFS=',' read -ra names <<< "$1"
for name in "${names[@]}"; do
    name="${name// /}"
    [[ -z "$name" ]] && continue
    module=$(find app/Modules -mindepth 1 -maxdepth 1 -type d -iname "$name" -printf '%f\n' | head -n 1)
    if [[ -z "$module" ]]; then
        echo "Unknown module: $name (known: $(ls app/Modules | tr '\n' ' '))" >&2
        exit 2
    fi
    pint_paths+=("app/Modules/$module")
    stan_paths+=("app/Modules/$module")
    for dir in "tests/Unit/Modules/$module" "tests/Feature/Modules/$module"; do
        if [[ -d "$dir" ]]; then
            pint_paths+=("$dir")
            test_paths+=("$dir")
        fi
    done
done

echo "== pint: ${pint_paths[*]}"
vendor/bin/pint --test "${pint_paths[@]}"

echo "== deptrac (whole app)"
vendor/bin/deptrac analyse --no-progress

echo "== phpstan: ${stan_paths[*]}"
vendor/bin/phpstan analyse --no-progress "${stan_paths[@]}"

echo "== phpunit: tests/Unit/Architecture ${test_paths[*]}"
php artisan config:clear --ansi
php artisan test tests/Unit/Architecture "${test_paths[@]}"
