# 📚 Documentation Index

Welcome to the Farm Delivery System documentation. This documentation is organized for different audiences and use cases.

## 🚀 Quick Links

### For New Users
- [Installation Guide](user-manual/INSTALLATION.md) - Get started with your first installation
- [User Manual](user-manual/README.md) - Learn how to use the system
- [Configuration Guide](user-manual/CONFIGURATION.md) - Basic setup and configuration

### For Developers
- [Developer Guide](developer/README.md) - Architecture and development workflow
- [API Reference](developer/api/README.md) - API endpoints and integration
- [Contributing](../CONTRIBUTING.md) - How to contribute to the project

### For System Administrators
- [Deployment Guide](deployment/README.md) - Production deployment instructions
- [Backup & Recovery](deployment/BACKUP_RECOVERY.md) - Disaster recovery procedures
- [Security](deployment/SECURITY.md) - Security best practices

## 📁 Documentation Structure

```
docs/
├── user-manual/          # End-user documentation
│   ├── README.md         # User manual home
│   ├── INSTALLATION.md   # Installation instructions
│   ├── CONFIGURATION.md  # Configuration guide
│   ├── DELIVERY_MANAGEMENT.md
│   ├── SUBSCRIPTION_MANAGEMENT.md
│   ├── TASK_SYSTEM.md
│   └── CRM_USAGE.md
│
├── developer/            # Developer documentation
│   ├── README.md         # Developer guide home
│   ├── ARCHITECTURE.md   # System architecture
│   ├── DATABASE.md       # Database schema
│   ├── TESTING.md        # Testing guide
│   ├── api/              # API documentation
│   │   ├── README.md
│   │   ├── SUBSCRIPTION_API.md
│   │   └── WEBHOOK_API.md
│   └── integrations/     # Integration guides
│       ├── FARMOS.md
│       ├── WOOCOMMERCE.md
│       ├── STRIPE.md
│       └── 3CX.md
│
├── deployment/           # Deployment documentation
│   ├── README.md
│   ├── REQUIREMENTS.md   # Server requirements
│   ├── INSTALLATION.md   # Production installation
│   ├── BACKUP_RECOVERY.md
│   └── SECURITY.md
│
└── internal/             # Internal/development notes
    └── *.md              # Implementation notes, status reports
```

## 🔍 Finding Documentation

### By Feature
- **Subscription Management**: [User Manual](user-manual/SUBSCRIPTION_MANAGEMENT.md) | [API Reference](developer/api/SUBSCRIPTION_API.md)
- **Delivery Routes**: [User Manual](user-manual/DELIVERY_MANAGEMENT.md)
- **FarmOS Integration**: [Developer Guide](developer/integrations/FARMOS.md)
- **Crop Planning**: [User Manual](user-manual/SUCCESSION_PLANNING.md)
- **Task System**: [User Manual](user-manual/TASK_SYSTEM.md)
- **CRM Integration**: [User Manual](user-manual/CRM_USAGE.md) | [Developer Guide](developer/integrations/3CX.md)

### By Audience

**End Users & Farm Managers**
- Start with [User Manual](user-manual/README.md)
- Focus on day-to-day operations
- No technical knowledge required

**Developers & Integrators**
- Start with [Developer Guide](developer/README.md)
- API references and code examples
- Architecture and design patterns

**System Administrators**
- Start with [Deployment Guide](deployment/README.md)
- Server configuration and maintenance
- Security and backup procedures

## 🌐 Generic Examples

All documentation uses generic examples:
- URLs: `https://your-domain.com/` (not specific production URLs)
- API Keys: `your-api-key-here` (not real credentials)
- Database: `your_database` (not actual database names)
- Emails: `admin@example.com` (not real email addresses)

**Replace placeholders with your actual values** when following guides.

## 🤝 Contributing to Documentation

- Documentation files use Markdown format (`.md`)
- Keep URLs and credentials generic for open source
- Include code examples where helpful
- Add screenshots for UI features (stored in `/docs/images/`)
- Follow the existing structure

See [CONTRIBUTING.md](../CONTRIBUTING.md) for more details.

## 📝 Documentation Standards

### URL References
❌ **Don't**: `https://admin.middleworldfarms.org:8444/api/subscriptions`
✅ **Do**: `https://your-domain.com/api/subscriptions`

### Code Examples
```php
// Use generic configuration references
$apiUrl = config('app.url') . '/api/subscriptions';

// Not hardcoded URLs
$apiUrl = 'https://admin.middleworldfarms.org:8444/api/subscriptions';
```

### Environment Variables
```bash
# Use .env placeholders
APP_URL=https://your-domain.com
DB_DATABASE=your_database_name

# Not actual production values
APP_URL=https://admin.middleworldfarms.org:8444
DB_DATABASE=admin_db
```

## 📮 Support

- **Issues**: [GitHub Issues](https://github.com/middleworldfarms/admin-middleworldfarms/issues)
- **Discussions**: [GitHub Discussions](https://github.com/middleworldfarms/admin-middleworldfarms/discussions)
- **Wiki**: [GitHub Wiki](https://github.com/middleworldfarms/admin-middleworldfarms/wiki)

## 📄 License

This documentation is part of the Farm Delivery System project, licensed under GPLv3.

---

**Last Updated**: December 2025  
**Documentation Version**: 1.0.0
