# Contributing to Middle World Farms - Farm Delivery System

## Repository Overview

This is a monorepo containing multiple applications for the Middle World Farms Community Supported Agriculture (CSA) delivery management system:

- **Laravel Admin** (`admin.soilsync.shop/`): Main administration interface with farmOS integration
- **farmOS** (`farmos.soilsync.shop/`): Farm data management system
- **FieldKit** (`fieldkit.soilsync.shop/`): Vue.js PWA for field data collection
- **WordPress** (`httpdocs/`): Customer-facing website and WooCommerce integration
- **AI Service** (`ai_service/`): Python service for crop planning and AI features
- **ROS Integration** (`ros/`): Robot Operating System components for farm automation

## Development Environment Setup

### Prerequisites
- Docker and Docker Compose
- Git
- Node.js 18+ and npm
- PHP 8.2+ and Composer
- Python 3.8+ and pip

### Quick Start with Docker (Recommended)

```bash
# Clone the repository
git clone https://github.com/your-org/soilsync-shop.git
cd soilsync-shop

# Start all services
docker-compose up -d

# Run database migrations
docker-compose exec laravel php artisan migrate

# Install dependencies for each service
docker-compose exec laravel composer install
docker-compose exec fieldkit npm install
docker-compose exec ai-service pip install -r requirements.txt
```

### Manual Setup

1. **Laravel Admin Setup:**
   ```bash
   cd admin.soilsync.shop
   composer install
   cp .env.example .env
   php artisan key:generate
   # Configure database and external service credentials in .env
   php artisan migrate
   npm install && npm run dev
   ```

2. **farmOS Setup:**
   - farmOS runs in a separate Docker container
   - OAuth2 credentials configured in Laravel .env file
   - Database connection configured for direct queries

3. **FieldKit Setup:**
   ```bash
   cd fieldkit.soilsync.shop/packages/field-kit
   npm install
   cp .env.local .env.local # Configure OAuth settings
   npm run dev
   ```

4. **AI Service Setup:**
   ```bash
   cd ai_service
   pip install -r requirements.txt
   cp .env.example .env # Configure API keys
   python main.py
   ```

## Development Workflow

### Branching Strategy
- `main`/`master`: Production branch (protected)
- `demo`: Staging/development branch
- Feature branches: `feature/feature-name`
- Bug fixes: `fix/issue-description`

### Commit Guidelines
- Use conventional commits: `feat:`, `fix:`, `docs:`, `refactor:`
- Keep commits focused and atomic
- Write clear commit messages

### Testing
```bash
# Laravel tests
cd admin.soilsync.shop
composer test

# AI service tests
cd ai_service
python -m pytest

# FieldKit tests
cd fieldkit.soilsync.shop/packages/field-kit
npm test
```

## Architecture Notes

### Database Connections
- **Laravel DB**: MySQL (`mysql` connection)
- **WordPress DB**: MySQL (`wordpress` connection)
- **farmOS DB**: MySQL (`farmos` connection) - direct queries for performance

### Performance Considerations
- Use direct farmOS database queries for reads (50ms vs 2-30s API calls)
- farmOS API only for writes and complex operations
- AI processing requires 60-90s timeouts (CPU-only server)

### Security
- Never commit `.env` files or sensitive credentials
- Use `.env.example` files for configuration templates
- All sensitive data is excluded via `.gitignore`

## Common Issues

### farmOS OAuth Issues
- Ensure OAuth client is configured in farmOS admin
- Check `FARMOS_OAUTH_*` variables in Laravel `.env`
- Clear config cache: `php artisan config:clear`

### Database Connection Issues
- Verify all three database connections in `config/database.php`
- Check credentials in `.env` files
- Ensure farmOS database allows remote connections

### AI Service Timeouts
- AI operations require 60-90s timeouts
- Check Python service logs for errors
- Verify CPU-only processing limitations

## Deployment

### Staging (Demo Branch)
```bash
git checkout demo
# Make changes
git push origin demo
# Automatic deployment via GitHub Actions
```

### Production (Main Branch)
```bash
git checkout main
git merge demo
git push origin main
# Automatic deployment via GitHub Actions
```

## Getting Help

- Check existing issues and documentation
- Review commit history for similar changes
- Contact the development team for architecture questions

## Code of Conduct

- Respect the monorepo structure
- Test changes thoroughly before committing
- Document significant changes
- Follow existing patterns and conventions