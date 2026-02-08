# === Colors ===
RESET  = \033[0m
BOLD   = \033[1m
DIM    = \033[2m

RED    = \033[31m
GREEN  = \033[32m
YELLOW = \033[33m
BLUE   = \033[34m
PURPLE = \033[35m
CYAN   = \033[36m
WHITE  = \033[37m
GRAY   = \033[90m

BRIGHT_CYAN   = \033[96m
BRIGHT_BLUE   = \033[94m
BRIGHT_PURPLE = \033[95m
BRIGHT_GREEN  = \033[92m

# === Icons ===
ICON_ROCKET   = 🚀
ICON_DEV      = 💻
ICON_TEST     = 🧪
ICON_LINT     = 🔧
ICON_CLEAN    = 🧹
ICON_BUILD    = 🏗️
ICON_PACKAGE  = 📦
ICON_DATABASE = 🗄️
ICON_KEY      = 🔑
ICON_LOCK     = 🔒
ICON_DOCKER   = 🐳

# === Docker Compose Detection (V2 preferred, fallback to V1) ===
DOCKER_COMPOSE := $(shell docker compose version > /dev/null 2>&1 && echo "docker compose" || echo "docker-compose")

# === Docker exec shortcuts (uses service names, not container names) ===
EXEC_BACKEND  = $(DOCKER_COMPOSE) exec -T backend
EXEC_STOREFRONT = $(DOCKER_COMPOSE) exec -T storefront
EXEC_BACKEND_IT = $(DOCKER_COMPOSE) exec backend
EXEC_MYSQL_IT = $(DOCKER_COMPOSE) exec mysql

.PHONY: help install setup-env setup-db apikey admin dev test lint clean build check-docker audit update update-composer update-npm guard-wipe db\:delete

# Docker container check - used as dependency for commands that need running containers
check-docker:
	@$(DOCKER_COMPOSE) ps --status running --format '{{.Service}}' 2>/dev/null | grep -q backend || \
		(printf "$(RED)$(ICON_DOCKER) Docker containers are not running.$(RESET)\n" && \
		 printf "$(YELLOW)   Run $(CYAN)make dev$(YELLOW) or $(CYAN)make install$(YELLOW) first.$(RESET)\n" && exit 1)

# Safety check for destructive database operations
guard-wipe:
	@if [ "$(CONFIRM_WIPE)" != "yes" ]; then \
		printf "$(RED)$(ICON_DATABASE) Refusing destructive database operation.$(RESET)\n"; \
		printf "$(YELLOW)   Re-run with: $(CYAN)make $(MAKECMDGOALS) CONFIRM_WIPE=yes$(RESET)\n"; \
		exit 1; \
	fi

# Default target
help:
	@echo ""
	@printf "$(CYAN)╔════════════════════════════════════════════════════════════════════════╗$(RESET)\n"
	@printf "$(CYAN)║$(RESET)                                                                        $(CYAN)║$(RESET)\n"
	@printf "$(CYAN)║$(RESET) $(BRIGHT_CYAN)      ██████  ███    ███  ███████  ██████   ███████  ██   █████$(RESET)        $(CYAN)║$(RESET)\n"
	@printf "$(CYAN)║$(RESET) $(BRIGHT_CYAN)     ██    ██ ████  ████  ██       ██   ██  ██       ██  ██   ██$(RESET)       $(CYAN)║$(RESET)\n"
	@printf "$(CYAN)║$(RESET) $(BRIGHT_CYAN)     ██    ██ ██ ████ ██  █████    ██████   ███████  ██  ███████$(RESET)       $(CYAN)║$(RESET)\n"
	@printf "$(CYAN)║$(RESET) $(BRIGHT_CYAN)     ██    ██ ██  ██  ██  ██       ██   ██       ██  ██  ██   ██$(RESET)       $(CYAN)║$(RESET)\n"
	@printf "$(CYAN)║$(RESET) $(BRIGHT_CYAN)      ██████  ██      ██  ███████  ██   ██  ███████  ██  ██   ██$(RESET)       $(CYAN)║$(RESET)\n"
	@printf "$(CYAN)║$(RESET)                                                                        $(CYAN)║$(RESET)\n"
	@printf "$(CYAN)║$(RESET)                $(DIM)E-Commerce Platform$(RESET) $(GRAY)·$(RESET) $(DIM)Version 1.0.0$(RESET)                     $(CYAN)║$(RESET)\n"
	@printf "$(CYAN)║$(RESET)                                                                        $(CYAN)║$(RESET)\n"
	@printf "$(CYAN)╚════════════════════════════════════════════════════════════════════════╝$(RESET)\n"
	@echo ""
	@printf "$(BOLD)$(BRIGHT_CYAN)Available Commands$(RESET)\n\n"

	@printf "$(CYAN)╭─ Quick Start ──────────────────────────────────────────╮$(RESET)\n"
	@printf "$(CYAN)│$(RESET)\n"
	@printf "$(CYAN)│$(RESET)  $(ICON_ROCKET)  $(CYAN)%-18s$(RESET) %s\n" "make install" "Complete installation (recommended)"
	@printf "$(CYAN)│$(RESET)  $(ICON_DEV)  $(CYAN)%-18s$(RESET) %s\n" "make dev" "Start development environment"
	@printf "$(CYAN)│$(RESET)\n"
	@printf "$(CYAN)╰────────────────────────────────────────────────────────╯$(RESET)\n"
	@echo ""

	@printf "$(CYAN)╭─ Development ──────────────────────────────────────────╮$(RESET)\n"
	@printf "$(CYAN)│$(RESET)\n"
	@printf "$(CYAN)│$(RESET)  $(ICON_TEST)  $(CYAN)%-18s$(RESET) %s\n" "make test" "Run all tests"
	@printf "$(CYAN)│$(RESET)  $(ICON_LINT)  $(CYAN)%-18s$(RESET) %s\n" "make lint" "Run linters (check code style)"
	@printf "$(CYAN)│$(RESET)  $(ICON_LINT)  $(CYAN)%-18s$(RESET) %s\n" "make lint-fix" "Fix linting issues automatically"
	@printf "$(CYAN)│$(RESET)  $(ICON_PACKAGE)  $(CYAN)%-18s$(RESET) %s\n" "make update" "Update all dependencies"
	@printf "$(CYAN)│$(RESET)  $(ICON_PACKAGE)  $(CYAN)%-18s$(RESET) %s\n" "make update-composer" "Update Composer packages only"
	@printf "$(CYAN)│$(RESET)  $(ICON_PACKAGE)  $(CYAN)%-18s$(RESET) %s\n" "make update-npm" "Update npm packages only"
	@printf "$(CYAN)│$(RESET)  $(ICON_LOCK)  $(CYAN)%-18s$(RESET) %s\n" "make audit" "Security audit (composer + npm)"
	@printf "$(CYAN)│$(RESET)  $(ICON_CLEAN)  $(CYAN)%-18s$(RESET) %s\n" "make clean" "Clean generated files & caches"
	@printf "$(CYAN)│$(RESET)  $(ICON_BUILD)  $(CYAN)%-18s$(RESET) %s\n" "make build" "Build for production"
	@printf "$(CYAN)│$(RESET)  $(ICON_LINT)  $(CYAN)%-18s$(RESET) %s\n" "make refresh-styles" "Regenerate frontend styles"
	@printf "$(CYAN)│$(RESET)\n"
	@printf "$(CYAN)╰────────────────────────────────────────────────────────╯$(RESET)\n"
	@echo ""

	@printf "$(CYAN)╭─ Database & Setup ─────────────────────────────────────╮$(RESET)\n"
	@printf "$(CYAN)│$(RESET)\n"
	@printf "$(CYAN)│$(RESET)  $(ICON_PACKAGE)  $(CYAN)%-18s$(RESET) %s\n" "make setup-env" "Setup environment files only"
	@printf "$(CYAN)│$(RESET)  $(ICON_DATABASE)  $(CYAN)%-18s$(RESET) %s\n" "make setup-db" "Setup database (migrate + seed)"
	@printf "$(CYAN)│$(RESET)  $(ICON_DATABASE)  $(CYAN)%-18s$(RESET) %s\n" "make migrate" "Run database migrations"
	@printf "$(CYAN)│$(RESET)  $(ICON_DATABASE)  $(CYAN)%-18s$(RESET) %s\n" "make migrate-fresh" "Fresh migration with seed data (requires CONFIRM_WIPE=yes)"
	@printf "$(CYAN)│$(RESET)  $(ICON_DATABASE)  $(CYAN)%-18s$(RESET) %s\n" "make db:delete" "Delete all DB tables (requires CONFIRM_WIPE=yes)"
	@printf "$(CYAN)│$(RESET)  $(ICON_KEY)  $(CYAN)%-18s$(RESET) %s\n" "make apikey" "Generate new API key"
	@printf "$(CYAN)│$(RESET)  $(ICON_LOCK)  $(CYAN)%-18s$(RESET) %s\n" "make admin" "Create admin user"
	@printf "$(CYAN)│$(RESET)\n"
	@printf "$(CYAN)╰────────────────────────────────────────────────────────╯$(RESET)\n"
	@echo ""

	@printf "$(CYAN)╭─ Docker ───────────────────────────────────────────────╮$(RESET)\n"
	@printf "$(CYAN)│$(RESET)\n"
	@printf "$(CYAN)│$(RESET)  $(ICON_DOCKER)  $(CYAN)%-18s$(RESET) %s\n" "make docker-up" "Start Docker containers"
	@printf "$(CYAN)│$(RESET)  $(ICON_DOCKER)  $(CYAN)%-18s$(RESET) %s\n" "make docker-down" "Stop Docker containers"
	@printf "$(CYAN)│$(RESET)  $(ICON_DOCKER)  $(CYAN)%-18s$(RESET) %s\n" "make docker-logs" "View Docker logs"
	@printf "$(CYAN)│$(RESET)  $(ICON_DOCKER)  $(CYAN)%-18s$(RESET) %s\n" "make docker-rebuild" "Rebuild Docker containers"
	@printf "$(CYAN)│$(RESET)\n"
	@printf "$(CYAN)╰────────────────────────────────────────────────────────╯$(RESET)\n"
	@echo ""

# Main installation target - runs the comprehensive install script
install:
	@echo ""
	@printf "$(BRIGHT_GREEN)$(ICON_ROCKET) Starting Omersia installation...$(RESET)\n"
	@echo ""
	@if [ ! -f .env.docker ]; then \
		printf "$(YELLOW)⚠️  Creating .env.docker from .env.docker.example...$(RESET)\n"; \
		cp .env.docker.example .env.docker; \
		printf "$(CYAN)💡 Edit .env.docker to customize your environment$(RESET)\n\n"; \
	fi
	@chmod +x scripts/*.sh backend/docker-entrypoint*.sh 2>/dev/null || true
	@./scripts/install.sh

# Setup environment files
setup-env: check-docker
	@echo "Setting up environment files..."
	@cp -n backend/.env.example backend/.env 2>/dev/null || true
	@cp -n storefront/.env.example storefront/.env.local 2>/dev/null || true
	@$(EXEC_BACKEND) php artisan key:generate --ansi
	@echo "✓ Environment files ready"

# Setup database (migrations + roles/permissions)
setup-db: check-docker
	@echo "Setting up database..."
	@$(EXEC_BACKEND) php artisan migrate --force
	@$(EXEC_BACKEND) php artisan db:seed --class=RolesAndPermissionsSeeder --force
	@echo "✓ Database setup complete"

# Generate API key
apikey: check-docker
	@$(EXEC_BACKEND) php artisan apikey:generate --sync

# Create admin user
admin: check-docker
	@$(EXEC_BACKEND_IT) php artisan admin:create

# Start development
dev:
	@echo ""
	@printf "$(BRIGHT_CYAN)$(ICON_DEV) Starting development environment...$(RESET)\n"
	@printf "$(CYAN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(RESET)\n"
	@echo ""
	@$(DOCKER_COMPOSE) up -d
	@echo ""
	@printf "$(GREEN)╭─ Services Started ─────────────────────────────────────╮$(RESET)\n"
	@printf "$(GREEN)│$(RESET)\n"
	@printf "$(GREEN)│$(RESET)  $(ICON_LOCK)  Admin Panel:  $(BRIGHT_BLUE)http://localhost:8000/admin$(RESET)\n"
	@printf "$(GREEN)│$(RESET)  $(ICON_PACKAGE)  Storefront:   $(BRIGHT_BLUE)http://localhost:8000$(RESET)\n"
	@printf "$(GREEN)│$(RESET)  📧  Mailpit:      $(BRIGHT_BLUE)http://localhost:8025$(RESET)\n"
	@printf "$(GREEN)│$(RESET)\n"
	@printf "$(GREEN)╰────────────────────────────────────────────────────────╯$(RESET)\n"
	@echo ""
	@printf "$(DIM)💡 View logs: $(CYAN)make docker-logs$(RESET)\n"
	@echo ""

# Run tests
test: check-docker
	@echo ""
	@printf "$(BRIGHT_CYAN)$(ICON_TEST) Running tests...$(RESET)\n"
	@printf "$(CYAN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(RESET)\n"
	@echo ""
	@printf "$(BLUE)➜ Backend tests (PHPUnit)...$(RESET)\n"
	@$(EXEC_BACKEND) php artisan config:clear >/dev/null
	@$(EXEC_BACKEND) env APP_ENV=testing DB_CONNECTION=sqlite DB_DATABASE=:memory: php artisan test --no-coverage
	@echo ""
	@printf "$(BLUE)➜ Frontend tests (Vitest)...$(RESET)\n"
	@$(EXEC_STOREFRONT) npm run test --if-present
	@echo ""
	@printf "$(BRIGHT_GREEN)✅ All tests completed!$(RESET)\n"
	@echo ""

# Run linters
lint: check-docker
	@echo ""
	@printf "$(BRIGHT_CYAN)$(ICON_LINT) Running linters...$(RESET)\n"
	@printf "$(CYAN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(RESET)\n"
	@echo ""
	@printf "$(BLUE)➜ PHP linting (Pint)...$(RESET)\n"
	@$(EXEC_BACKEND) ./vendor/bin/pint --test
	@echo ""
	@printf "$(BLUE)➜ JavaScript/TypeScript linting (ESLint)...$(RESET)\n"
	@$(EXEC_STOREFRONT) npm run lint
	@echo ""
	@printf "$(BRIGHT_GREEN)✅ Linting completed!$(RESET)\n"
	@echo ""

# Fix linting issues
lint-fix: check-docker
	@echo ""
	@printf "$(BRIGHT_CYAN)$(ICON_LINT) Fixing linting issues...$(RESET)\n"
	@printf "$(CYAN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(RESET)\n"
	@echo ""
	@printf "$(BLUE)➜ Fixing PHP...$(RESET)\n"
	@$(EXEC_BACKEND) ./vendor/bin/pint
	@echo ""
	@printf "$(BLUE)➜ Fixing JavaScript/TypeScript...$(RESET)\n"
	@$(EXEC_STOREFRONT) npm run lint:fix
	@echo ""
	@printf "$(BRIGHT_GREEN)✅ All fixes applied!$(RESET)\n"
	@echo ""

# Clean
clean: check-docker
	@echo ""
	@printf "$(BRIGHT_CYAN)$(ICON_CLEAN) Cleaning caches and generated files...$(RESET)\n"
	@printf "$(CYAN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(RESET)\n"
	@echo ""
	@printf "$(BLUE)➜ Clearing Laravel caches...$(RESET)\n"
	@$(EXEC_BACKEND) php artisan cache:clear
	@$(EXEC_BACKEND) php artisan config:clear
	@$(EXEC_BACKEND) php artisan route:clear
	@$(EXEC_BACKEND) php artisan view:clear
	@echo ""
	@printf "$(BLUE)➜ Clearing Next.js build files...$(RESET)\n"
	@$(EXEC_STOREFRONT) rm -rf .next node_modules/.cache
	@echo ""
	@printf "$(BRIGHT_GREEN)✅ Cleanup completed!$(RESET)\n"
	@echo ""

# Refresh frontend styles (restart storefront to regenerate Tailwind classes)
refresh-styles: check-docker
	@echo ""
	@printf "$(BRIGHT_CYAN)$(ICON_LINT) Refreshing frontend styles...$(RESET)\n"
	@printf "$(CYAN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(RESET)\n"
	@echo ""
	@printf "$(BLUE)➜ Restarting storefront container...$(RESET)\n"
	@$(DOCKER_COMPOSE) restart storefront
	@echo ""
	@printf "$(BRIGHT_GREEN)✅ Styles refreshed! Tailwind classes regenerated.$(RESET)\n"
	@echo ""

# Build for production
build: check-docker
	@echo ""
	@printf "$(BRIGHT_CYAN)$(ICON_BUILD) Building for production...$(RESET)\n"
	@printf "$(CYAN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(RESET)\n"
	@echo ""
	@printf "$(BLUE)➜ Building frontend...$(RESET)\n"
	@$(EXEC_STOREFRONT) npm run build
	@echo ""
	@printf "$(BLUE)➜ Optimizing backend...$(RESET)\n"
	@$(EXEC_BACKEND) php artisan config:cache
	@$(EXEC_BACKEND) php artisan route:cache
	@$(EXEC_BACKEND) php artisan view:cache
	@echo ""
	@printf "$(BRIGHT_GREEN)✅ Build completed successfully!$(RESET)\n"
	@echo ""

# Database
migrate: check-docker
	@$(EXEC_BACKEND) php artisan migrate

migrate-fresh: check-docker guard-wipe
	@$(EXEC_BACKEND) php artisan migrate:fresh --seed

db: check-docker
	@$(EXEC_MYSQL_IT) mysql -u omersia -psecret omersia

db\:delete: check-docker guard-wipe
	@echo ""
	@printf "$(BRIGHT_CYAN)$(ICON_DATABASE) Wiping database tables...$(RESET)\n"
	@printf "$(CYAN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(RESET)\n"
	@echo ""
	@$(EXEC_BACKEND) php artisan db:wipe --force
	@echo ""
	@printf "$(BRIGHT_GREEN)✅ Database wiped. You can run: $(CYAN)make install$(RESET)\n"
	@echo ""

tinker: check-docker
	@$(EXEC_BACKEND_IT) php artisan tinker

# Update dependencies
update: check-docker
	@echo ""
	@printf "$(BRIGHT_CYAN)$(ICON_PACKAGE) Updating dependencies...$(RESET)\n"
	@printf "$(CYAN)━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━$(RESET)\n"
	@echo ""
	@printf "$(BLUE)➜ Updating Composer packages...$(RESET)\n"
	@$(EXEC_BACKEND) composer update
	@echo ""
	@printf "$(BLUE)➜ Updating npm packages...$(RESET)\n"
	@$(EXEC_STOREFRONT) npm update
	@echo ""
	@printf "$(BRIGHT_GREEN)✅ All dependencies updated!$(RESET)\n"
	@echo ""

update-composer: check-docker
	@printf "$(BLUE)➜ Updating Composer packages...$(RESET)\n"
	@$(EXEC_BACKEND) composer update

update-npm: check-docker
	@printf "$(BLUE)➜ Updating npm packages...$(RESET)\n"
	@$(EXEC_STOREFRONT) npm update

# Docker
docker-up:
	@$(DOCKER_COMPOSE) up -d

docker-down:
	@$(DOCKER_COMPOSE) down

docker-logs:
	@$(DOCKER_COMPOSE) logs -f

docker-rebuild:
	@$(DOCKER_COMPOSE) down
	@$(DOCKER_COMPOSE) build --no-cache
	@$(DOCKER_COMPOSE) up -d
