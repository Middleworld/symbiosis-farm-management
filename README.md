# Symbiosis Farm Management - Monorepo

**A comprehensive digital ecosystem for sustainable farming operations**

This monorepo contains all components of the Middleworld Farms digital infrastructure, designed to connect sustainable farming with modern technology.

## 🌱 Project Overview

This is a unified repository containing four interconnected applications:

1. **WordPress Shop** (`httpdocs/`) - Customer-facing e-commerce site
2. **Laravel Admin** (`admin.soilsync.shop/`) - Farm management and administration
3. **farmOS** (`farmos.soilsync.shop/`) - Field operations and crop planning
4. **farmOS Field Kit** (Coming soon) - Mobile field data collection

## 📁 Repository Structure

```
symbiosis-farm-management/
├── httpdocs/                    # WordPress + WooCommerce Shop
│   ├── wp-content/
│   │   ├── plugins/
│   │   │   ├── mwf-integration/
│   │   │   ├── mwf-subscriptions/
│   │   │   ├── mwf-solidarity-pricing/
│   │   │   ├── mwf-reviews/
│   │   │   └── mwf-team-members/
│   │   └── themes/
│   └── ...
│
├── admin.soilsync.shop/         # Laravel Admin Application
│   ├── app/
│   ├── resources/
│   ├── routes/
│   ├── database/
│   └── ...
│
├── farmos.soilsync.shop/        # farmOS (Drupal)
│   ├── web/
│   ├── vendor/
│   └── ...
│
├── fieldkit/                    # farmOS Field Kit (Coming Soon)
│   └── ...
│
├── .gitignore                   # Monorepo ignore rules
└── README.md                    # This file
```

## 🚀 Quick Start

### Prerequisites

- PHP 8.3+
- MySQL 8.0+
- Node.js 18+
- Composer 2.x
- Git

### Initial Setup

1. **Clone the repository:**
   ```bash
   git clone git@github.com:Middleworld/symbiosis-farm-management.git
   cd symbiosis-farm-management
   ```

2. **Set up each application:**
   - [WordPress Setup](httpdocs/README.md)
   - [Laravel Admin Setup](admin.soilsync.shop/README.md)
   - [farmOS Setup](farmos.soilsync.shop/README.md)

## 🏗️ Architecture

### WordPress Shop (`httpdocs/`)
- **Purpose:** Customer-facing e-commerce and subscriptions
- **Tech Stack:** WordPress 6.x, WooCommerce, Custom MWF plugins
- **Database:** `wp_demo`
- **URL:** https://soilsync.shop

### Laravel Admin (`admin.soilsync.shop/`)
- **Purpose:** Farm operations, product management, business intelligence
- **Tech Stack:** Laravel 11.x, Vue.js, PostgreSQL (RAG), MySQL
- **Database:** `admin_demo`
- **URL:** https://admin.soilsync.shop

### farmOS (`farmos.soilsync.shop/`)
- **Purpose:** Field operations, crop planning, plant variety database
- **Tech Stack:** Drupal 10.x, farmOS 3.x, farm_crop_plan module
- **Database:** `farmos_demo`
- **URL:** https://farmos.soilsync.shop
- **Key Features:** 
  - Crop planning with timeline visualization
  - Plant variety taxonomy with growing data
  - Seeding/transplanting/harvest logs
  - Quick forms for rapid data entry
  - SSO integration with Laravel Admin

### farmOS Field Kit (Coming Soon)
- **Purpose:** Mobile data collection and offline field work
- **Tech Stack:** Progressive Web App (PWA)
- **Integration:** Syncs with farmOS API

## 🔗 Integration Points

### Data Flow
```
WordPress Shop ←→ Laravel Admin ←→ farmOS ←→ Field Kit
     ↓                  ↓              ↓
  Customers      Business Logic   Field Data
```

### Key Integrations
- **WordPress ↔ Laravel:** Product sync, order management, API authentication
- **Laravel ↔ farmOS:** 
  - Plant variety data and growing metadata
  - Succession planning with crop plan linking
  - Planting schedules and harvest tracking
  - SSO authentication (OAuth2/OpenID Connect)
- **farmOS ↔ Field Kit:** Real-time field observations, offline-first data collection

## 🗄️ Database Structure

| Application | Database | User | Purpose |
|------------|----------|------|---------|
| WordPress | `wp_demo` | `wp_demo_user` | E-commerce, customers, orders |
| Laravel Admin | `admin_demo` | `admin_demo_user` | Products, business logic, analytics |
| farmOS | `farmos_demo` | `farmos_demo_user` | Field data, plant varieties, logs |
| PostgreSQL RAG | `farm_rag_db` | `farm_rag_user` | AI/ML knowledge base |

## 🔧 Development Workflow

### Branch Strategy
- `main` - Stable, demo environment
- `feature/*` - New features
- `bugfix/*` - Bug fixes
- `hotfix/*` - Urgent production fixes

### Making Changes

1. **Create a feature branch:**
   ```bash
   git checkout -b feature/your-feature-name
   ```

2. **Make changes in relevant directories:**
   - WordPress: `httpdocs/wp-content/plugins/mwf-*/`
   - Laravel: `admin.soilsync.shop/app/`
   - farmOS: `farmos.soilsync.shop/web/modules/custom/`

3. **Commit with descriptive messages:**
   ```bash
   git add .
   git commit -m "feat(wordpress): Add new subscription feature"
   ```

4. **Push and create PR:**
   ```bash
   git push origin feature/your-feature-name
   ```

## 📝 Commit Message Convention

```
type(scope): brief description

Examples:
- feat(wordpress): Add solidarity pricing feature
- fix(laravel): Resolve product sync race condition
- docs(farmos): Update planting schedule guide
- chore(monorepo): Update .gitignore patterns
```

**Types:** `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

**Scopes:** `wordpress`, `laravel`, `farmos`, `fieldkit`, `monorepo`

## 🔒 Security

### Credentials Management
- **Never commit `.env` files** - Use `.env.example` templates
- **Separate database users** - Each app has isolated DB access
- **API keys stored** - In environment variables only

### Database Security
- Dedicated MySQL users per application
- No shared credentials between apps
- Minimal privileges (no global access)

## 🧪 Testing

```bash
# Laravel Admin tests
cd admin.soilsync.shop
php artisan test

# WordPress - Use WP-CLI
cd httpdocs
wp plugin list

# farmOS - Drupal testing
cd farmos.soilsync.shop
./vendor/bin/phpunit
```

## 📦 Deployment

### Demo Environment (Current)
- Server: Plesk-managed VPS
- Git: Pull-based deployment
- URL: `*.soilsync.shop`

### Production Deployment
Each application can be deployed independently:
- **WordPress:** Copy `httpdocs/` to production web root
- **Laravel:** Standard Laravel deployment process
- **farmOS:** Drupal deployment workflow

## 📚 Documentation

- [WordPress Shop Documentation](httpdocs/README.md)
- [Laravel Admin Documentation](admin.soilsync.shop/README.md)
- [farmOS Documentation](farmos.soilsync.shop/README.md)
- [API Integration Guide](admin.soilsync.shop/docs/laravel-admin-endpoints.md)
- [MWF Plugin Documentation](httpdocs/docs/mwf-integration-plugin-sample.php)

## 🤝 Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md) for development guidelines.

## 📄 License

See [LICENSE](LICENSE) for details.

## 🌍 Project Links

- **Demo Site:** https://soilsync.shop
- **Production:** https://middleworldfarms.org
- **Repository:** https://github.com/Middleworld/symbiosis-farm-management
- **Documentation:** [Wiki](https://github.com/Middleworld/symbiosis-farm-management/wiki)

## 👥 Team

**Middleworld Farms** - Sustainable farming with modern technology

---

*Part of the Middleworld Farms digital ecosystem - connecting sustainable farming with modern technology.*
