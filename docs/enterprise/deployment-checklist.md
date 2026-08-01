# Deployment Checklist (Local, Staging, Production)

## Local
1. composer install
2. copy .env.example .env
3. php artisan key:generate
4. php artisan migrate
5. npm install && npm run build
6. php artisan test

## Staging
1. Sinkron branch release
2. Set APP_ENV=staging, APP_DEBUG=false
3. Jalankan migrate --force
4. Build assets production
5. Warm cache config, route, view
6. Jalankan smoke test modul inti

## Production
1. Aktifkan maintenance mode
2. Pull/tag release
3. composer install --no-dev --optimize-autoloader
4. php artisan migrate --force
5. php artisan optimize
6. Restart queue workers
7. Nonaktifkan maintenance mode
8. Verifikasi health endpoint /up
9. Pantau log 30 menit

## Rollback
1. Aktifkan maintenance mode
2. Checkout tag release sebelumnya
3. composer install --no-dev
4. php artisan migrate:rollback --step=1 (jika perlu)
5. Restore backup jika mismatch data
6. php artisan optimize
7. Nonaktifkan maintenance mode
