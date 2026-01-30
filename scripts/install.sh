#!/bin/bash
set -e

# ========================================
# Omersia Installation Script
# ========================================
# Complete setup using Docker containers
# Usage: ./scripts/install.sh
# Non-interactive: INTERACTIVE=false ./scripts/install.sh
# Option: DB_NAME=mydb INTERACTIVE=false ./scripts/install.sh
# ========================================

# Configuration
PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

# Ensure all scripts are executable (fixes Windows/NTFS permission issues)
chmod +x "$PROJECT_ROOT/scripts/"*.sh 2>/dev/null || true
chmod +x "$PROJECT_ROOT/backend/docker-entrypoint"*.sh 2>/dev/null || true
BACKEND_DIR="$PROJECT_ROOT/backend"
STOREFRONT_DIR="$PROJECT_ROOT/storefront"
INTERACTIVE="${INTERACTIVE:-true}"
DEMO_DATA="${DEMO_DATA:-false}"
ADMIN_EMAIL="${ADMIN_EMAIL:-admin@omersia.com}"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-}"
PASSWORD_AUTO_GENERATED="false"

# Database defaults
DB_NAME="${DB_NAME:-omersia}"
DB_ROOT_PASSWORD="${DB_ROOT_PASSWORD:-secret}"
DB_USERNAME="${DB_USERNAME:-omersia}"
DB_PASSWORD="${DB_PASSWORD:-secret}"

# Source CLI utilities
source "$PROJECT_ROOT/scripts/cli-utils.sh"

# Error handler
error_exit() {
    print_error "$1"
    exit 1
}

# ========================================
# MAIN INSTALLATION
# ========================================

print_banner

# Step 1: Check Prerequisites
print_step_fancy "1" "10" "Checking prerequisites..." "$ICON_SEARCH"

# Check Docker
if ! command -v docker &> /dev/null; then
    error_exit "Docker is not installed. Please install Docker first."
fi
print_success "Docker found: $(docker --version | head -n1)"

# Check Docker Compose (V2 preferred, fallback to V1)
if docker compose version &> /dev/null; then
    DOCKER_COMPOSE="docker compose"
    print_success "Docker Compose V2 found"
elif command -v docker-compose &> /dev/null; then
    DOCKER_COMPOSE="docker-compose"
    print_success "Docker Compose V1 found"
else
    error_exit "Docker Compose is not installed. Please install Docker Compose first."
fi

# Check if Docker daemon is running
if ! docker info &> /dev/null; then
    error_exit "Docker daemon is not running. Please start Docker first."
fi
print_success "Docker daemon is running"

echo ""

# Step 2: Copy .env files
print_step_fancy "2" "10" "Setting up environment files..." "$ICON_PACKAGE"

if [ ! -f "$BACKEND_DIR/.env" ]; then
    cp "$BACKEND_DIR/.env.example" "$BACKEND_DIR/.env"
    print_success "Created backend/.env"
else
    print_warning "backend/.env already exists, skipping"
fi

if [ ! -f "$STOREFRONT_DIR/.env.local" ]; then
    cp "$STOREFRONT_DIR/.env.example" "$STOREFRONT_DIR/.env.local"
    print_success "Created storefront/.env.local"
else
    print_warning "storefront/.env.local already exists, skipping"
fi

# Verify backend .env has correct Docker settings
if grep -q "DB_HOST=mysql" "$BACKEND_DIR/.env"; then
    print_success "backend/.env configured for Docker MySQL"
else
    print_warning "Updating backend/.env for Docker MySQL..."
    sed_inplace 's/DB_HOST=127.0.0.1/DB_HOST=mysql/g' "$BACKEND_DIR/.env"
    sed_inplace 's/DB_USERNAME=root/DB_USERNAME=omersia/g' "$BACKEND_DIR/.env"
    sed_inplace 's/DB_PASSWORD=$/DB_PASSWORD=secret/g' "$BACKEND_DIR/.env"
    sed_inplace 's/MAIL_HOST=127.0.0.1/MAIL_HOST=mailpit/g' "$BACKEND_DIR/.env"
    print_success "Updated backend/.env for Docker"
fi

# Ensure DB_DATABASE exists and is set
if grep -q "^DB_DATABASE=" "$BACKEND_DIR/.env"; then
    sed_inplace "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" "$BACKEND_DIR/.env"
else
    echo "DB_DATABASE=$DB_NAME" >> "$BACKEND_DIR/.env"
fi

echo ""

# Step 3: Build and start Docker services
print_step_fancy "3" "10" "Building and starting Docker services..." "$ICON_DOCKER"

cd "$PROJECT_ROOT"

# Ensure root .env exists with defaults before first docker compose up
# (Docker Compose reads this file for variable substitution)
if [ ! -f ".env" ]; then
    cat > ".env" <<EOF
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD
DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD
FRONT_API_KEY=temporary_key_will_be_replaced
EOF
    print_success "Created root .env with defaults"
fi

# Show spinner while building
show_spinner "Building Docker images..." &
SPINNER_PID=$!
$DOCKER_COMPOSE build --quiet 2>&1 | grep -v "Warning" || true
stop_spinner $SPINNER_PID "success" "Docker images built"

# Show spinner while starting
show_spinner "Starting containers..." &
SPINNER_PID=$!
$DOCKER_COMPOSE up -d 2>&1 | grep -v "Warning" || true
stop_spinner $SPINNER_PID "success" "Docker services started"

echo ""

# Step 4: Wait for MySQL
print_step_fancy "4" "10" "Waiting for MySQL to be ready..." "$ICON_DATABASE"

# Start spinner while waiting
show_spinner "Connecting to MySQL..." &
SPINNER_PID=$!

MAX_ATTEMPTS=30
ATTEMPT=0
while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    if docker exec omersia-mysql mysqladmin ping -h localhost --silent &> /dev/null; then
        stop_spinner $SPINNER_PID "success" "MySQL is ready!"
        break
    fi
    ATTEMPT=$((ATTEMPT + 1))
    if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
        stop_spinner $SPINNER_PID "error" "MySQL failed to start"
        error_exit "MySQL failed to start after $MAX_ATTEMPTS attempts"
    fi
    sleep 2
done

echo ""

# Step 4.5: Database configuration
print_step_fancy "4.5" "10" "Database configuration..." "$ICON_DATABASE"

if [ "$INTERACTIVE" = "true" ]; then
    echo ""
    printf "${CYAN}Database name${RESET} [${DIM}$DB_NAME${RESET}]: "
    read -r INPUT_DB
    if [ -n "$INPUT_DB" ]; then
        DB_NAME="$INPUT_DB"
    fi
fi

print_success "Using database: $DB_NAME"

# Write root .env for docker-compose (DB + root pass)
cd "$PROJECT_ROOT"
if [ -f ".env" ]; then
    # Update if exists
    grep -q "^DB_DATABASE=" ".env" && sed_inplace "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" ".env" || echo "DB_DATABASE=$DB_NAME" >> ".env"
    grep -q "^DB_USERNAME=" ".env" && sed_inplace "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/" ".env" || echo "DB_USERNAME=$DB_USERNAME" >> ".env"
    grep -q "^DB_PASSWORD=" ".env" && sed_inplace "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" ".env" || echo "DB_PASSWORD=$DB_PASSWORD" >> ".env"
    grep -q "^DB_ROOT_PASSWORD=" ".env" && sed_inplace "s/^DB_ROOT_PASSWORD=.*/DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD/" ".env" || echo "DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD" >> ".env"
else
    cat > ".env" <<EOF
DB_DATABASE=$DB_NAME
DB_USERNAME=$DB_USERNAME
DB_PASSWORD=$DB_PASSWORD
DB_ROOT_PASSWORD=$DB_ROOT_PASSWORD
EOF
fi

# Update backend .env for Laravel too (useful if you exec outside docker env)
if grep -q "^DB_DATABASE=" "$BACKEND_DIR/.env"; then
    sed_inplace "s/^DB_DATABASE=.*/DB_DATABASE=$DB_NAME/" "$BACKEND_DIR/.env"
else
    echo "DB_DATABASE=$DB_NAME" >> "$BACKEND_DIR/.env"
fi

# Apply new env to containers: MySQL must be recreated because MYSQL_DATABASE is only applied on first init
show_spinner "Recreating containers to apply new DB env..." &
SPINNER_PID=$!
$DOCKER_COMPOSE up -d --force-recreate mysql backend nginx storefront >/dev/null 2>&1
stop_spinner $SPINNER_PID "success" "Containers recreated"

# Wait again for mysql (quick)
show_spinner "Waiting MySQL after recreate..." &
SPINNER_PID=$!
MAX_ATTEMPTS=30
ATTEMPT=0
while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    if docker exec omersia-mysql mysqladmin ping -h localhost --silent &> /dev/null; then
        stop_spinner $SPINNER_PID "success" "MySQL ready after recreate"
        break
    fi
    ATTEMPT=$((ATTEMPT + 1))
    [ $ATTEMPT -eq $MAX_ATTEMPTS ] && stop_spinner $SPINNER_PID "error" "MySQL failed" && error_exit "MySQL failed after recreate"
    sleep 2
done

# Ensure DB exists (idempotent)
show_spinner "Ensuring database '$DB_NAME' exists..." &
SPINNER_PID=$!
docker exec omersia-mysql mysql -uroot -p"$DB_ROOT_PASSWORD" -e "
CREATE DATABASE IF NOT EXISTS \`$DB_NAME\`
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
" >/dev/null 2>&1 || {
    stop_spinner $SPINNER_PID "error" "DB create failed"
    error_exit "Failed to create database '$DB_NAME'"
}
stop_spinner $SPINNER_PID "success" "Database ensured"

# NEW: Grant privileges to app user on selected database
show_spinner "Granting privileges on '$DB_NAME'..." &
SPINNER_PID=$!
docker exec omersia-mysql mysql -uroot -p"$DB_ROOT_PASSWORD" -e "
CREATE USER IF NOT EXISTS '$DB_USERNAME'@'%' IDENTIFIED BY '$DB_PASSWORD';
GRANT ALL PRIVILEGES ON \`$DB_NAME\`.* TO '$DB_USERNAME'@'%';
FLUSH PRIVILEGES;
" >/dev/null 2>&1 || {
    stop_spinner $SPINNER_PID "error" "Privileges grant failed"
    error_exit "Failed to grant privileges for '$DB_USERNAME' on '$DB_NAME'"
}
stop_spinner $SPINNER_PID "success" "Privileges granted"

# Clear Laravel caches so config is reloaded
docker exec omersia-backend php artisan config:clear >/dev/null 2>&1 || true
docker exec omersia-backend php artisan cache:clear >/dev/null 2>&1 || true

echo ""

# Step 5: Wait for backend dependencies
print_step_fancy "5" "10" "Installing backend dependencies..." "$ICON_PACKAGE"

# Start spinner while waiting
show_spinner "Installing Composer packages..." &
SPINNER_PID=$!

MAX_ATTEMPTS=60
ATTEMPT=0
while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    if docker exec omersia-backend test -f /var/www/html/vendor/autoload.php &> /dev/null; then
        stop_spinner $SPINNER_PID "success" "Backend dependencies installed!"
        break
    fi
    ATTEMPT=$((ATTEMPT + 1))
    if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
        stop_spinner $SPINNER_PID "error" "Installation failed"
        error_exit "Backend dependencies failed to install after $MAX_ATTEMPTS attempts. Check logs: docker-compose logs backend"
    fi
    sleep 3
done

echo ""

# Step 6: Generate APP_KEY and run migrations
print_step_fancy "6" "10" "Setting up Laravel application..." "$ICON_KEY"

show_spinner "Generating APP_KEY..." &
SPINNER_PID=$!
docker exec omersia-backend php artisan key:generate --ansi > /dev/null 2>&1
stop_spinner $SPINNER_PID "success" "APP_KEY generated"

show_spinner "Running database migrations..." &
SPINNER_PID=$!
docker exec omersia-backend php artisan migrate --force > /dev/null 2>&1
stop_spinner $SPINNER_PID "success" "Database migrations completed"

echo ""

# Step 7: Seed shop, roles and permissions
print_step_fancy "7" "10" "Seeding initial data..." "$ICON_DATABASE"

show_spinner "Creating shop..." &
SPINNER_PID=$!
docker exec omersia-backend php artisan db:seed --class=ShopSeeder --force > /dev/null 2>&1
stop_spinner $SPINNER_PID "success" "Shop created"

show_spinner "Setting up roles and permissions..." &
SPINNER_PID=$!
docker exec omersia-backend php artisan db:seed --class=RolesAndPermissionsSeeder --force > /dev/null 2>&1
stop_spinner $SPINNER_PID "success" "Roles and permissions configured"

show_spinner "Installing default theme..." &
SPINNER_PID=$!
docker exec omersia-backend php artisan db:seed --class=DefaultThemeSeeder --force > /dev/null 2>&1
stop_spinner $SPINNER_PID "success" "Default theme installed"

echo ""

# Step 8: Ask for demo data (interactive only)
print_step_fancy "8" "10" "Demo data setup..." "$ICON_SPARKLES"

if [ "$INTERACTIVE" = "true" ]; then
    echo ""
    printf "${YELLOW}Would you like to install demo products? (y/N)${RESET}\n"
    read -r -p "> " RESPONSE
    echo ""
    if [[ "$RESPONSE" =~ ^[Yy]$ ]]; then
        DEMO_DATA="true"
    fi
fi

if [ "$DEMO_DATA" = "true" ]; then
    show_spinner "Importing demo products..." &
    SPINNER_PID=$!
    docker exec omersia-backend php artisan db:seed --class=DemoProductsSeeder --force
    stop_spinner $SPINNER_PID "success" "Demo products imported"

    show_spinner "Creating demo menu..." &
    SPINNER_PID=$!
    docker exec omersia-backend php artisan db:seed --class=DemoMenuSeeder --force
    stop_spinner $SPINNER_PID "success" "Demo menu created"

    show_spinner "Initializing homepage with demo content..." &
    SPINNER_PID=$!
    docker exec omersia-backend php artisan omersia:init-pages --demo --force
    stop_spinner $SPINNER_PID "success" "Homepage initialized with demo content"
else
    print_info "Skipping demo data"

    show_spinner "Initializing homepage..." &
    SPINNER_PID=$!
    docker exec omersia-backend php artisan omersia:init-pages --force
    stop_spinner $SPINNER_PID "success" "Homepage initialized"
fi

# Refresh storefront to regenerate Tailwind CSS classes
show_spinner "Refreshing frontend styles..." &
SPINNER_PID=$!
$DOCKER_COMPOSE restart storefront > /dev/null 2>&1
stop_spinner $SPINNER_PID "success" "Frontend styles refreshed"

echo ""

# Step 9: Create admin user
print_step_fancy "9" "10" "Creating admin user..." "$ICON_LOCK"

if [ "$INTERACTIVE" = "true" ]; then
    echo ""
    print_info "Enter admin credentials"
    echo ""

    # Ask for email
    printf "${CYAN}Email${RESET} [${DIM}$ADMIN_EMAIL${RESET}]: "
    read -r INPUT_EMAIL
    if [ -n "$INPUT_EMAIL" ]; then
        ADMIN_EMAIL="$INPUT_EMAIL"
    fi

    # Ask for password
    printf "${CYAN}Password${RESET} (leave empty to auto-generate): "
    read -rs INPUT_PASSWORD
    echo ""

    if [ -n "$INPUT_PASSWORD" ]; then
        ADMIN_PASSWORD="$INPUT_PASSWORD"
    else
        ADMIN_PASSWORD=$(openssl rand -base64 12)
        PASSWORD_AUTO_GENERATED="true"
        printf "${YELLOW}Generated password: ${ADMIN_PASSWORD}${RESET}\n"
    fi

    echo ""
    show_spinner "Creating admin user..." &
    SPINNER_PID=$!
    docker exec omersia-backend php artisan admin:create --email="$ADMIN_EMAIL" --password="$ADMIN_PASSWORD" --no-interaction > /dev/null 2>&1
    stop_spinner $SPINNER_PID "success" "Admin user created"
else
    # Non-interactive mode
    if [ -z "$ADMIN_PASSWORD" ]; then
        ADMIN_PASSWORD=$(openssl rand -base64 12)
        PASSWORD_AUTO_GENERATED="true"
        print_warning "Generated random password: $ADMIN_PASSWORD"
    fi

    show_spinner "Creating admin user..." &
    SPINNER_PID=$!
    docker exec omersia-backend php artisan admin:create --email="$ADMIN_EMAIL" --password="$ADMIN_PASSWORD" --no-interaction 2>/dev/null || {
        stop_spinner $SPINNER_PID "warning" "Admin creation via CLI failed"
        print_warning "You may need to create it manually"
    }
    stop_spinner $SPINNER_PID "success" "Admin user created: $ADMIN_EMAIL"
fi

echo ""

# Step 10: Generate API key
print_step_fancy "10" "10" "Generating API key..." "$ICON_KEY"

show_spinner "Generating API key..." &
SPINNER_PID=$!
API_KEY_OUTPUT=$(docker exec omersia-backend php artisan apikey:generate --sync --force 2>&1)
stop_spinner $SPINNER_PID "success" "API key generated and synced"

# Extract API key from output (64 character alphanumeric string)
API_KEY=$(echo "$API_KEY_OUTPUT" | grep -oE 'Key:[[:space:]]+[a-zA-Z0-9]+' | awk '{print $2}')

# Update root .env for docker-compose
cd "$PROJECT_ROOT"
if [ -n "$API_KEY" ]; then
    if [ -f ".env" ]; then
        # Update existing .env
        if grep -q "^FRONT_API_KEY=" ".env"; then
            sed_inplace "s/^FRONT_API_KEY=.*/FRONT_API_KEY=$API_KEY/" ".env"
        else
            echo "FRONT_API_KEY=$API_KEY" >> ".env"
        fi
    else
        # Create new .env
        echo "FRONT_API_KEY=$API_KEY" > ".env"
    fi
fi

# Restart storefront to pick up new API key
$DOCKER_COMPOSE up -d --force-recreate storefront >/dev/null 2>&1 || true

echo ""

# ========================================
# INSTALLATION COMPLETE
# ========================================

echo ""
echo ""
print_double_separator 75
echo ""
typewriter_effect "🎉  Installation Complete! Omersia is ready to use." 0.03
echo ""
print_double_separator 75
echo ""

# Admin credentials box
if [ -n "$ADMIN_EMAIL" ]; then
    echo ""
    printf "${GREEN}╭─ ${ICON_LOCK} Admin Credentials ──────────────────────────────────╮${RESET}\n"
    printf "${GREEN}│${RESET}\n"
    printf "${GREEN}│${RESET}  Email:    ${BRIGHT_CYAN}%-45s${RESET}\n" "$ADMIN_EMAIL"
    if [ "$PASSWORD_AUTO_GENERATED" = "true" ]; then
        printf "${GREEN}│${RESET}  Password: ${BRIGHT_YELLOW}%-45s${RESET}\n" "$ADMIN_PASSWORD"
    else
        printf "${GREEN}│${RESET}  Password: ${DIM}%-45s${RESET}\n" "••••••••••••••••"
    fi
    printf "${GREEN}│${RESET}\n"
    printf "${GREEN}╰────────────────────────────────────────────────────────╯${RESET}\n"
fi

# API Key box
if [ -n "$API_KEY" ]; then
    echo ""
    printf "${BLUE}╭─ ${ICON_KEY} API Key ────────────────────────────────────────────╮${RESET}\n"
    printf "${BLUE}│${RESET}\n"
    printf "${BLUE}│${RESET}  ${BRIGHT_CYAN}%-54s${RESET}\n" "$API_KEY"
    printf "${BLUE}│${RESET}  ${DIM}%-54s${RESET}\n" "(saved in storefront/.env.local)"
    printf "${BLUE}│${RESET}\n"
    printf "${BLUE}╰────────────────────────────────────────────────────────╯${RESET}\n"
fi

# Access URLs box
echo ""
printf "${BRIGHT_CYAN}╭─ 🌐 Access URLs ───────────────────────────────────────╮${RESET}\n"
printf "${BRIGHT_CYAN}│${RESET}\n"
printf "${BRIGHT_CYAN}│${RESET}  ${ICON_LOCK}  Admin panel:  ${BRIGHT_BLUE}%-32s${RESET}\n" "http://localhost:8000/admin"
printf "${BRIGHT_CYAN}│${RESET}  ${ICON_PACKAGE}  Storefront:   ${BRIGHT_BLUE}%-32s${RESET}\n" "http://localhost:8000"
printf "${BRIGHT_CYAN}│${RESET}  📧  Mailpit:      ${BRIGHT_BLUE}%-32s${RESET}\n" "http://localhost:8025"
printf "${BRIGHT_CYAN}│${RESET}\n"
printf "${BRIGHT_CYAN}╰────────────────────────────────────────────────────────╯${RESET}\n"

# Useful commands box
echo ""
printf "${CYAN}╭─ 💡 Useful Commands ───────────────────────────────────╮${RESET}\n"
printf "${CYAN}│${RESET}\n"
printf "${CYAN}│${RESET}  ${BRIGHT_CYAN}make dev${RESET}              Start development environment\n"
printf "${CYAN}│${RESET}  ${BRIGHT_CYAN}make test${RESET}             Run tests\n"
printf "${CYAN}│${RESET}  ${BRIGHT_CYAN}make lint${RESET}             Run linters\n"
printf "${CYAN}│${RESET}  ${BRIGHT_CYAN}docker-compose logs -f${RESET}  View logs\n"
printf "${CYAN}│${RESET}\n"
printf "${CYAN}╰────────────────────────────────────────────────────────╯${RESET}\n"

echo ""
echo ""
printf "${BRIGHT_GREEN}${ICON_SPARKLES} Happy coding! ${ICON_SPARKLES}${RESET}\n"
echo ""
echo ""
