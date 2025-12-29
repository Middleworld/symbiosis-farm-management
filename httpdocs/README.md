# 🌱 Middle World Farms - Farm Delivery System

[![License: GPL v3](https://img.shields.io/badge/License-GPLv3-blue.svg)](https://www.gnu.org/licenses/gpl-3.0)
[![Laravel](https://img.shields.io/badge/Laravel-11-red.svg)](https://laravel.com)
[![PHP](https://img.shields.io/badge/PHP-8.2+-blue.svg)](https://php.net)

> **Community Interest Company** - Enabling communities to grow and distribute quality food through technology.

A comprehensive farm delivery management system built with Laravel, designed for Community Supported Agriculture (CSA) programs, farmers' markets, and local food distribution networks.

## 🌟 Features

### 🏢 **Admin Management System**
- User authentication and role-based permissions
- Dashboard with real-time analytics
- Customer and order management
- Delivery route optimization
- Automated reporting and analytics

### 🔄 **Automated Backup System**
- Multi-site backup orchestration
- Disaster recovery tools
- Command-line restoration utilities
- Scheduled backups with timezone support
- Comprehensive backup monitoring

### 🌾 **FarmOS Integration**
- Seamless data synchronization
- Crop planning and harvest tracking
- Farm data analytics
- API-driven integration

### 🚚 **Delivery Management**
- Route planning and optimization
- Real-time delivery tracking
- Customer notification system
- Delivery scheduling and coordination

### 📊 **Data Management**
- Comprehensive database design
- Data export and reporting
- Backup and recovery systems
- Performance monitoring

## 🚀 Quick Start

### Prerequisites
- PHP 8.2+
- Composer
- Node.js & NPM
- MySQL/PostgreSQL
- Redis (optional)

### Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/middleworldfarms/farm-delivery-system.git
   cd farm-delivery-system
   ```

2. **Install PHP dependencies**
   ```bash
   composer install
   ```

3. **Install Node dependencies**
   ```bash
   npm install
   ```

4. **Environment setup**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

5. **Database setup**
   ```bash
   php artisan migrate
   php artisan db:seed
   ```

6. **Build assets**
   ```bash
   npm run build
   ```

7. **Start the application**
   ```bash
   php artisan serve
   ```

## 📖 Documentation

- [Installation Guide](docs/installation.md)
- [Configuration](docs/configuration.md)
- [API Documentation](docs/api.md)
- [Backup & Recovery](docs/backup-recovery.md)
- [Contributing](docs/contributing.md)

## 🏗️ Architecture

### Tech Stack
- **Backend**: Laravel 11 (PHP)
- **Frontend**: Blade templates, Tailwind CSS
- **Database**: MySQL/PostgreSQL
- **Cache**: Redis
- **Backup**: Spatie Laravel Backup
- **Testing**: PHPUnit

### Key Components
- `UnifiedBackupService`: Multi-site backup orchestration
- `DeliveryController`: Delivery management
- `FarmOsService`: FarmOS API integration
- `Admin Dashboard`: Comprehensive admin interface

## 🌍 Community Impact

This system was built by and for community food systems. Our mission is to:

- **Democratize food systems** through accessible technology
- **Support local farmers** with efficient distribution tools
- **Enable community ownership** of food supply chains
- **Promote sustainable agriculture** through data-driven insights

## 🤝 Contributing

We welcome contributions from developers, farmers, and community organizers!

### Ways to Contribute
- 🐛 **Bug Reports**: Use GitHub issues
- 💡 **Feature Requests**: Community-driven development
- 📖 **Documentation**: Help improve guides
- 🧪 **Testing**: Add test cases
- 🌍 **Localization**: Support multiple languages

### Development Setup
```bash
# Fork and clone
git clone https://github.com/your-username/farm-delivery-system.git

# Create feature branch
git checkout -b feature/amazing-feature

# Make changes and test
# Submit pull request
```

## 📄 License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.

**Why GPL v3?**
- Ensures derivative works remain open source
- Protects community ownership
- Prevents commercial exploitation
- Aligns with our not-for-profit mission

## 🙏 Acknowledgments

- **Laravel Community** for the amazing framework
- **Spatie** for backup and media libraries
- **FarmOS** for agricultural data standards
- **Open Source Community** for inspiration and tools

## 📞 Support

- 📧 **Email**: hello@middleworldfarms.org
- 🐛 **Issues**: [GitHub Issues](https://github.com/middleworldfarms/farm-delivery-system/issues)
- 📖 **Discussions**: [GitHub Discussions](https://github.com/middleworldfarms/farm-delivery-system/discussions)

## 🌱 Our Mission

> "To enable more folks to grow quality food through community-powered technology."

---

**Built with ❤️ by Middle World Farms CIC**

*Supporting local food systems, one line of code at a time.*
