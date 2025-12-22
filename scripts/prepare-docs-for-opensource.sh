#!/bin/bash
# Master Documentation Cleanup Script
# Runs all documentation preparation steps for open source release

REPO_ROOT="/var/www/vhosts/soilsync.shop/admin.soilsync.shop"
cd "$REPO_ROOT"

echo "╔════════════════════════════════════════════╗"
echo "║  Documentation Cleanup for Open Source    ║"
echo "╚════════════════════════════════════════════╝"
echo ""
echo "This script will:"
echo "  1. Audit current documentation"
echo "  2. Sanitize URLs (replace production URLs with generic)"
echo "  3. Reorganize files into user/dev/internal structure"
echo "  4. Create documentation index"
echo ""
read -p "Continue? (y/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Cancelled."
    exit 1
fi

echo ""
echo "═══════════════════════════════════════════"
echo "  Step 1: Audit Documentation"
echo "═══════════════════════════════════════════"
./scripts/audit-documentation.sh

echo ""
echo "═══════════════════════════════════════════"
echo "  Step 2: Sanitize URLs"
echo "═══════════════════════════════════════════"
./scripts/sanitize-documentation-urls.sh

echo ""
echo "═══════════════════════════════════════════"
echo "  Step 3: Reorganize Files"
echo "═══════════════════════════════════════════"
./scripts/reorganize-documentation.sh

echo ""
echo "═══════════════════════════════════════════"
echo "  Step 4: Create User Manual Index"
echo "═══════════════════════════════════════════"

cat > docs/user-manual/README.md << 'EOF'
# 📖 User Manual

Welcome to the Farm Delivery System user manual. This guide will help you understand and use all features of the system.

## 🚀 Getting Started

### For New Users
1. [System Overview](#system-overview)
2. [Basic Navigation](#basic-navigation)
3. [Your First Login](#your-first-login)

### Quick Access
- [Subscription Management](SUBSCRIPTION_MANAGEMENT.md)
- [Delivery Management](DELIVERY_MANAGEMENT.md)
- [Task System](TASK_SYSTEM.md)
- [CRM Usage](CRM_USAGE.md)

## 📚 Feature Guides

### Core Features
- **[Subscription Management](SUBSCRIPTION_MANAGEMENT.md)** - Creating and managing customer subscriptions
- **[Delivery Management](DELIVERY_MANAGEMENT.md)** - Planning routes and tracking deliveries
- **[User Management](USER_MANAGEMENT.md)** - Managing staff and customer accounts

### Farm Operations
- **[Succession Planning](SUCCESSION_PLANNING.md)** - Crop planning and harvest scheduling
- **[Task System](TASK_SYSTEM.md)** - Creating and tracking farm tasks

### Business Tools
- **[CRM Usage](CRM_USAGE.md)** - Customer relationship management
- **[POS Integration](POS_INTEGRATION.md)** - Point of sale system integration

## 🎯 Common Tasks

### Subscription Management
- Creating a new subscription
- Pausing/resuming subscriptions
- Changing subscription plans
- Processing renewals

### Delivery Operations
- Planning delivery routes
- Assigning drivers
- Tracking deliveries
- Handling collection days

### Farm Planning
- Creating crop succession plans
- Scheduling plantings
- Tracking harvests
- Managing bed occupancy

## 💡 Tips & Best Practices

### Daily Workflow
1. Check pending tasks
2. Review delivery schedule
3. Process new subscriptions
4. Update crop plans

### Weekly Tasks
- Review subscription renewals
- Plan next week's deliveries
- Update crop succession
- Check task completion

## 🆘 Getting Help

- Check the relevant feature guide above
- See [Troubleshooting](TROUBLESHOOTING.md)
- Contact your system administrator

## 📝 Glossary

- **Subscription**: A recurring order from a customer
- **Vegbox**: Weekly vegetable box delivery
- **Succession Planning**: Scheduled crop plantings for continuous harvest
- **Collection Day**: Alternative to delivery where customers pick up orders

---

Need developer documentation? See [Developer Guide](../developer/README.md)
EOF

echo "Created: docs/user-manual/README.md"

echo ""
echo "═══════════════════════════════════════════"
echo "  Step 5: Create Developer Guide Index"
echo "═══════════════════════════════════════════"

cat > docs/developer/README.md << 'EOF'
# 👨‍💻 Developer Guide

Welcome to the Farm Delivery System developer documentation. This guide covers architecture, APIs, and integrations.

## 🏗️ Architecture

- **Framework**: Laravel 11 (PHP 8.2+)
- **Database**: MySQL/MariaDB (multi-database architecture)
- **Frontend**: Blade templates, Alpine.js, Tailwind CSS
- **AI Service**: Python FastAPI service (CPU-based)

### Multi-Database Architecture
The system integrates three databases:
- **Laravel (`mysql`)**: Primary application data
- **WordPress (`wordpress`)**: WooCommerce integration
- **farmOS (`farmos`)**: Farm management data

## 📡 API Reference

### Subscription API
- [Subscription Endpoints](api/SUBSCRIPTION_API.md)
- [Webhook Integration](api/WEBHOOK_API.md)
- [Laravel API Setup](api/LARAVEL_API.md)

### Common Endpoints
```
GET  /api/subscriptions/user/{id}    - Get user subscriptions
POST /api/subscriptions               - Create subscription
PUT  /api/subscriptions/{id}          - Update subscription
POST /api/subscriptions/{id}/action   - Perform action (pause/resume/cancel)
```

## 🔌 Integrations

### External Services
- [FarmOS Authentication](integrations/FARMOS_AUTH.md)
- [FarmOS API Integration](integrations/FARMOS_API.md)
- [Stripe Payments](integrations/STRIPE.md)
- [3CX CRM](integrations/3CX_CRM.md)
- [Claude AI](integrations/CLAUDE_AI.md)
- [Weather RAG](integrations/WEATHER_RAG.md)

### FarmOS Integration
- [Plant Types Setup](integrations/FARMOS_PLANT_TYPES.md)
- [Spacing Fields](integrations/FARMOS_SPACING.md)

## 🛠️ Development

### Setup
```bash
git clone https://github.com/your-org/farm-delivery-system.git
cd farm-delivery-system
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

### Configuration
See [Configuration Guide](CONFIGURATION.md) for environment variables and settings.

### Testing
```bash
composer test                 # Run all tests
php artisan test              # PHPUnit
php artisan test --filter=SubscriptionTest
```

### Code Style
- Follow PSR-12 coding standards
- Use meaningful variable names
- Document complex logic
- Write tests for new features

## 🏃 Running Locally

### Development Server
```bash
composer dev    # Runs server, queue, logs, vite concurrently
```

Or individually:
```bash
php artisan serve              # Web server (port 8000)
php artisan queue:listen       # Background jobs
php artisan pail               # Real-time logs
npm run dev                    # Vite asset compilation
```

### AI Service
```bash
cd ai_service
python -m venv venv
source venv/bin/activate
pip install -r requirements.txt
uvicorn app.main:app --reload
```

## 📦 Key Services

### Laravel Services
- `FarmOSApi` - farmOS integration
- `SymbiosisAIService` - AI crop planning
- `VegboxPaymentService` - Subscription payments
- `WeatherService` - Met Office integration

### Service Layer Pattern
All external integrations use dedicated service classes in `app/Services/`.

## 🔐 Security

### Authentication
- Laravel Sanctum for API tokens
- OAuth2 for farmOS integration
- Session-based for admin panel

### Best Practices
- Always validate user input
- Use parameterized queries
- Implement CSRF protection
- Rate limit API endpoints

## 📊 Database

### Migrations
```bash
php artisan migrate              # Run migrations
php artisan migrate:rollback     # Rollback last batch
php artisan migrate:fresh        # Drop all tables and re-run
```

### Models
Key models in `app/Models/`:
- `VegboxSubscription` - Native subscription management
- `PlantVariety` - Crop variety data (synced from farmOS)
- `DeliverySchedule` - Delivery scheduling

## 🤝 Contributing

See [CONTRIBUTING.md](../../CONTRIBUTING.md) for:
- Git workflow
- Pull request process
- Code review guidelines
- Documentation standards

## 📚 Additional Resources

- [Laravel Documentation](https://laravel.com/docs)
- [farmOS API Documentation](https://farmos.org/development/api/)
- [Stripe API Reference](https://stripe.com/docs/api)

---

Need user documentation? See [User Manual](../user-manual/README.md)
EOF

echo "Created: docs/developer/README.md"

echo ""
echo "╔════════════════════════════════════════════╗"
echo "║  Documentation Cleanup Complete!          ║"
echo "╚════════════════════════════════════════════╝"
echo ""
echo "✅ Documentation has been:"
echo "   • Audited for hardcoded URLs"
echo "   • Sanitized with generic placeholders"
echo "   • Reorganized into clear structure"
echo "   • Indexed with README files"
echo ""
echo "📁 New structure:"
echo "   docs/"
echo "     ├── README.md (main index)"
echo "     ├── user-manual/ (for end users)"
echo "     ├── developer/ (for developers)"
echo "     ├── deployment/ (for admins)"
echo "     └── internal/ (implementation notes)"
echo ""
echo "🔍 Review changes:"
echo "   git status"
echo "   git diff"
echo ""
echo "📝 Commit changes:"
echo "   git add ."
echo "   git commit -m 'docs: prepare documentation for open source release'"
echo ""
echo "⚠️  Manual review needed:"
echo "   1. Check moved files still work"
echo "   2. Update internal cross-references"
echo "   3. Verify no sensitive data in docs"
echo "   4. Test documentation links"
echo ""
