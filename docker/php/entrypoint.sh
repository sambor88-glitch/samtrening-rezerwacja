#!/bin/sh
set -e

echo "🚀 SAMtrening Laravel starting..."

# Generate app key if missing
if [ -z "$APP_KEY" ]; then
  php artisan key:generate --no-interaction
fi

# Run migrations
echo "📦 Running migrations..."
php artisan migrate --no-interaction --force

# Seed if first run (no trainers exist)
TRAINER_COUNT=$(php artisan tinker --execute="echo \App\Models\Trainer::count();" 2>/dev/null | tail -1 || echo "0")
if [ "$TRAINER_COUNT" = "0" ]; then
  echo "🌱 Seeding initial data..."
  php artisan db:seed --no-interaction --force
fi

# Cache config in production
if [ "$APP_ENV" = "production" ]; then
  php artisan config:cache
  php artisan route:cache
fi

echo "✅ Ready!"
exec "$@"
