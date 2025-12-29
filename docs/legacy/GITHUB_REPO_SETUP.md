# GitHub Repository Configuration

## Repository Name
`symbiosis-farm-management`

## Short Description (for GitHub repo settings)
Open-source CSA farm management system with subscription boxes, delivery scheduling, crop planning, and farmOS integration. Built with Laravel 12 for Community Supported Agriculture operations.

## Topics/Tags
```
laravel
php
csa
farm-management
agriculture
community-supported-agriculture
woocommerce
farmos
crop-planning
delivery-management
subscription-management
open-source
regenerative-farming
market-garden
smallholding
```

## About Section
🌱 Complete farm administration platform for CSA operations | Subscription management | Delivery scheduling | Crop planning | farmOS integration | WooCommerce sync

## Repository Settings

### Features to Enable
- [x] Issues
- [x] Discussions
- [x] Projects
- [x] Wiki
- [ ] Sponsorship (optional)

### Branch Protection (main/master)
- Require pull request reviews before merging
- Require status checks to pass
- Include administrators in restrictions

### Labels to Create
- `bug` - Something isn't working
- `enhancement` - New feature or request
- `documentation` - Documentation improvements
- `good first issue` - Good for newcomers
- `help wanted` - Extra attention is needed
- `crop-planning` - Related to crop planning features
- `subscriptions` - Related to subscription management
- `deliveries` - Related to delivery scheduling
- `farmos` - Related to farmOS integration
- `woocommerce` - Related to WooCommerce integration
- `ai` - Related to AI features

---

## Initial Git Setup Commands

```bash
cd /var/www/vhosts/soilsync.shop/admin.soilsync.shop

# Initialize git repository
git init

# Add all files
git add .

# Create initial commit
git commit -m "Initial commit: Symbiosis Farm Management System

- Laravel 12 base application
- CSA subscription management with decoupled billing/delivery
- Distribution schedule for deliveries and collections
- farmOS integration for crop tracking
- WooCommerce webhook integration
- AI-powered crop planning (optional)
- Met Office weather integration (UK)
- Multi-database architecture (Laravel + WordPress + farmOS)"

# Add remote origin (replace with your repo URL)
git remote add origin https://github.com/YOUR_ORG/symbiosis-farm-management.git

# Push to GitHub
git branch -M main
git push -u origin main
```

---

## License File (MIT)

Create `LICENSE` file with MIT license text for open-source distribution.

---

## .gitignore Essentials

Ensure these are in `.gitignore`:
```
/vendor/
/node_modules/
.env
.env.backup
.env.production
/storage/*.key
/storage/app/
/storage/framework/
/storage/logs/
/public/hot
/public/storage
/public/build/
*.log
.phpunit.result.cache
Homestead.json
Homestead.yaml
auth.json
```
