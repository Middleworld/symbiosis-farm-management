SSO Configuration - All 4 Sites Working
Date: January 6, 2026
Status: ✅ FULLY FUNCTIONAL

WHAT THIS BACKUP CONTAINS:
- Laravel admin SSO controller and views
- WordPress theme functions.php (syntax fix)
- farmOS CORS configuration
- FieldKit SSO login with timeout handling
- Session cookie settings for cross-domain

SYSTEMS WORKING:
1. WordPress (soilsync.shop) - SSO login via JWT tokens
2. Admin Dashboard (admin.soilsync.shop) - Direct access
3. farmOS (farmos.soilsync.shop) - OAuth OpenID Connect
4. FieldKit (feildkit.soilsync.shop) - Pre-authenticated tokens

KEY FIXES APPLIED:
- WordPress: Fixed missing closing brace in functions.php (line 699)
- WordPress: Set filesystem method to 'direct' (no FTP)
- farmOS: Fixed OAuth client ID (OpenID vs API client)
- farmOS: Enabled CORS for FieldKit domains
- Laravel: Changed session cookies to SameSite=None; Secure
- Laravel: Added OPTIONS route for CORS preflight
- FieldKit: Added 10-second timeout to prevent infinite spinning

TO RESTORE:
tar -xzf SSO_ALL_WORKING_YYYYMMDD_HHMM.tar.gz -C /var/www/vhosts/soilsync.shop/
