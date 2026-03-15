# AGENTS.md

## Cursor Cloud specific instructions

### Architecture

This is a Personal Management System (PMS) monorepo with:
- **Backend** (`backend/`): PHP 8.3 / Symfony 5.4, runs in Docker (MariaDB + Nginx + PHP-FPM)
- **Frontend** (`frontend/`): Vue 3 / TypeScript / Vite, runs on Node 18

### Starting Services

**Backend** (Docker required):
```sh
cd backend && docker compose -f docker-compose.yml up -d
```
Wait ~2 min for the PHP-FPM entrypoint to finish (composer install, migrations, JWT key generation, etc.). Monitor with `docker logs -f pms-php-fpm`.

**Frontend** (dev mode):
```sh
source /home/ubuntu/.nvm/nvm.sh && nvm use 18.19.1
cd frontend && npx vite --port 4001 --host
```

### Ports

| Service | URL |
|---------|-----|
| Backend API (Nginx) | http://localhost:8002 |
| Frontend (Vite dev) | http://localhost:4001 |
| Adminer (DB admin) | http://localhost:8081 |
| Mailpit (email) | http://localhost:8082 |

### Key Gotchas

1. **Composer lock file sync**: The `composer.json` added `aws/aws-sdk-php` but the original lock file may be out of sync. Newer Composer (2.7+) blocks packages with security advisories. The `composer.json` has `"audit": {"block-insecure": false}` to work around this. If the PHP-FPM container is in a restart loop, check `docker logs pms-php-fpm` for Composer errors.

2. **Backend config files are gitignored**: `backend/.env` and `backend/config/services.yaml` must exist for the app to work. These are not committed to the repo. The Docker entrypoint expects them to be present. See the backend `.env` section below for required values.

   **backend/.env** (minimum required):
   ```
   APP_ENV=dev
   APP_DEBUG=true
   APP_SECRET=s3cr3t_k3y_f0r_d3v3l0pm3nt
   DATABASE_URL=mysql://root:password@pms-database-mariadb:3306/pms
   MAILER_DSN=smtp://pms-mail:1025
   UPLOAD_DIR=upload
   IMAGES_UPLOAD_DIR=upload/images
   FILES_UPLOAD_DIR=upload/files
   VIDEOS_UPLOAD_DIR=upload/videos
   MINIATURES_UPLOAD_DIR=upload/miniatures
   PUBLIC_ROOT_DIR=public
   APP_DEMO=false
   APP_DEFAULT_NPL_RECEIVER_EMAILS="['admin@admin.admin']"
   ```

   **backend/config/services.yaml** (minimum required):
   ```yaml
   parameters:
       jwt_token_lifetime: 86400
       project.name: 'PMS'
   services:
       _defaults:
           autowire: true
           autoconfigure: true
           public: false
       App\:
           resource: '../src/*'
           exclude: '../src/{DependencyInjection,Entity,Migrations,Tests,Kernel.php}'
       App\Action\:
           resource: '../src/Action'
           tags: ['controller.service_arguments']
       App\Services\ConfigLoaders\ConfigLoaderSecurity:
           calls:
               - setRestrictedIps: ['[]']
       App\Services\ConfigLoaders\ConfigLoaderSystem:
           calls:
               - setSystemFromEmail: ['noreply@pms.local']
       App\NotifierProxyLoggerBridge:
           class: App\NotifierProxyLoggerBridge
           arguments:
               $logFilePath: '%kernel.logs_dir%/npl_bridge.log'
               $loggerName: 'npl_bridge'
               $baseUrl: 'http://localhost'
       App\PmsIo\PmsIoBridge:
           class: App\PmsIo\PmsIoBridge
           arguments:
               $logFilePath: '%kernel.logs_dir%/pms_io_bridge.log'
               $loggerName: 'pms_io_bridge'
               $baseUrl: 'http://localhost'
               $login: 'admin'
               $password: 'admin'
               $secret: 'dev_secret'
   ```

3. **node-sass build failures**: The frontend uses legacy `node-sass` (devDependency) which requires native compilation. Use `npm install --force --ignore-scripts` to skip this; the modern `sass` package handles SCSS compilation.

4. **Frontend Vite HMR port**: The vite.config.js sets `hmr.clientPort: 4001`. When running outside Docker, start Vite with `--port 4001`.

5. **Login credentials format**: The `/login` endpoint expects `{"username": "email@example.com", "password": "..."}` (the field is called `username` but accepts the email address). Default demo credentials: `admin@admin.admin` / `admin`.

6. **Docker in nested containers**: This VM environment requires `fuse-overlayfs` storage driver and `iptables-legacy` for Docker to work. Docker daemon config is at `/etc/docker/daemon.json`.

7. **JWT public key sync**: After the backend generates JWT keys (on first startup), the frontend config at `frontend/src/config/dev/jwt.json` must have the matching public key from `backend/config/jwt/dev/public.pem`. If there's a JWT signature verification error in the browser console, update this file.

8. **User registration**: On fresh database, register a user via: `curl -X POST http://127.0.0.1:8002/register-user -H "Content-Type: application/json" -d '{"email":"admin@admin.admin","username":"admin","password":"admin","passwordConfirmed":"admin","lockPassword":"admin","lockPasswordConfirmed":"admin"}'`. Only one active user is allowed.

### Lint

- **Frontend**: `cd frontend && npx eslint --ext .js,.vue --ignore-path .gitignore src` (pre-existing warnings/errors exist)
- **Backend PHP syntax**: `docker exec pms-php-fpm sh -c 'find src -name "*.php" -exec php -l {} \;'`

### Tests

- **Backend PHPUnit**: `docker exec pms-php-fpm vendor/bin/phpunit` (if configured)
- **Frontend**: No automated test runner configured in package.json
