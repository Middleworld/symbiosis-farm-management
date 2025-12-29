# MWF FarmOS System

Farm management and planning system for Middleworld Farms, built on Drupal/FarmOS.

## 🌱 Overview

This FarmOS installation provides comprehensive farm management capabilities including:

- **Crop Planning**: Plan and track seasonal crop rotations
- **Harvest Scheduling**: Coordinate harvest timing with delivery schedules  
- **Field Management**: Track field usage, soil conditions, and crop history
- **Livestock Tracking**: Monitor animals and their movements
- **Equipment Management**: Track farm equipment and maintenance
- **Financial Records**: Record income, expenses, and profitability by crop/field

## 🔧 Technical Stack

- **Platform**: Drupal 9/10 with FarmOS distribution
- **Database**: MySQL/MariaDB 
- **API**: JSON:API with custom endpoints
- **Authentication**: OAuth2 integration with Laravel Admin
- **Hosting**: Subdomain on Ionos/Plesk

## 🔗 Integration

Integrates with the MWF ecosystem:

- **WordPress Main Site**: Customer-facing website at `middleworldfarms.org`
- **Laravel Admin**: Backend management at `middleworldfarms.org/admin`
- **POS System**: Self-serve shop for on-site sales

## 📁 Project Structure

```
/
├── web/                    # Drupal root directory
│   ├── modules/custom/     # Custom MWF modules
│   ├── modules/contrib/    # Contributed modules (git ignored)
│   ├── core/              # Drupal core (git ignored)
│   └── sites/default/     # Site configuration
├── vendor/                # Composer dependencies
├── composer.json          # PHP dependencies
└── README.md              # This file
```

## 🚀 Development Setup

1. **Clone Repository**:
   ```bash
   git clone https://github.com/YOUR_GITHUB_USERNAME/mwf-farmos.git
   cd mwf-farmos
   ```

2. **Install Dependencies**:
   ```bash
   composer install
   ```

3. **Configure Database**:
   - Copy `web/sites/default/default.settings.php` to `web/sites/default/settings.php`
   - Configure database connection settings

4. **Install FarmOS**:
   ```bash
   cd web
   ../vendor/bin/drush site:install farmos
   ```

## 📡 API Endpoints

Custom JSON:API endpoints for MWF integration:

### Menu API
- `GET /api/menu-items/{menu_name}` - Get menu structure for Laravel Admin

### Crop Planning API  
- `GET /api/crops/planning` - Get current crop planning data
- `POST /api/crops/planning` - Update crop planning

### Harvest Schedule API
- `GET /api/harvest/schedule` - Get harvest schedule
- `POST /api/harvest/schedule` - Update harvest timing

## 🔐 Authentication

OAuth2 integration allows Laravel Admin to:
- Access FarmOS data via API
- Sync harvest schedules with delivery planning
- Coordinate crop availability with website inventory

## 🌍 Environment Configuration

Environment-specific settings in `web/sites/default/settings.php`:

```php
// Database configuration
$databases['default']['default'] = [
  'database' => 'farmos_db',
  'username' => 'farmos_user', 
  'password' => 'your_password',
  'host' => 'localhost',
  'driver' => 'mysql',
];

// API base URL for cross-system integration
$config['farmos_api']['base_url'] = 'https://middleworldfarms.org/farmos';
```

## 📚 Documentation

- [FarmOS Official Documentation](https://docs.farmOS.org/)
- [Drupal API Documentation](https://api.drupal.org/)
- [MWF System Integration Guide](../admin/SYSTEM-INTEGRATION.md)

## 🤝 Contributing

This is a private repository for Middleworld Farms. Development follows:

1. Feature branches for new functionality
2. Pull requests for code review
3. Integration testing with Laravel Admin
4. Staging deployment before production

## 📞 Support

For technical issues or feature requests:
- System Administrator: [Your contact details]
- FarmOS Community: [farmOS.org community](https://farmOS.org/community)

---

*Part of the Middleworld Farms digital ecosystem - connecting sustainable farming with modern technology.*
