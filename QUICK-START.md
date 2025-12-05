# Quick Start Guide

This guide helps you quickly get the project running when cloning to a new machine or switching between your Windows 11 PC and Linux Mint laptop.

## Prerequisites

- **Docker Desktop** installed and running
- **Git** installed
- **WSL2** (Windows only - required for Docker Desktop)

## First Time Setup on a New Machine

### 1. Clone the Repository

```bash
git clone <your-repo-url>
cd example-api
```

### 2. Copy Environment File

```bash
cp .env.example .env
```

**Important:** Make sure `.env` has these Docker settings:

```env
DB_HOST=mysql
DB_USERNAME=sail
DB_PASSWORD=password
REDIS_HOST=redis
```

### 3. Fix Permissions (Linux/Mac only)

```bash
sudo chown -R $USER:$USER .
chmod -R 775 storage bootstrap/cache
```

### 4. Set Up Sail Alias

**Linux/Mac:**
```bash
echo "alias sail='sh \$([ -f sail ] && echo sail || echo vendor/bin/sail)'" >> ~/.bashrc
source ~/.bashrc
```

**Windows (PowerShell - run as Admin):**

Add to your PowerShell profile:
```powershell
notepad $PROFILE
```

Add this line:
```powershell
function sail { bash vendor/bin/sail @args }
```

Save and restart PowerShell.

### 5. Start Docker Containers

```bash
sail up -d
```

**First time only** - this will build the Docker image (takes 5-10 minutes).

### 6. Install Dependencies

```bash
sail composer install
sail npm install
```

### 7. Generate App Key & Run Migrations

```bash
sail artisan key:generate
sail artisan migrate
```

### 8. Start Development Server

```bash
sail npm run dev
```

Visit **http://localhost** in your browser!

---

## Daily Workflow (Returning to Project)

### Starting Your Work Session

```bash
# 1. Pull latest changes from GitHub
git pull origin main

# 2. Start Docker containers
sail up -d

# 3. Update dependencies (if needed)
sail composer install
sail npm install

# 4. Run new migrations (if any)
sail artisan migrate

# 5. Start dev server with hot reload
sail npm run dev
```

Your app is now running at **http://localhost**

### Ending Your Work Session

```bash
# 1. Stop dev server (Ctrl+C if running in foreground)

# 2. Commit and push your changes
git add .
git commit -m "Your commit message"
git push origin main

# 3. Stop containers (optional - they can stay running)
sail stop

# Or completely remove containers (will need to rebuild)
sail down
```

---

## Switching Between Machines

### Leaving Current Machine

```bash
# Save your work
git add .
git commit -m "Work in progress: [describe changes]"
git push origin main

# Stop containers
sail stop
```

### On New Machine

```bash
# Get latest changes
git pull origin main

# Start containers
sail up -d

# Update dependencies (if package files changed)
sail composer install
sail npm install

# Run any new migrations
sail artisan migrate

# Start dev server
sail npm run dev
```

**That's it!** Your environment is identical on both machines thanks to Docker.

---

## Common Commands Reference

| Task | Command |
|------|---------|
| Start containers | `sail up -d` |
| Stop containers | `sail stop` |
| View logs | `sail logs -f` |
| Run migrations | `sail artisan migrate` |
| Access database CLI | `sail mysql` |
| Run tests | `sail artisan test` |
| Shell into container | `sail shell` |
| Update composer deps | `sail composer install` |
| Update npm deps | `sail npm install` |
| Build for production | `sail npm run build` |
| Dev with hot reload | `sail npm run dev` |

---

## Troubleshooting

### Containers won't start
```bash
# Check if Docker is running
docker ps

# Rebuild containers
sail down -v
sail build --no-cache
sail up -d
```

### Permission errors
```bash
sudo chown -R $USER:$USER storage bootstrap/cache
sail artisan cache:clear
```

### Port 80 already in use
Edit `.env`:
```env
APP_PORT=8000
```
Then restart: `sail down && sail up -d`

### "sail: command not found"
Use the full path: `./vendor/bin/sail` or set up the alias (see step 4 above)

### Database connection refused
Make sure `.env` has:
```env
DB_HOST=mysql
DB_USERNAME=sail
DB_PASSWORD=password
```

---

## Windows-Specific Notes

### WSL2 Required
Docker Desktop on Windows requires WSL2. Make sure it's enabled.

### File Performance
For best performance on Windows, clone the repo inside WSL2:
```bash
# Inside WSL2 terminal
cd ~
mkdir projects
cd projects
git clone <your-repo-url>
```

Then access it from Windows at: `\\wsl$\Ubuntu\home\<username>\projects\example-api`

### PowerShell vs WSL
You can run `sail` commands from:
- **PowerShell** (requires the function alias)
- **WSL2 Bash** (use the regular bash alias)
- **Git Bash** (use the regular bash alias)

---

## Platform-Specific Alias Setup

### Linux (Ubuntu/Mint/Debian)
```bash
echo "alias sail='sh \$([ -f sail ] && echo sail || echo vendor/bin/sail)'" >> ~/.bashrc
source ~/.bashrc
```

### macOS
```bash
echo "alias sail='sh \$([ -f sail ] && echo sail || echo vendor/bin/sail)'" >> ~/.zshrc
source ~/.zshrc
```

### Windows PowerShell
```powershell
# Create profile if doesn't exist
if (!(Test-Path -Path $PROFILE)) {
    New-Item -ItemType File -Path $PROFILE -Force
}

# Add function
Add-Content -Path $PROFILE -Value "function sail { bash vendor/bin/sail @`args }"

# Reload
. $PROFILE
```

---

## What Gets Synced via Git

✅ **Included in Git:**
- Application code (PHP, React)
- Configuration files (`.env.example`, `compose.yaml`)
- Database migrations
- Package definitions (`composer.json`, `package.json`)

❌ **Not in Git (Docker handles these):**
- `vendor/` - PHP dependencies
- `node_modules/` - Node dependencies
- `.env` - Your local environment config
- `storage/` - Logs, cache, uploads
- Database data (stored in Docker volumes)

This is why you run `sail composer install` and `sail npm install` on each machine!

---

## Need More Help?

- See [DOCKER-SETUP.md](DOCKER-SETUP.md) for detailed Docker/Sail documentation
- See [CLAUDE.md](CLAUDE.md) for Laravel-specific development guidelines
- Laravel Sail Docs: https://laravel.com/docs/12.x/sail
