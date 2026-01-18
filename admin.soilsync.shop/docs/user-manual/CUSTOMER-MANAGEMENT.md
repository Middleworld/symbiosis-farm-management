# Customer Management Guide

## Overview

The Customer Management system provides comprehensive tools for managing your farm's customers, including search, filtering, user switching, and SMS campaigns. This guide covers all features available on the `/admin/customers` page.

**Note**: This page shows all WordPress users who either have customer/subscriber roles OR have active vegbox subscriptions. Only users that exist in the WordPress database are displayed, as these are the customers you can actively manage within the system.

## Accessing Customer Management

Navigate to **Admin → Customers** in the main navigation menu, or visit `/admin/customers` directly.

## Customer List Features

### Search and Filtering

The customer list supports multiple filtering options:

- **Search**: Search by customer name, email, or username
- **Customer Filter**:
  - `All`: Show all customers
  - `Has Orders`: Only customers with order history
  - `Subscribers`: Only customers with active subscriptions
  - `Recent`: Customers who joined in the last 30 days

- **Order Filter**:
  - `Any`: No order count filtering
  - `None`: Customers with no orders
  - `Some`: Customers with 1-4 orders
  - `Many`: Customers with 5+ orders

- **Date Filter**:
  - `Any`: No date filtering
  - `Today`: Customers who joined today
  - `This Week`: Customers who joined this week
  - `This Month`: Customers who joined this month
  - `Older`: Customers who joined more than a month ago

### Customer Information Display

Each customer row shows:
- **Name**: Customer's display name (from billing info or WordPress profile)
- **Email**: Customer's email address
- **Phone**: Billing phone number (if available)
- **Subscribed**: Whether the customer has active subscriptions
- **Joined**: Registration date
- **Orders**: Total number of orders
- **Last Order**: Date of most recent order

## Customer Actions

### View Customer Details

Click the **Details** button next to any customer to view comprehensive information including:
- Full customer profile
- Order history
- Subscription details
- Billing information
- Communication preferences

### User Switching (Impersonation)

The **Switch** button allows administrators to temporarily log in as a customer to:
- View their My Account page
- See their subscription status
- Access their order history
- Test customer-facing features

**Security Note**: User switching logs are maintained for audit purposes.

### SMS Campaigns

Send bulk SMS messages to selected customers:

1. Click **Send SMS Campaign**
2. Select recipients (all customers or filtered results)
3. Compose your message
4. Review and send

**SMS Limits**: Monitor your remaining SMS credits in the campaign interface.

## Customer Data Sources

The system integrates customer data from multiple sources:

### WordPress Users
- Primary customer database
- User roles and capabilities
- Profile information and metadata

### WooCommerce Integration
- Order history and purchase data
- Subscription information
- Billing and shipping addresses
- Payment method details

### Vegbox Subscriptions (Native)
- Native Laravel subscription management
- Enhanced delivery scheduling
- Advanced billing features
- Active subscription tracking

**Note**: The "Active Subscribers" filter checks both WooCommerce subscriptions and native Vegbox subscriptions to provide comprehensive subscriber data.

## Troubleshooting

### Common Issues

**"No customers found"**
- Check your search filters
- Verify WordPress user roles are set correctly
- Ensure WooCommerce integration is active

**"Switch user failed"**
- Verify the customer account is active
- Check WordPress user permissions
- Review error logs for authentication issues

**"SMS campaign failed"**
- Verify SMS service configuration
- Check account balance/credits
- Ensure phone numbers are valid

### Performance Tips

- Use specific search terms rather than broad filters
- Apply multiple filters to narrow results quickly
- The system supports up to 100 customers per page
- Large customer databases (>1000) may require pagination

## API Integration

For developers integrating with the customer system:

### Endpoints
- `GET /admin/customers` - List customers with filtering
- `GET /admin/customers/details/{userId}` - Customer details
- `POST /admin/customers/switch/{userId}` - Switch to user
- `POST /admin/customers/sms-campaign` - Send SMS campaign
- `GET /admin/customers/sms-stats` - Campaign statistics

### Data Format
Customer data is returned in JSON format with pagination metadata.

## Recent Updates

**January 2026**: Fixed critical bug where customer page showed "Target class does not exist" error. The CustomerManagementController now properly loads customer data from WordPress and WooCommerce databases.

**January 2026**: Fixed "Active Subscribers" filter returning zero results. The filter now checks both WooCommerce subscriptions and native Vegbox subscriptions for comprehensive subscriber detection.

**January 2026**: Enhanced customer visibility. Customer Management page now shows all users with active subscriptions, not just those with WordPress customer roles, providing complete customer oversight.

**January 2026**: Fixed subscription status detection. Now uses the same active subscription criteria as the Vegbox Subscriptions page for consistent status reporting.

## Support

For technical support or feature requests related to customer management:
- Check the application logs for error details
- Review the [Contributing Guide](../CONTRIBUTING.md) for development information
- Contact the development team for assistance</content>
<parameter name="filePath">/var/www/vhosts/soilsync.shop/admin.soilsync.shop/docs/user-manual/CUSTOMER-MANAGEMENT.md