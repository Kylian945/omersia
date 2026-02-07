#!/usr/bin/env bash
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="$(cd "${SCRIPT_DIR}/.." && pwd)"
ROOT_DIR="$(cd "${BACKEND_DIR}/.." && pwd)"

info() {
  printf "[laravel11] %s\n" "$*"
}

fail() {
  printf "[laravel11] ERROR: %s\n" "$*" >&2
  exit 1
}

DOCKER_COMPOSE_CMD=()
if command -v docker >/dev/null 2>&1; then
  if docker compose version >/dev/null 2>&1; then
    DOCKER_COMPOSE_CMD=(docker compose)
  elif command -v docker-compose >/dev/null 2>&1; then
    DOCKER_COMPOSE_CMD=(docker-compose)
  fi
fi

is_backend_running() {
  if [[ ${#DOCKER_COMPOSE_CMD[@]} -eq 0 ]]; then
    return 1
  fi
  local cid
  cid="$("${DOCKER_COMPOSE_CMD[@]}" -f "${ROOT_DIR}/docker-compose.yml" ps -q backend 2>/dev/null | head -n1)"
  [[ -n "${cid}" ]] || return 1
  docker inspect -f '{{.State.Running}}' "${cid}" 2>/dev/null | grep -q true
}

RUNNER="local"
if [[ -f "/.dockerenv" ]]; then
  RUNNER="local"
elif is_backend_running; then
  RUNNER="docker"
fi

run_cmd() {
  if [[ "${RUNNER}" == "docker" ]]; then
    "${DOCKER_COMPOSE_CMD[@]}" -f "${ROOT_DIR}/docker-compose.yml" exec -T -w /var/www/html backend "$@"
  else
    (cd "${BACKEND_DIR}" && "$@")
  fi
}

info "Runner: ${RUNNER}"
info "Backend dir: ${BACKEND_DIR}"

run_cmd test -f composer.json || fail "composer.json not found."
run_cmd test -d vendor || fail "vendor/ not found. Run composer install/update first."

info "PHP version"
run_cmd php -v | head -n 1

info "Laravel version"
run_cmd php artisan --version

info "Composer validate"
run_cmd composer validate --no-check-publish

if [[ "${SKIP_PHPSTAN:-0}" != "1" ]]; then
  if run_cmd test -x vendor/bin/phpstan; then
    info "PHPStan"
    run_cmd vendor/bin/phpstan analyse --memory-limit=2G
  else
    info "PHPStan skipped (vendor/bin/phpstan not found)"
  fi
else
  info "PHPStan skipped (SKIP_PHPSTAN=1)"
fi

if [[ "${SKIP_PINT:-0}" != "1" ]]; then
  if run_cmd test -x vendor/bin/pint; then
    info "Pint (lint)"
    run_cmd vendor/bin/pint --test
  else
    info "Pint skipped (vendor/bin/pint not found)"
  fi
else
  info "Pint skipped (SKIP_PINT=1)"
fi

if [[ "${SKIP_TESTS:-0}" != "1" ]]; then
  info "PHPUnit"
  if run_cmd test -x vendor/bin/phpunit; then
    run_cmd vendor/bin/phpunit --no-coverage
  else
    run_cmd php artisan test
  fi
else
  info "PHPUnit skipped (SKIP_TESTS=1)"
fi

info "Validation complete."
