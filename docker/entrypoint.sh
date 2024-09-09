#!/bin/bash
set -x

# Check if .env file exists, if not copy from example
if [ ! -f .env ]; then
  cp .env.example .env
fi

# Update variables in the .env file
update_env() {
  local key=$1
  local value=$2

  # Check if the value is empty
  if [ -z "$value" ]; then
    echo "Skipping ${key} as the value is empty."
    return
  fi
  # Check if the key exists in the .env file
  if grep -q "^${key}=" .env; then
    echo "Updating ${key} in .env"
    # If the key exists, update the value
    sed -i "s/^${key}=.*/${key}=${value}/" .env
  else
    echo "Adding ${key} to .env"
    # If the key doesn't exist, append it
    echo "${key}=${value}" >> .env
  fi
}
echo "$APP_ENV"
echo "$APP_DEBUG"
echo "$DB_HOST"
# Update environment variables in the .env file
update_env "APP_ENV" "$APP_ENV"
update_env "APP_DEBUG" "$APP_DEBUG"
update_env "DB_HOST" "$DB_HOST"
update_env "DB_PORT" "$DB_PORT"
update_env "DB_DATABASE" "$DB_DATABASE"
update_env "DB_USERNAME" "$DB_USERNAME"
update_env "DB_PASSWORD" "$DB_PASSWORD"
update_env "QUEUE_CONNECTION" "$QUEUE_CONNECTION"
update_env "REDIS_HOST" "$REDIS_HOST"
update_env "DB_CONNECTION" "$DB_CONNECTION"

if [ "$DB_CONNECTION" = "sqlite" ]; then
  echo "Setting up SQLite database..."
  mkdir -p /var/www/html/database
  touch /var/www/html/database/database.sqlite
fi

# Testing if it will solve cache permission error
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Cache configurations and optimize the application
php artisan config:cache
php artisan optimize:clear
php artisan storage:link || echo "Storage link already exists"


# Run supervisor to manage the Laravel Queue
supervisord -c -n /etc/supervisor/supervisord.conf

# Wait for db to run
# Read DB_HOST AND PORT FROM .env file
DB_HOST=$(grep DB_HOST .env | cut -d '=' -f2)
DB_PORT=$(grep DB_PORT .env | cut -d '=' -f2)
while ! nc -z "$DB_HOST" "$DB_PORT"; do
  echo "Waiting for the database to be available..."
  sleep 1
done
echo "Database is up and running!"
# If already installed, skip the installation
php artisan queue:table || echo "Queue table already exists"
php artisan migrate || echo "Migration failed"


# Execute the original CMD
exec "$@"
