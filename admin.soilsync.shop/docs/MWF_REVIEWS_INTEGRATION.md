# MWF Reviews Plugin Integration Guide

## Overview
This document explains the integration between the Laravel product editor and the MWF Reviews WordPress plugin for product-specific review display.

## Current Implementation

### Product Editor Features
**Enable Reviews Toggle** (Added December 2024)
- Location: Right sidebar → Product Settings
- Database: `products.reviews_enabled` (boolean, default true)
- WooCommerce Sync: Updates `posts.comment_status` ('open' or 'closed')
- UI: Checkbox with star icon and MWF Reviews plugin note

### Database Schema
```sql
-- products table
reviews_enabled BOOLEAN DEFAULT TRUE
upsell_ids JSON NULL
crosssell_ids JSON NULL
```

### WooCommerce Sync Methods
**syncReviewsToWooCommerce($product)**
- Updates: `wp_posts.comment_status` = 'open' | 'closed'
- When reviews disabled: Hides WooCommerce native reviews
- When reviews enabled: Allows reviews per product

**syncUpsellsToWooCommerce($product)**
- Updates: `wp_postmeta._upsell_ids` (serialized array of WooCommerce product IDs)
- Display: "You may also like" section on product pages
- Purpose: Suggest higher-value alternatives (e.g., Large Box → Family Box)

**syncCrosssellsToWooCommerce($product)**
- Updates: `wp_postmeta._crosssell_ids` (serialized array)
- Display: Cart page "Customers also bought" section
- Purpose: Suggest complementary products (e.g., Bread + Butter)

## MWF Reviews Plugin Architecture

### Current Features
**Plugin File**: `wp-content/plugins/mwf-reviews/mwf-reviews.php`

**Google Reviews Integration**:
- API: Google Places API
- Settings: place_id + api_key (stored in WordPress options)
- Cache: 24 hours via transients
- Display: Star ratings, review text, author names

**Facebook Reviews**:
- Integration: Facebook page embed (no API needed)
- Display: Native Facebook widget via iframe

**Shortcodes**:
```php
[mwf_reviews source="all|google|facebook" limit="3" carousel="yes|no" min_rating="4"]
```

### Display Options
- Carousel mode with autoplay
- Grid layout
- Star rating visualization
- Author information
- Review timestamps
- Min rating filter

## Integration Opportunities

### Phase 1: Product-Specific Review Filtering
**Goal**: Show reviews specific to a product when viewing its WooCommerce page.

**Implementation**:
1. **Add product_id parameter to shortcode**:
   ```php
   [mwf_reviews product_id="123" source="all"]
   ```

2. **Modify plugin to filter reviews**:
   ```php
   // In mwf-reviews.php get_google_reviews() function
   function get_google_reviews($place_id, $api_key, $product_id = null) {
       // Existing code...
       
       // Filter by product if specified
       if ($product_id) {
           $reviews = array_filter($reviews, function($review) use ($product_id) {
               // Check if review text mentions product or matches metadata
               return str_contains($review['text'], get_product_name($product_id));
           });
       }
   }
   ```

3. **Store product associations in custom table**:
   ```sql
   CREATE TABLE wp_mwf_product_reviews (
       id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       product_id BIGINT UNSIGNED NOT NULL,
       review_source VARCHAR(50) NOT NULL, -- 'google', 'facebook', 'woocommerce'
       review_id VARCHAR(255) NOT NULL,
       review_text TEXT,
       rating INT,
       author_name VARCHAR(255),
       created_at DATETIME,
       UNIQUE KEY (product_id, review_source, review_id)
   );
   ```

### Phase 2: Admin Review Management
**Goal**: Let admins manually associate reviews with products.

**Features**:
1. Admin page: `wp-admin/admin.php?page=mwf-product-reviews`
2. Review list with product assignment dropdown
3. Bulk assignment actions
4. Preview before publishing

**UI Example**:
```
┌─────────────────────────────────────────────────┐
│ MWF Product Reviews                             │
├─────────────────────────────────────────────────┤
│ Google Review: "Amazing organic carrots!"       │
│ Rating: ⭐⭐⭐⭐⭐                                 │
│ Author: Sarah M.                                │
│ Assign to product: [Dropdown: Carrots ▼]       │
│ [Save Assignment]                               │
└─────────────────────────────────────────────────┘
```

### Phase 3: Laravel Integration
**Goal**: Manage product reviews from Laravel admin panel.

**Routes** (`routes/web.php`):
```php
Route::prefix('admin/products/{product}/reviews')->name('admin.products.reviews.')->group(function() {
    Route::get('/', [ProductReviewController::class, 'index'])->name('index');
    Route::post('/assign', [ProductReviewController::class, 'assign'])->name('assign');
    Route::delete('/{review}', [ProductReviewController::class, 'unassign'])->name('unassign');
});
```

**Controller** (`app/Http/Controllers/Admin/ProductReviewController.php`):
```php
class ProductReviewController extends Controller
{
    public function index(Product $product)
    {
        // Fetch reviews from WordPress database
        $reviews = DB::connection('wordpress')
            ->table('mwf_product_reviews')
            ->where('product_id', $product->woo_product_id)
            ->get();
            
        return view('admin.products.reviews.index', compact('product', 'reviews'));
    }
    
    public function assign(Request $request, Product $product)
    {
        // Assign a Google/Facebook review to this product
        DB::connection('wordpress')->table('mwf_product_reviews')->insert([
            'product_id' => $product->woo_product_id,
            'review_source' => $request->source,
            'review_id' => $request->review_id,
            'review_text' => $request->text,
            'rating' => $request->rating,
            'author_name' => $request->author,
            'created_at' => now()
        ]);
    }
}
```

### Phase 4: Frontend Display
**Goal**: Automatically show product-specific reviews on WooCommerce pages.

**Implementation in WordPress theme** (`functions.php`):
```php
// Auto-inject product reviews on WooCommerce product pages
add_action('woocommerce_after_single_product_summary', 'mwf_show_product_reviews', 15);

function mwf_show_product_reviews() {
    global $product;
    
    // Check if reviews are enabled for this product
    $reviews_enabled = get_post_meta($product->get_id(), '_reviews_enabled', true);
    
    if ($reviews_enabled === 'yes') {
        // Show MWF Reviews if available
        echo do_shortcode('[mwf_reviews product_id="' . $product->get_id() . '"]');
        
        // Also show WooCommerce native reviews
        comments_template();
    }
}
```

## Technical Considerations

### Database Connections
- **Laravel**: Connects to `wordpress` database for sync operations
- **WordPress**: Uses native `$wpdb` for database access
- **Shared Tables**: `posts`, `postmeta`, `term_relationships`

### Review Sources Priority
1. **WooCommerce Native Reviews**: Standard WordPress comments system
2. **Google Reviews**: External API, cached locally
3. **Facebook Reviews**: Embedded widget, no local storage

### Performance Optimization
- Cache product review associations for 1 hour
- Use WordPress transients for temporary storage
- Lazy load Google/Facebook reviews via AJAX
- Paginate large review lists (>10 reviews)

### Security
- Validate product IDs before database queries
- Sanitize review text before display
- Escape HTML in review content
- Rate limit Google API calls (avoid quota exhaustion)

## Development Roadmap

### Immediate Implementation (Week 1)
- ✅ Enable Reviews checkbox in product editor
- ✅ WooCommerce sync for comment_status
- ✅ Database migration for reviews_enabled column

### Phase 1 (Week 2-3)
- Create `wp_mwf_product_reviews` table
- Add product_id parameter to MWF Reviews shortcode
- Implement review filtering by product
- Test with existing Google reviews

### Phase 2 (Month 1)
- Build WordPress admin page for review assignment
- Add bulk assignment functionality
- Create review preview system

### Phase 3 (Month 2)
- Create Laravel ProductReviewController
- Build review management UI in Laravel admin
- Implement cross-database review assignment

### Phase 4 (Month 3)
- Auto-inject reviews on WooCommerce pages
- Add review moderation workflow
- Implement review response system

## Testing Strategy

### Unit Tests
```php
// tests/Feature/ProductReviewTest.php
public function test_reviews_sync_to_woocommerce()
{
    $product = Product::factory()->create([
        'reviews_enabled' => true,
        'woo_product_id' => 123
    ]);
    
    $controller = new ProductController();
    $controller->syncReviewsToWooCommerce($product);
    
    $commentStatus = DB::connection('wordpress')
        ->table('posts')
        ->where('ID', 123)
        ->value('comment_status');
        
    $this->assertEquals('open', $commentStatus);
}
```

### Integration Tests
- Test review assignment via Laravel admin
- Verify WordPress displays assigned reviews
- Check Google API integration
- Validate Facebook embed rendering

## Configuration Files

### Environment Variables (`.env`)
```env
# MWF Reviews Plugin Settings
MWF_GOOGLE_PLACES_API_KEY=your_api_key_here
MWF_GOOGLE_PLACE_ID=ChIJ...your_place_id
MWF_FACEBOOK_PAGE_URL=https://facebook.com/middleworldfarms
MWF_REVIEWS_CACHE_DURATION=86400  # 24 hours
```

### WordPress Options (stored in `wp_options`)
```php
// Set via WordPress admin or programmatically
update_option('mwf_google_api_key', 'YOUR_KEY');
update_option('mwf_google_place_id', 'YOUR_PLACE_ID');
update_option('mwf_facebook_page_url', 'https://facebook.com/...');
update_option('mwf_reviews_per_page', 10);
update_option('mwf_min_rating_display', 4);
```

## Documentation Links

### External Resources
- [Google Places API Documentation](https://developers.google.com/maps/documentation/places/web-service)
- [WooCommerce Reviews System](https://woocommerce.com/document/reviews/)
- [WordPress Transients API](https://developer.wordpress.org/apis/handbook/transients/)

### Internal Documentation
- `COPILOT-INSTRUCTIONS.md`: Project architecture overview
- `README.md`: Main project documentation
- WordPress plugin: `wp-content/plugins/mwf-reviews/README.txt`

## Support

For questions about this integration:
1. Check existing Laravel ProductController sync methods
2. Review WordPress MWF Reviews plugin code
3. Test in staging environment (`admin.soilsync.shop`) first
4. Document any changes in this file

---

**Last Updated**: December 24, 2025  
**Version**: 1.0  
**Status**: Phase 1 Complete (Reviews toggle implemented)
