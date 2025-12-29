# Collection Day Preference System

## Overview
Customers can now choose their preferred collection day (Friday or Saturday) on the My Account page in WordPress. The admin delivery schedule automatically groups collections by each customer's preferred day.

## What Was Implemented

### 1. WordPress My Account Page
**Location:** `/var/www/vhosts/middleworldfarms.org/httpdocs/wp-content/themes/divi-child/functions.php`

**Features:**
- Collection day selector added to My Account > Account Details
- **Only visible to collection customers** (customers with shipping = £0.00)
- Delivery customers do NOT see this option
- Options: Friday or Saturday only
- Saved to WordPress user meta: `preferred_collection_day`
- Required field with validation

### 2. Admin Schedule Grouping
**Location:** `app/Http/Controllers/Admin/DeliveryController.php`

**Changes:**
- **Deliveries:** Now grouped by Thursday (changed from Monday)
- **Collections:** Grouped by customer's preferred day (Friday or Saturday)
  - Fetches preferences from WordPress database in batch
  - Defaults to Friday if no preference set
- Collections appear under their correct day on the schedule

### 3. Historical Data Analysis
**Command:** `php artisan collections:update-preferred-days`

**What it does:**
- Analyzes `delivery_completions` table
- Uses `completed_at` timestamp (actual day customer collected)
- Counts Friday vs Saturday collections per customer
- Ignores other days (Monday/Thursday/etc)
- Converts Sunday to Saturday
- Updates WordPress user meta with most common day
- Shows confidence percentage

**Results from first run:**
- Analyzed 123 collection completions
- Updated 17 customers
- 10 customers prefer Friday (59%)
- 7 customers prefer Saturday (41%)

**Options:**
- `--dry-run`: Preview changes without saving
- Run without flag to apply changes

### 4. Database Schema

**delivery_completions table:**
```
- external_id: Subscription ID
- type: 'delivery' or 'collection'
- delivery_date: Scheduled date (legacy, shows Monday)
- completed_at: ACTUAL timestamp when marked complete
- customer_name, customer_email: For reference
```

**WordPress usermeta:**
```
- meta_key: 'preferred_collection_day'
- meta_value: 'Friday' or 'Saturday'
```

## How It Works

1. **Customer Journey:**
   - Customer logs into My Account
   - If they have collection subscription, sees "Preferred Collection Day" dropdown
   - Selects Friday or Saturday
   - Saves to WordPress database

2. **Admin Schedule:**
   - Loads all collection customers
   - Fetches preferred_collection_day from WordPress for each
   - Groups collections by their preferred day
   - Thursday shows all deliveries
   - Friday shows Friday collections
   - Saturday shows Saturday collections

3. **Completion Tracking:**
   - When admin marks collection complete, `completed_at` timestamp is recorded
   - This captures the ACTUAL day they came
   - Historical analysis uses this to recommend preference

## Future Improvements

- For new customers without history: Default to Saturday (busier day)
- Add reminder emails on customer's preferred day
- Analytics dashboard showing Friday vs Saturday breakdown
- Suggest day changes if one day is getting too crowded

## Maintenance Commands

```bash
# Preview what would be updated based on completion history
php artisan collections:update-preferred-days --dry-run

# Actually update preferences
php artisan collections:update-preferred-days

# Check WordPress database
mysql wp_pxmxy -e "SELECT user_id, meta_value FROM usermeta WHERE meta_key='preferred_collection_day';"

# Check Laravel completion data
mysql admin_db -e "SELECT external_id, DAYNAME(completed_at) as day, COUNT(*) FROM delivery_completions WHERE type='collection' GROUP BY external_id, day;"
```

## Files Modified

1. `app/Http/Controllers/Admin/DeliveryController.php`
   - Added `fetchCollectionDayPreferences()` method
   - Modified `transformScheduleData()` to accept and use preferences
   - Deliveries: Thursday (line ~377)
   - Collections: Customer's preferred day (line ~402-422)

2. `app/Console/Commands/UpdateCollectionDaysFromHistory.php`
   - Analyzes `completed_at` timestamps
   - Filters to Friday/Saturday only
   - Updates WordPress user meta

3. `/var/www/vhosts/middleworldfarms.org/httpdocs/wp-content/themes/divi-child/functions.php`
   - `mwf_add_collection_day_field()`: Display selector
   - `mwf_validate_collection_day_field()`: Validate input
   - `mwf_save_collection_day_field()`: Save to user meta
   - Only shown to collection customers
