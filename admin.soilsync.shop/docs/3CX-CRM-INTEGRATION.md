# 3CX CRM Integration Setup

## Overview
Your farm management system now has a built-in CRM that integrates with your 3CX phone system. When someone calls, their customer information automatically pops up in your browser.

## Configuration

### 1. Admin Settings (Already Configured)
- Go to: `https://admin.middleworldfarms.org/admin/settings`
- Navigate to **API Keys** tab
- Scroll to **3CX Phone System - CRM Integration** section
- The URL is already set to: `https://admin.middleworldfarms.org/crm/contact?phone=%CallerNumber%&name=%CallerDisplayName%`

### 2. Configure in 3CX Management Console
1. Log into 3CX at: `https://pineappletelecoms2.3cx.uk:5001`
2. Go to: **Settings → Integration → CRM Integration**
3. Paste this URL:
   ```
   https://admin.middleworldfarms.org/crm/contact?phone=%CallerNumber%&name=%CallerDisplayName%
   ```
4. Set **"Notify when"** to: **Ringing**
5. Click **Save**

### 3. Test It
Call your DID number: **01522 449 610**

When you answer, a browser tab should automatically open showing:
- Customer name, email, phone
- Billing and shipping addresses
- Recent order history
- Customer lifetime value
- Quick action buttons (email, call back, add note)

## Features

### Automatic Caller Lookup
- Searches WooCommerce customers by phone number
- Handles UK phone number variations (07XXX, +447XXX, etc.)
- Shows "New Caller" badge if not found

### Customer Information Displayed
- **Contact Details**: Name, email, phone, addresses
- **Order History**: Last 10 orders with status, items, totals
- **Customer Stats**: Total orders, lifetime value, average order
- **Customer Notes**: Previous call notes and interactions

### Quick Actions
- **Send Email**: Opens mailto link
- **Call Back**: Dial customer's number
- **Add Note**: Record call details for future reference
- **View in WooCommerce**: Opens customer record in WP admin

### Phone Number Matching
The system intelligently matches UK phone numbers in multiple formats:
- `01234567890`
- `+441234567890`
- `07912345678`
- `+447912345678`

## Adding Customer Notes
1. When viewing a customer, click **Add Note**
2. Select note type:
   - Phone Call
   - Complaint
   - Query
   - Feedback
   - General
3. Type your note
4. Click **Save Note**

Notes are stored in your admin database and visible on future calls.

## Database Structure

### Tables Used
- **WordPress/WooCommerce DB** (read-only):
  - `users` - Customer accounts
  - `usermeta` - Customer phone numbers, addresses
  - `posts` - Orders (post_type = 'shop_order')
  - `postmeta` - Order details
  - `woocommerce_order_items` - Order line items
  - `woocommerce_order_itemmeta` - Item details

- **Admin DB** (read-write):
  - `customer_notes` - Call notes and interactions

### No External CRM Needed
You don't need HubSpot, Salesforce, or any external CRM. All your customer data comes from WooCommerce, which you already use for orders and subscriptions.

## Troubleshooting

### Customer Not Found
If a known customer doesn't appear:
1. Check their WooCommerce account has a phone number in **Billing Phone** field
2. Try searching manually in the "No Customer Found" screen
3. Phone number might be formatted differently - system handles most variations

### Page Doesn't Pop Up
1. Check browser isn't blocking popups from 3CX
2. Verify the CRM URL is correct in 3CX settings
3. Make sure "Notify when" is set to "Ringing"

### TAPI Integration
You probably don't need this. TAPI is only for:
- Legacy desktop CRM software (Act!, Goldmine, old Outlook)
- Desktop phone softphones

For web-based CRM (which you now have), just use the "Open Contact URL" setting.

## URL Structure

### Contact Lookup URL
```
https://admin.middleworldfarms.org/crm/contact?phone=%CallerNumber%&name=%CallerDisplayName%
```

**Variables:**
- `%CallerNumber%` - Replaced by 3CX with caller's phone number
- `%CallerDisplayName%` - Replaced by 3CX with caller ID name (if available)

## Future Enhancements

Possible additions:
- Click-to-dial functionality (call customers from admin)
- Call history logging
- Automatic email templates
- SMS integration
- Delivery schedule display during calls
- Farm box subscription details
- Payment status alerts

## Technical Details

**Controller:** `app/Http/Controllers/Admin/CrmController.php`
**View:** `resources/views/admin/crm/contact.blade.php`
**Routes:** `routes/web.php` (admin.crm.contact, admin.crm.addNote)

**Authentication:** Uses admin.auth middleware (must be logged into admin)

**Performance:** Page loads in ~200-500ms, auto-refreshes every 5 minutes

---

**Support:** For issues, check logs in `/opt/sites/admin.middleworldfarms.org/storage/logs/laravel.log`
