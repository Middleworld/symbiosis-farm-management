# 🤝 Contributing to Farm Delivery System

Thank you for your interest in contributing to the Farm Delivery System! This project is built by and for the farming community.

## 🌱 Code of Conduct

This project follows a community-first approach:
- Be respectful and inclusive
- Focus on what's best for the farming community
- Help newcomers and be patient with questions
- Collaborate constructively

## 🚀 Quick Start for Contributors

### 1. Fork & Clone
```bash
git clone https://github.com/your-username/farm-delivery-system.git
cd farm-delivery-system
```

### 2. Local Development Setup
```bash
# Using Docker (recommended)
docker-compose up -d
docker-compose exec app composer install
docker-compose exec app php artisan key:generate
docker-compose exec app php artisan migrate
docker-compose exec app php artisan db:seed

# Or traditional setup
cp .env.example .env
composer install
npm install && npm run dev
php artisan key:generate
php artisan migrate
php artisan db:seed
```

### 3. Create Feature Branch
```bash
git checkout -b feature/your-feature-name
```

## 🎯 Ways to Contribute

### 🐛 Bug Reports
- Use GitHub Issues
- Include steps to reproduce
- Mention your environment (PHP version, OS, etc.)
- Include error messages and logs

### 💡 Feature Requests
- Check existing issues first
- Describe the farming use case
- Explain how it benefits the community
- Consider implementation complexity

### 📖 Documentation
- Improve installation guides
- Add farming workflow examples
- Translate to other languages
- Create video tutorials

### 🧪 Testing
- Write unit tests for new features
- Test on different farm setups
- Report compatibility issues
- Add integration tests

## 📝 Development Guidelines

### Code Style
```bash
# Use Laravel Pint for formatting
./vendor/bin/pint

# Run tests
php artisan test

# Check for issues
./vendor/bin/phpstan analyse
```

### Database Changes
- Always create migrations for schema changes
- Include rollback logic
- Test with sample farm data
- Update seeders if needed

### Frontend Changes
- Use Tailwind CSS classes
- Ensure mobile responsiveness
- Test with farm-realistic data
- Follow accessibility guidelines

## 🌾 Farm-Specific Considerations

### Understanding Farm Operations
- Talk to real farmers about workflows
- Consider seasonal variations
- Think about different farm scales
- Account for varying tech literacy

### Data Privacy
- Farm data is sensitive business information
- Always encrypt customer information
- Follow GDPR/privacy guidelines
- Consider data portability

### Performance
- Farms often have slower internet
- Optimize for mobile devices
- Keep bundle sizes small
- Cache appropriately

## 🔄 Pull Request Process

### Before Submitting
- [ ] Tests pass (`php artisan test`)
- [ ] Code is formatted (`./vendor/bin/pint`)
- [ ] Documentation updated
- [ ] Migration tested
- [ ] Feature works on mobile

### PR Description Template
```markdown
## What does this PR do?
Brief description of changes

## Farming use case
How does this help farms?

## Testing
- [ ] Unit tests added/updated
- [ ] Manual testing completed
- [ ] Tested with sample farm data

## Screenshots (if UI changes)
Before/after images

## Breaking changes?
Any changes that affect existing installations
```

### Review Process
1. Automated tests run
2. Code review by maintainers
3. Community feedback (for major changes)
4. Testing with real farm data
5. Merge and release

## 🏷️ Issue Labels

- `bug` - Something isn't working
- `enhancement` - New feature request
- `documentation` - Documentation improvements
- `good first issue` - Good for new contributors
- `help wanted` - Extra attention needed
- `farm-workflow` - Relates to farm operations
- `mobile` - Mobile-specific issues
- `performance` - Performance improvements

## 🌍 Community Channels

- **GitHub Discussions**: General questions and ideas
- **GitHub Issues**: Bug reports and feature requests
- **Email**: hello@middleworldfarms.org for sensitive topics

## 🎉 Recognition

Contributors will be:
- Listed in our contributors file
- Mentioned in release notes
- Invited to farm visits (when possible!)
- Given priority support for their own farms

## 📋 Seasonal Development

### Spring (March-May)
- Focus on planting and planning features
- Succession planning improvements
- Mobile optimization for field use

### Summer (June-August)
- Delivery optimization
- Customer management
- Performance improvements

### Fall (September-November)
- Harvest tracking
- Analytics and reporting
- Data export features

### Winter (December-February)
- Planning for next season
- Major feature development
- Documentation improvements

## 🚜 Farm Testing Program

We maintain relationships with several farms for testing:
- Small market gardens (1-5 acres)
- Medium CSA operations (10-50 customers)
- Large farms (100+ customers)

Contributors can request access to anonymized test data that reflects real farm operations.

---

**Remember**: Every contribution, no matter how small, helps farmers and strengthens local food systems! 🌱
