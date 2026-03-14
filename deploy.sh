#!/bin/bash

# Personal Management System - Deployment Script
# Usage: ./deploy.sh [--docker|--manual] [--run] [--help]
#   --docker  : Deploy using Docker (backend/docker-compose.yml)
#   --manual  : Deploy manually (composer + npm, requires PHP & Node locally)
#   --run     : Start services after deployment (Docker: compose up; Manual: prints instructions)
#   --help    : Show this help

set -e

show_help() {
  echo "PMS Deployment Script"
  echo ""
  echo "Usage: ./deploy.sh [OPTIONS]"
  echo ""
  echo "Options:"
  echo "  --docker   Deploy using Docker (recommended if Docker is installed)"
  echo "  --manual   Deploy manually with local PHP and Node.js"
  echo "  --run      Start services after deployment"
  echo "  --help     Show this help"
  echo ""
  echo "Examples:"
  echo "  ./deploy.sh --docker --run    # Full Docker deployment and start"
  echo "  ./deploy.sh --manual          # Manual deploy (backend + frontend build)"
  echo "  ./deploy.sh --manual --run    # Manual deploy + run instructions"
  exit 0
}

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKEND_DIR="${SCRIPT_DIR}/backend"
FRONTEND_DIR="${SCRIPT_DIR}/frontend"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

print_header() {
  echo -e "\n${GREEN}========================================${NC}"
  echo -e "${GREEN}$1${NC}"
  echo -e "${GREEN}========================================${NC}\n"
}

print_warn() {
  echo -e "${YELLOW}[WARN] $1${NC}"
}

print_error() {
  echo -e "${RED}[ERROR] $1${NC}"
}

check_php() {
  if command -v php &>/dev/null; then
    php -v
    return 0
  fi
  return 1
}

check_composer() {
  if command -v composer &>/dev/null; then
    composer --version
    return 0
  fi
  if [ -f "${BACKEND_DIR}/composer.phar" ]; then
    php "${BACKEND_DIR}/composer.phar" --version
    return 0
  fi
  return 1
}

check_node() {
  if command -v node &>/dev/null; then
    node -v
    return 0
  fi
  return 1
}

check_npm() {
  if command -v npm &>/dev/null; then
    npm -v
    return 0
  fi
  return 1
}

check_docker() {
  if command -v docker &>/dev/null && docker info &>/dev/null; then
    docker --version
    return 0
  fi
  return 1
}

deploy_backend_manual() {
  print_header "Deploying Backend (manual)"
  cd "${BACKEND_DIR}"

  if ! check_php; then
    print_error "PHP is required. Install PHP 8.3+ or use --docker"
    exit 1
  fi

  COMPOSER_CMD="composer"
  if [ -f "${BACKEND_DIR}/composer.phar" ]; then
    COMPOSER_CMD="php composer.phar"
  elif command -v composer &>/dev/null; then
    COMPOSER_CMD="composer"
  else
    print_error "Composer is required. Install: https://getcomposer.org/download/ or add composer.phar to backend/"
    exit 1
  fi

  print_header "Installing PHP dependencies"
  $COMPOSER_CMD install --no-interaction --optimize-autoloader

  print_header "Running database migrations"
  php bin/console doctrine:database:create --if-not-exists 2>/dev/null || true
  php bin/console doctrine:migrations:migrate --no-interaction

  print_header "Clearing cache"
  php bin/console cache:clear --env=prod
  php bin/console cache:warmup --env=prod

  print_header "Backend deployment complete"
}

deploy_frontend_manual() {
  print_header "Deploying Frontend (manual)"
  cd "${FRONTEND_DIR}"

  if ! check_node; then
    print_error "Node.js is required for frontend build. Install Node.js or use --docker"
    exit 1
  fi

  if ! check_npm; then
    print_error "npm is required for frontend build"
    exit 1
  fi

  print_header "Installing Node dependencies"
  npm install

  print_header "Building frontend assets"
  if [ -f "build-assets.sh" ]; then
    chmod +x build-assets.sh
    ./build-assets.sh production
  else
    npm run build
  fi

  print_header "Frontend deployment complete"
}

deploy_docker() {
  print_header "Deploying with Docker"
  cd "${BACKEND_DIR}"

  if ! check_docker; then
    print_error "Docker is required for --docker mode"
    exit 1
  fi

  print_header "Building and starting containers"
  docker compose -f docker-compose.yml up -d --build

  print_header "Waiting for services to be ready..."
  sleep 10

  print_header "Running database migrations"
  docker compose -f docker-compose.yml exec -T pms-php-fpm php bin/console doctrine:database:create --if-not-exists 2>/dev/null || true
  docker compose -f docker-compose.yml exec -T pms-php-fpm php bin/console doctrine:migrations:migrate --no-interaction

  print_header "Clearing cache"
  docker compose -f docker-compose.yml exec -T pms-php-fpm php bin/console cache:clear --env=prod

  print_header "Building frontend (inside container or locally)"
  if check_node; then
    cd "${FRONTEND_DIR}"
    npm install 2>/dev/null || true
    [ -f "build-assets.sh" ] && ./build-assets.sh production || npm run build
  else
    print_warn "Node not found. Build frontend manually: cd frontend && npm install && npm run build"
  fi

  print_header "Docker deployment complete"
  echo -e "Backend:  http://localhost:8002"
  echo -e "Adminer:  http://localhost:8081"
  echo -e "Mailpit:  http://localhost:8082"
}

run_manual() {
  print_header "Starting services (manual)"
  echo -e "To run the application:"
  echo -e "  1. Backend:  cd backend && php -S localhost:8000 -t public"
  echo -e "  2. Frontend: cd frontend && npm run serve  (or point nginx/apache to frontend/dist)"
  echo -e "  3. Database: Ensure MySQL/MariaDB is running and DATABASE_URL in backend/.env is correct"
  echo -e ""
  echo -e "Or use Docker: ./deploy.sh --docker --run"
}

run_docker() {
  if ! docker compose -f "${BACKEND_DIR}/docker-compose.yml" ps | grep -q "Up"; then
    cd "${BACKEND_DIR}"
    docker compose -f docker-compose.yml up -d
  fi
  echo -e "\n${GREEN}Services running.${NC}"
  echo -e "Backend:  http://localhost:8002"
  echo -e "Adminer:  http://localhost:8081"
  echo -e "Mailpit:  http://localhost:8082"
}

# Parse arguments
MODE=""
DO_RUN=false

for arg in "$@"; do
  case $arg in
    --docker)
      MODE="docker"
      ;;
    --manual)
      MODE="manual"
      ;;
    --run)
      DO_RUN=true
      ;;
    --help|-h)
      show_help
      ;;
  esac
done

# Default to manual if no mode specified
if [ -z "$MODE" ]; then
  if check_docker; then
    MODE="docker"
    print_warn "No mode specified. Using --docker (Docker detected)"
  else
    MODE="manual"
    print_warn "No mode specified. Using --manual (Docker not detected)"
  fi
fi

print_header "PMS Deployment - Mode: ${MODE}"

if [ "$MODE" = "docker" ]; then
  deploy_docker
  $DO_RUN && run_docker
else
  deploy_backend_manual
  deploy_frontend_manual
  $DO_RUN && run_manual
fi

print_header "Deployment finished successfully"
