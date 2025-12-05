# Docker Setup Guide - Laravel React Starter Kit

This guide will help you run this Laravel + React application using Docker via Laravel Sail, eliminating the need for global PHP, Node, or other dependencies.

## Prerequisites

- Docker Desktop installed and running
- Docker Compose installed (comes with Docker Desktop)

Verify installation:
```bash
docker --version
docker compose version
```

## Initial Setup

### 1. Install Laravel Sail

Generate the Docker configuration files:

```bash
php artisan sail:install --with=mysql,redis
```

If you don't have PHP installed locally, use Docker directly:

```bash
docker run --rm \
    -u "$(id -u):$(id -g)" \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php84-composer:latest \
    php artisan sail:install --with=mysql,redis
```

### 2. Create Shell Alias (Highly Recommended)

**IMPORTANT:** To avoid typing `./vendor/bin/sail` every time, create a shell alias. This is the official recommended approach from Laravel.

Add this to your `~/.bashrc` (Linux) or `~/.zshrc` (Mac):

```bash
alias sail='sh $([ -f sail ] && echo sail || echo vendor/bin/sail)'
```

Make it permanent:
```bash
echo "alias sail='sh \$([ -f sail ] && echo sail || echo vendor/bin/sail)'" >> ~/.bashrc
source ~/.bashrc
```

On Windows (PowerShell):
```powershell
function sail { bash vendor/bin/sail @args }
```

**After setting up the alias**, you can use `sail` instead of `./vendor/bin/sail`:
```bash
sail up -d
sail artisan migrate
sail npm run dev
```

**Note:** All examples below use `sail` assuming you've set up the alias. If you haven't, replace `sail` with `./vendor/bin/sail`.

## Starting the Application

### First Time Setup

```bash
# Start Docker containers
sail up -d

# Install PHP dependencies
sail composer install

# Install Node dependencies
sail npm install

# Generate application key (if not done)
sail artisan key:generate

# Run database migrations
sail artisan migrate

# Build frontend assets (or use 'sail npm run dev' for development)
sail npm run build
```

### Development Mode

Start all services in development mode:

```bash
sail up
```

Or run in background:
```bash
sail up -d
```

For frontend development with hot reload:
```bash
sail npm run dev
```

This starts Vite with hot module replacement. Your app will be at **http://localhost**

## Accessing the Application

- **Application**: http://localhost
- **MySQL**: localhost:3306
  - Username: `sail`
  - Password: `password`
  - Database: `example_api`
- **Redis**: localhost:6379
- **Mailpit** (email testing): http://localhost:8025

## Common Commands

### Artisan Commands
```bash
sail artisan migrate
sail artisan tinker
sail artisan make:model Post
```

### Composer
```bash
sail composer install
sail composer require package/name
```

### NPM
```bash
sail npm install
sail npm run dev
sail npm run build
```

### Running Tests
```bash
sail artisan test
sail artisan test --filter=testName
```

### Database
```bash
# Access MySQL CLI
sail mysql

# Run migrations
sail artisan migrate

# Fresh migrations with seeding
sail artisan migrate:fresh --seed
```

### Shell Access
```bash
# Access application container shell
sail shell

# Access root shell
sail root-shell
```

## Stopping the Application

```bash
# Stop containers (keeps them for restart)
sail stop

# Stop and remove containers
sail down

# Stop and remove containers + volumes (destroys database)
sail down -v
```

## Troubleshooting

### Port Already in Use

If port 80 is already taken, you can change it in `.env`:

```env
APP_PORT=8000
```

Then restart:
```bash
sail down
sail up -d
```

### Permission Issues

If you encounter permission errors:

```bash
sudo chown -R $USER:$USER storage bootstrap/cache
sail artisan cache:clear
```

### Rebuild Containers

If something is broken, rebuild from scratch:

```bash
sail down -v
sail build --no-cache
sail up -d
sail composer install
sail npm install
sail artisan migrate
```

### View Logs

```bash
# All containers
sail logs

# Specific container
sail logs laravel.test

# Follow logs
sail logs -f
```

## Environment Configuration

Make sure your `.env` file has these Docker-specific settings:

```env
APP_URL=http://localhost
APP_SERVICE=laravel.test

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=example_api
DB_USERNAME=sail
DB_PASSWORD=password

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
```

## Quick Reference

| Task | Command |
|------|---------|
| Start containers | `sail up -d` |
| Stop containers | `sail stop` |
| View logs | `sail logs -f` |
| Run artisan | `sail artisan {command}` |
| Run composer | `sail composer {command}` |
| Run npm | `sail npm {command}` |
| Access shell | `sail shell` |
| Run tests | `sail artisan test` |
| Access MySQL | `sail mysql` |

## Benefits of Using Docker

- No need to install PHP, MySQL, Redis, or Node globally
- Consistent environment across all developers
- Easy to reset/rebuild if something breaks
- Isolated from your system
- Same versions as production (if configured correctly)

## Next Steps

1. Run the first-time setup commands above
2. Visit http://localhost in your browser
3. Start developing with `sail npm run dev` for hot reload

For more information, see the [Laravel Sail documentation](https://laravel.com/docs/12.x/sail).
