#!/usr/bin/env bash
set -euo pipefail

# ─── Usage ───────────────────────────────────────────────────────────────────
# Option 1 – Run directly via curl (private repo – requires a GitHub PAT):
#   export GITHUB_TOKEN="ghp_your_token_here"
#   curl -fsSL -H "Authorization: token $GITHUB_TOKEN" \
#     https://raw.githubusercontent.com/ptmuldoon/Laravel-Golf/main/install.sh \
#     | sudo GITHUB_TOKEN="$GITHUB_TOKEN" bash
#
# Option 2 – Clone first, then run:
#   git clone https://github.com/ptmuldoon/Laravel-Golf.git
#   cd Laravel-Golf && sudo bash install.sh
# ─────────────────────────────────────────────────────────────────────────────

REPO_URL="https://github.com/ptmuldoon/Laravel-Golf.git"
GITHUB_TOKEN="${GITHUB_TOKEN:-}"

# ─── Colours ──────────────────────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; RESET='\033[0m'

info()    { echo -e "${CYAN}[INFO]${RESET}  $*"; }
success() { echo -e "${GREEN}[OK]${RESET}    $*"; }
warn()    { echo -e "${YELLOW}[WARN]${RESET}  $*"; }
error()   { echo -e "${RED}[ERROR]${RESET} $*" >&2; exit 1; }
step()    { echo -e "\n${BOLD}── $* ──${RESET}"; }

# read wrapper that always reads from the terminal (works with curl | bash)
prompt() { read "$@" < /dev/tty; }

# ─── Must run as root ─────────────────────────────────────────────────────────
[[ $EUID -ne 0 ]] && error "Please run as root:  curl -fsSL <url> | sudo bash"

# ─── Detect distro ────────────────────────────────────────────────────────────
if ! command -v apt-get &>/dev/null; then
    error "This script requires a Debian/Ubuntu-based system (apt-get not found)."
fi

WEB_USER="www-data"

# ─── Determine install directory ─────────────────────────────────────────────
# If we're already inside a clone of the repo, use this directory.
# Otherwise we'll clone the repo into the chosen directory.
NEED_CLONE=true
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]:-/dev/null}")" 2>/dev/null && pwd || echo "")"

if [[ -n "$SCRIPT_DIR" && -f "${SCRIPT_DIR}/artisan" && -f "${SCRIPT_DIR}/.env.example" ]]; then
    # Running from inside an existing clone
    INSTALL_DIR="$SCRIPT_DIR"
    NEED_CLONE=false
    info "Detected existing project in ${INSTALL_DIR}"
fi

if $NEED_CLONE; then
    step "Install location"
    prompt -rp "Install directory [/var/www/html/golf]: " INSTALL_DIR
    INSTALL_DIR="${INSTALL_DIR:-/var/www/html/golf}"

    # Prompt for GitHub token if not set (needed for private repo)
    if [[ -z "$GITHUB_TOKEN" ]]; then
        warn "Repository is private. A GitHub Personal Access Token (PAT) is required."
        prompt -rsp "GitHub token: " GITHUB_TOKEN
        echo
        [[ -z "$GITHUB_TOKEN" ]] && error "GitHub token cannot be empty for private repo access."
    fi
fi

# ─── Gather configuration ─────────────────────────────────────────────────────
step "Configuration"

prompt -rp "App name            [Tuesday Golf League]: " APP_NAME
APP_NAME="${APP_NAME:-Tuesday Golf League}"

prompt -rp "App URL             [http://localhost]: " APP_URL
APP_URL="${APP_URL:-http://localhost}"

prompt -rp "Nginx server_name   [_]: " SERVER_NAME
SERVER_NAME="${SERVER_NAME:-_}"

prompt -rp "MySQL root password : " MYSQL_ROOT_PASS
[[ -z "$MYSQL_ROOT_PASS" ]] && error "MySQL root password cannot be empty."

prompt -rp "DB name             [golf]: " DB_DATABASE
DB_DATABASE="${DB_DATABASE:-golf}"

prompt -rp "DB user             [golf_user]: " DB_USERNAME
DB_USERNAME="${DB_USERNAME:-golf_user}"

prompt -rsp "DB password         : " DB_PASSWORD
echo
[[ -z "$DB_PASSWORD" ]] && error "DB password cannot be empty."

prompt -rp "Admin name          [Admin]: " ADMIN_NAME
ADMIN_NAME="${ADMIN_NAME:-Admin}"

prompt -rp "Admin email         [admin@golf.com]: " ADMIN_EMAIL
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@golf.com}"

prompt -rsp "Admin password      : " ADMIN_PASSWORD
echo
[[ -z "$ADMIN_PASSWORD" ]] && error "Admin password cannot be empty."

echo
info "Install directory : $INSTALL_DIR"
info "App URL           : $APP_URL"
info "Nginx server_name : $SERVER_NAME"
info "Database          : $DB_DATABASE (user: $DB_USERNAME)"
info "Admin email       : $ADMIN_EMAIL"
echo
prompt -rp "Proceed? [y/N]: " CONFIRM
[[ "${CONFIRM,,}" != "y" ]] && echo "Aborted." && exit 0

# ─── System packages ──────────────────────────────────────────────────────────
step "Installing system packages"

apt-get update -qq
apt-get install -y -qq software-properties-common

info "Adding ondrej/php PPA for PHP 8.4..."
add-apt-repository -y ppa:ondrej/php
apt-get update -qq

PHP_VER="8.4"

info "Installing PHP ${PHP_VER} and extensions..."
apt-get install -y -qq \
    "php${PHP_VER}" \
    "php${PHP_VER}-fpm" \
    "php${PHP_VER}-cli" \
    "php${PHP_VER}-mysql" \
    "php${PHP_VER}-mbstring" \
    "php${PHP_VER}-xml" \
    "php${PHP_VER}-curl" \
    "php${PHP_VER}-zip" \
    "php${PHP_VER}-bcmath" \
    "php${PHP_VER}-tokenizer" \
    "php${PHP_VER}-intl"

info "Installing nginx, MySQL, and utilities..."
apt-get install -y -qq nginx mariadb-server curl unzip git

# Node.js via NodeSource (LTS)
if ! command -v node &>/dev/null; then
    info "Installing Node.js LTS..."
    curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - &>/dev/null
    apt-get install -y -qq nodejs
fi

# Composer
if ! command -v composer &>/dev/null; then
    info "Installing Composer..."
    curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer &>/dev/null
fi

success "System packages installed (PHP ${PHP_VER}, nginx, MySQL, Node $(node -v), Composer $(composer --version --no-ansi 2>/dev/null | awk '{print $3}'))"

# ─── Clone repository (if needed) ────────────────────────────────────────────
if $NEED_CLONE; then
    step "Cloning repository"

    # Use GITHUB_TOKEN for private repo access when available
    if [[ -n "$GITHUB_TOKEN" ]]; then
        CLONE_URL="https://${GITHUB_TOKEN}@github.com/ptmuldoon/Laravel-Golf.git"
    else
        CLONE_URL="$REPO_URL"
    fi

    if [[ -d "$INSTALL_DIR" && -f "${INSTALL_DIR}/artisan" ]]; then
        warn "Directory ${INSTALL_DIR} already contains a Laravel project — pulling latest..."
        git -C "$INSTALL_DIR" pull --ff-only
    else
        git clone "$CLONE_URL" "$INSTALL_DIR"
    fi
    # Remove .git for a clean production install (also removes any embedded token)
    rm -rf "${INSTALL_DIR}/.git"
    success "Repository cloned to ${INSTALL_DIR}"
fi

# ─── MySQL setup ──────────────────────────────────────────────────────────────
step "Configuring MySQL"

systemctl enable --now mariadb &>/dev/null

mysql -uroot -p"${MYSQL_ROOT_PASS}" <<SQL
CREATE DATABASE IF NOT EXISTS \`${DB_DATABASE}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER IF NOT EXISTS '${DB_USERNAME}'@'127.0.0.1' IDENTIFIED BY '${DB_PASSWORD}';
GRANT ALL PRIVILEGES ON \`${DB_DATABASE}\`.* TO '${DB_USERNAME}'@'127.0.0.1';
FLUSH PRIVILEGES;
SQL

success "Database '${DB_DATABASE}' ready, user '${DB_USERNAME}' granted."

# ─── Laravel application ──────────────────────────────────────────────────────
step "Setting up Laravel application"

cd "$INSTALL_DIR"

# .env
if [[ ! -f .env ]]; then
    cp .env.example .env
    info "Created .env from .env.example"
fi

# Write the key values we know
set_env() {
    local key="$1" val="$2"
    if grep -q "^${key}=" .env; then
        sed -i "s|^${key}=.*|${key}=${val}|" .env
    else
        echo "${key}=${val}" >> .env
    fi
}

set_env APP_NAME        "\"${APP_NAME}\""
set_env APP_ENV         "production"
set_env APP_DEBUG       "false"
set_env APP_URL         "${APP_URL}"
set_env DB_CONNECTION   "mysql"
set_env DB_HOST         "127.0.0.1"
set_env DB_PORT         "3306"
set_env DB_DATABASE     "${DB_DATABASE}"
set_env DB_USERNAME     "${DB_USERNAME}"
set_env DB_PASSWORD     "\"${DB_PASSWORD}\""
set_env SESSION_DRIVER  "database"
set_env CACHE_STORE     "database"
set_env QUEUE_CONNECTION "database"

info "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction -q

info "Generating application key..."
php artisan key:generate --force

info "Running database migrations..."
php artisan migrate --force

info "Creating super admin user..."
php artisan tinker --execute="
\App\Models\User::firstOrCreate(
    ['email' => '${ADMIN_EMAIL}'],
    [
        'name'              => '${ADMIN_NAME}',
        'password'          => bcrypt('${ADMIN_PASSWORD}'),
        'is_admin'          => true,
        'is_super_admin'    => true,
        'email_verified_at' => now(),
    ]
);
echo 'Super admin ready.';
"

info "Installing npm dependencies..."
npm ci --silent

info "Building frontend assets..."
npm run build

info "Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

info "Setting file permissions..."
# Ensure required storage subdirectories exist
mkdir -p storage/app/public \
         storage/framework/{cache,sessions,views} \
         storage/logs

chown -R "${WEB_USER}:${WEB_USER}" .
chmod -R 755 .
chmod -R 775 storage bootstrap/cache
# Protect .env – contains DB credentials and app key; no world access
chmod 640 .env

info "Creating public storage symlink..."
php artisan storage:link

success "Laravel application configured."

# ─── PHP-FPM ──────────────────────────────────────────────────────────────────
step "Configuring PHP-FPM"

PHP_FPM_SOCK="/var/run/php/php${PHP_VER}-fpm.sock"
systemctl enable --now "php${PHP_VER}-fpm" &>/dev/null
success "PHP ${PHP_VER}-FPM running (socket: ${PHP_FPM_SOCK})"

# ─── Nginx ────────────────────────────────────────────────────────────────────
step "Configuring nginx"

NGINX_CONF="/etc/nginx/sites-available/golf"
cat > "$NGINX_CONF" <<NGINX
server {
    listen 80 default_server;
    listen [::]:80 default_server;

    server_name ${SERVER_NAME};
    root ${INSTALL_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:${PHP_FPM_SOCK};
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
NGINX

# Remove all existing sites so only the golf app is served on port 80
for site in /etc/nginx/sites-enabled/*; do
    [[ -e "$site" ]] && rm -f "$site" && info "Removed $(basename "$site") from sites-enabled."
done

ln -sf "$NGINX_CONF" /etc/nginx/sites-enabled/golf

nginx -t && systemctl enable --now nginx && systemctl reload nginx
success "Nginx configured and reloaded."

# ─── Done ─────────────────────────────────────────────────────────────────────
echo
echo -e "${GREEN}${BOLD}Installation complete!${RESET}"
echo -e "  App URL    : ${CYAN}${APP_URL}${RESET}"
echo -e "  App dir    : ${INSTALL_DIR}"
echo -e "  PHP        : ${PHP_VER}"
echo -e "  Database   : ${DB_DATABASE} @ 127.0.0.1"
echo -e "  Admin      : ${CYAN}${ADMIN_EMAIL}${RESET} (super admin)"
echo
