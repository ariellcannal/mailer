#!/usr/bin/env bash
# deploy.sh – Script de deploy compatível com Ubuntu (heartbeats, timeouts, git clean, composer dist)

# ===================== Helpers =====================
# Exibe mensagens com carimbo de data/hora
stage() { echo "[$(date '+%F %T')] $*"; }

# Executa comandos com timeout quando disponível
do_timeout() {
  if command -v timeout >/dev/null 2>&1; then
    timeout --preserve-status "$TIMEOUT_SECS" "$@"
  else
    "$@"
  fi
}

set -euo pipefail

# ===================== Configs =====================
RUN_AS="cannal"                      # usuário dono do site
TIMEOUT_SECS="${TIMEOUT_SECS:-1800}" # tempo máx (30 min)
HEARTBEAT_SECS="${HEARTBEAT_SECS:-30}" # intervalo de heartbeat
REPO_SSH_URL="${REPO_SSH_URL:-git@github.com:ariellcannal/mailer.git}" 

# ===================== Helpers =====================
# Exibe mensagens com carimbo de data/hora
stage() { echo "[$(date '+%F %T')] $*"; }

# Executa comandos com timeout quando disponível
do_timeout() {
  if command -v timeout >/dev/null 2>&1; then
    timeout --preserve-status "$TIMEOUT_SECS" "$@"
  else
    "$@"
  fi
}

# ===================== Reexecuta como usuário correto =====================
if [ "$(id -un)" != "$RUN_AS" ]; then
  if command -v sudo >/dev/null 2>&1; then
    exec sudo -u "$RUN_AS" -H bash "$0"
  else
    echo "Este script deve ser executado como $RUN_AS" >&2
    exit 1
  fi
fi

# ===================== Diretórios e ambiente =====================
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Arquivo de lock utilizando flock
LOCKFILE="$SCRIPT_DIR/deploy.lock"
exec 9>"$LOCKFILE"
if ! flock -n 9; then
  echo "Outro deploy em andamento (lock: $LOCKFILE)"
  exit 0
fi
cleanup() { rm -f "$LOCKFILE"; }
trap cleanup EXIT

REPO_SSH_URL="${REPO_SSH_URL:-git@github.com:ariellcannal/mailer.git}"

# ===================== Reexecuta como usuário correto =====================
if [ "$(id -un)" != "$RUN_AS" ]; then
  if command -v sudo >/dev/null 2>&1; then
    exec sudo -u "$RUN_AS" -H bash "$0"
  else
    echo "Este script deve ser executado como $RUN_AS" >&2
    exit 1
  fi
fi

# ===================== Diretórios e ambiente =====================
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

# Arquivo de lock utilizando flock
LOCKFILE="$SCRIPT_DIR/deploy.lock"
exec 9>"$LOCKFILE"
if ! flock -n 9; then
  echo "Outro deploy em andamento (lock: $LOCKFILE)"
  exit 0
fi
cleanup() { rm -f "$LOCKFILE"; }
trap cleanup EXIT

LOG_DIR="$SCRIPT_DIR/application/logs"
LOG_FILE="$LOG_DIR/deploy.log"

# Ambiente consistente p/ Git/Composer
export HOME="/home/$RUN_AS"
export COMPOSER_HOME="$HOME/.composer"
export PATH="$HOME/.config/composer/vendor/bin:/usr/local/bin:/usr/bin:/bin:$PATH"

mkdir -p "$LOG_DIR" "$COMPOSER_HOME"


LOG_DIR="$SCRIPT_DIR/application/logs"
LOG_FILE="$LOG_DIR/deploy.log"

# Ambiente consistente p/ Git/Composer
export HOME="/home/$RUN_AS"
export COMPOSER_HOME="$HOME/.composer"
export PATH="$HOME/.config/composer/vendor/bin:/usr/local/bin:/usr/bin:/bin:$PATH"

mkdir -p "$LOG_DIR" "$COMPOSER_HOME"

LOG_DIR="$SCRIPT_DIR/application/logs"
LOG_FILE="$LOG_DIR/deploy.log"

# Ambiente consistente p/ Git/Composer
export HOME="/home/$RUN_AS"
export COMPOSER_HOME="$HOME/.composer"
export PATH="$HOME/.config/composer/vendor/bin:/usr/local/bin:/usr/bin:/bin:$PATH"

mkdir -p "$LOG_DIR" "$COMPOSER_HOME"

LOG_DIR="$SCRIPT_DIR/application/logs"
LOG_FILE="$LOG_DIR/deploy.log"

# Ambiente consistente p/ Git/Composer
export HOME="/home/$RUN_AS"
export COMPOSER_HOME="$HOME/.composer"
export PATH="$HOME/.config/composer/vendor/bin:/usr/local/bin:/usr/bin:/bin:$PATH"

mkdir -p "$LOG_DIR" "$COMPOSER_HOME"

# ===================== Logs (só depois do lock) =====================
exec > >(tee -a "$LOG_FILE") 2>&1

echo "-------------------------------"
echo "[$(date '+%F %T')] Iniciando deploy em $SCRIPT_DIR (user: $(id -un), pid: $$, timeout: ${TIMEOUT_SECS}s)"

# Heartbeat para acompanhar progresso
(
  while sleep "$HEARTBEAT_SECS"; do
    echo "[$(date '+%F %T')] heartbeat: deploy em andamento (pid $$)"
  done
) &
HB_PID=$!
trap 'kill "$HB_PID" 2>/dev/null || true; cleanup' EXIT

# ===================== GIT =====================
stage "Git: configurar safe.directory (global, com HOME definido)"
git config --global --add safe.directory "$SCRIPT_DIR" || true

if ! git remote | grep -qx 'origin'; then
  stage "Git: adicionando remote origin $REPO_SSH_URL"
  git remote add origin "$REPO_SSH_URL"
fi
git remote set-url origin "$REPO_SSH_URL"

stage "Git: fetch --prune origin (timeout)"
do_timeout git fetch --prune origin

# Branch alvo
if git rev-parse --verify origin/main >/dev/null 2>&1; then
  BRANCH="main"
elif git rev-parse --verify origin/master >/dev/null 2>&1; then
  BRANCH="master"
else
  BRANCH="$(git symbolic-ref --short HEAD 2>/dev/null || echo main)"
fi
stage "Branch alvo: $BRANCH"

# Working tree limpo
stage "Git: reset --hard e clean -fd"
git reset --hard
git clean -fd

stage "Git: checkout -B $BRANCH origin/$BRANCH"
git checkout -B "$BRANCH" "origin/$BRANCH"

stage "Git: reset --hard origin/$BRANCH"
git reset --hard "origin/$BRANCH"

# Atualiza submódulos somente quando configurados
if [ -f .gitmodules ] && git config --file .gitmodules --name-only --get-regexp '^submodule\.' >/dev/null 2>&1; then
  stage "Git: submodule sync and update --remote"
  git submodule sync --recursive
  git submodule update --init --recursive --remote
fi

# ===================== COMPOSER =====================
stage "Composer: preferir dist (usar flag na instalação)"
# Evitar composer config -g para não depender do HOME; a flag --prefer-dist resolve.

# Se houver qualquer .git dentro de vendor, reinstalar limpo
if find vendor -type d -name ".git" | grep -q . 2>/dev/null; then
  stage "Composer: detectado .git em vendor — removendo vendor/ para instalação limpa"
  rm -rf vendor
fi

# ===================== COMPOSER =====================
stage "Composer: removendo vendor/ para instalação limpa"
rm -rf vendor

stage "Composer: clear-cache"
composer clear-cache || true

stage "Composer: self-update"
# Atualiza o Composer apenas se o binário for gravável
COMPOSER_BIN="$(command -v composer || true)"
if [ -n "$COMPOSER_BIN" ] && [ -w "$COMPOSER_BIN" ]; then
  composer self-update --2 --no-interaction || true
else
  stage "Composer: sem permissão para self-update, prosseguindo"
fi

# Valida composer.json sem exigir composer.lock
stage "Composer: validate"
if ! composer validate --no-check-lock --no-check-publish; then
  stage "Composer: validação falhou"
  exit 1
fi

stage "Composer: update --no-dev --prefer-dist --optimize-autoloader --no-progress (timeout)"
do_timeout composer update --no-interaction --prefer-dist --no-dev --optimize-autoloader --no-progress

stage "Deploy OK"
echo "[$(date '+%F %T')] Deploy OK"
