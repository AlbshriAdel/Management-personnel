#!/bin/bash

set -e

ENCRYPTION_CONF_FILE_PATH="config/packages/config/encryption.yaml"
BIN_CONSOLE_PATH="bin/console"
SED_SEPARATOR_CHARACTER="~"

function logDebug() {
  echo -e "[Debug] ${1}"
}

function trim() {
  echo $(echo "${1}" | sed 's/^ *//;s/ *$//')
}

function getEnvValue() {
  MATCH=$(cat .env | grep -iP "${1}[ ]?=[ ]?(.*)" | head -1 | sed s/.*=//);
  echo $(trim "$MATCH");
}

function createDirs() {
  logDebug "Creating directories"

  UPLOAD_DIR=$(getEnvValue "UPLOAD_DIR");
  IMAGES_UPLOAD_DIR=$(getEnvValue "IMAGES_UPLOAD_DIR");
  FILES_UPLOAD_DIR=$(getEnvValue "FILES_UPLOAD_DIR");
  VIDEOS_UPLOAD_DIR=$(getEnvValue "VIDEOS_UPLOAD_DIR");
  MINIATURES_UPLOAD_DIR=$(getEnvValue "MINIATURES_UPLOAD_DIR");
  SCIENTIFIC_PAPERS_UPLOAD_DIR=$(getEnvValue "SCIENTIFIC_PAPERS_UPLOAD_DIR");
  PUBLIC_ROOT_DIR=$(getEnvValue "PUBLIC_ROOT_DIR");

  mkdir -p "$PUBLIC_ROOT_DIR";
  mkdir -p "$PUBLIC_ROOT_DIR/$UPLOAD_DIR/PROFILE_IMAGE";
  mkdir -p "$PUBLIC_ROOT_DIR/$IMAGES_UPLOAD_DIR";
  mkdir -p "$PUBLIC_ROOT_DIR/$FILES_UPLOAD_DIR";
  mkdir -p "$PUBLIC_ROOT_DIR/$VIDEOS_UPLOAD_DIR";
  mkdir -p "$PUBLIC_ROOT_DIR/$MINIATURES_UPLOAD_DIR";
  mkdir -p "$PUBLIC_ROOT_DIR/${SCIENTIFIC_PAPERS_UPLOAD_DIR:-upload/scientific_papers}";

  chown www-data:www-data "$PUBLIC_ROOT_DIR" -R;
}

function generateEncryptionKey() {
  RESULT=$($BIN_CONSOLE_PATH --env=prod encrypt:genkey 2>/dev/null | grep -i "Ok" | sed s/\\[OK]//);
  echo $(trim "${RESULT}");
}

function getEncryptionKey() {
  KEY=$(cat "${ENCRYPTION_CONF_FILE_PATH}" | grep -Po '(?<=encrypt_key:)[ ]?.*');
  echo $(trim "${KEY}");
}

function isEncryptionKeySet() {
  CURR_KEY=$(getEncryptionKey)
  if [ -z "${CURR_KEY}" ]; then
    echo 0;
  else
    echo 1;
  fi;
}

function setEncryptionKey() {
  if [ "$(isEncryptionKeySet)" -eq 1 ]; then
    logDebug "Encryption key is already set - skipping"
    return 0;
  fi;

  logDebug "Setting encryption key"
  KEY=$(generateEncryptionKey)
  sed -i "s${SED_SEPARATOR_CHARACTER}encrypt_key:${SED_SEPARATOR_CHARACTER}encrypt_key: '${KEY}'${SED_SEPARATOR_CHARACTER}" "${ENCRYPTION_CONF_FILE_PATH}"
}

function generateJwtKeyPair() {
  # On Railway the filesystem is ephemeral: keys are lost on every redeploy.
  # Supply JWT_PRIVATE_KEY_B64 and JWT_PUBLIC_KEY_B64 as base64-encoded env vars
  # to persist the key pair across deploys (avoids invalidating all user sessions).
  # To generate: base64 -w 0 config/jwt/prod/private.pem
  JWT_DIR="${ROOT_DIR_PATH}config/jwt/${APP_ENV:-prod}"
  mkdir -p "$JWT_DIR"

  if [ -n "${JWT_PRIVATE_KEY_B64:-}" ] && [ -n "${JWT_PUBLIC_KEY_B64:-}" ]; then
    logDebug "Loading JWT keys from environment variables"
    echo "$JWT_PRIVATE_KEY_B64" | base64 -d > "$JWT_DIR/private.pem"
    echo "$JWT_PUBLIC_KEY_B64"  | base64 -d > "$JWT_DIR/public.pem"
    chmod 644 "$JWT_DIR/private.pem" "$JWT_DIR/public.pem"
  else
    logDebug "Generating JWT key pair - only if none exists yet (WARNING: keys are ephemeral without JWT_PRIVATE_KEY_B64/JWT_PUBLIC_KEY_B64 env vars)"
    $BIN_CONSOLE_PATH lexik:jwt:generate-keypair --skip-if-exists;
  fi
}

# ---- Wait for database ----
logDebug "Waiting for database..."
DB_HOST=$(echo "$DATABASE_URL" | grep -oP '(?<=@)[^:/]+')
DB_PORT=$(echo "$DATABASE_URL" | grep -oP '(?<=:)\d+(?=/[^/])')
DB_PORT=${DB_PORT:-3306}

until nc -z "$DB_HOST" "$DB_PORT" 2>/dev/null; do
  echo "  Waiting for database at $DB_HOST:$DB_PORT..."
  sleep 2
done
logDebug "Database is ready."

# ---- Install dependencies ----
logDebug "Installing composer packages"
composer install --no-dev --optimize-autoloader --ignore-platform-reqs --no-interaction

logDebug "Doing: composer dump-autoload"
composer dump-autoload --optimize --ignore-platform-reqs

logDebug "Setting up vendor dir rights"
chown www-data:www-data vendor/* -R

# ---- Clear cache ----
logDebug "Clearing cache"
$BIN_CONSOLE_PATH cache:clear --env=prod --no-debug
$BIN_CONSOLE_PATH cache:warmup --env=prod --no-debug

# ---- Database ----
logDebug "Create database if not exists"
$BIN_CONSOLE_PATH doctrine:database:create --if-not-exists --no-interaction

logDebug "Execute migrations"
$BIN_CONSOLE_PATH doctrine:migrations:migrate --no-interaction --allow-no-migration

# ---- Permissions ----
logDebug "Set up var dir rights"
chown www-data:www-data var/* -R

# ---- Upload dirs ----
createDirs

# ---- Encryption & JWT ----
setEncryptionKey
generateJwtKeyPair

logDebug "Set up JWT key permissions"
chown www-data:www-data config/jwt/ -R
chmod 644 config/jwt/private.pem config/jwt/public.pem 2>/dev/null || true

# ---- Configure Nginx port ----
PORT=${PORT:-8080}
logDebug "Configuring Nginx on port $PORT"
sed -i "s/listen PORT/listen $PORT/" /etc/nginx/sites-available/default

# ---- Start PHP-FPM in background ----
logDebug "Starting PHP-FPM..."
php-fpm --nodaemonize &
PHP_FPM_PID=$!

# Give PHP-FPM a moment to bind to port 9000
sleep 1

# ---- Start Nginx in foreground ----
logDebug "Starting Nginx..."
trap "kill $PHP_FPM_PID" EXIT
nginx -g "daemon off;"
