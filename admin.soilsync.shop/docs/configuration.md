# Configuration Guide

## Environment Variables

Copy `.env.example` to `.env` and configure the following variables:

### Database Configuration
```bash
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_username
DB_PASSWORD=your_db_password
```

### IONOS Backup Service Configuration

To enable IONOS cloud metrics monitoring in the admin dashboard:

1. **Get IONOS API Credentials:**
   - Log into your IONOS account
   - Navigate to **Management > Backup > API Access**
   - Generate API credentials (username and password)

2. **Configure Environment Variables:**
   ```bash
   IONOS_USERNAME=your_ionos_api_username
   IONOS_PASSWORD=your_ionos_api_password
   IONOS_BASE_URL=https://eu16-cloud.acronis.com
   ```

3. **Restart Services:**
   ```bash
   php artisan config:cache
   php artisan config:clear
   ```

### Optional Services

#### WooCommerce Integration
```bash
WC_API_URL=https://your-woocommerce-site.com
WC_CONSUMER_KEY=your_consumer_key
WC_CONSUMER_SECRET=your_consumer_secret
```

#### AI Service (Optional)
```bash
AI_SERVICE_ENABLED=true
AI_SERVICE_URL=http://localhost:11434
AI_MODEL=llama3.1
```

#### Google Maps API (for delivery optimization)
```bash
GOOGLE_MAPS_API_KEY=your_google_maps_api_key
```

## Admin Setup

1. Run database migrations:
   ```bash
   php artisan migrate
   ```

2. Create admin user:
   ```bash
   php artisan tinker
   >>> App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password'), 'role' => 'admin']);
   ```

3. Access admin panel at `/admin` with your credentials.
