# BeautyShop API Routing Summary

## What Changed

Your BeautyShop backend now uses **clean API URLs** instead of direct file access:

| Old URL | New URL | Method | Purpose |
|---------|---------|--------|---------|
| `process_booking.php` | `/backend/api/bookings` | POST | Create booking & initiate M-Pesa |
| `get_bookings.php` | `/backend/api/bookings` | GET | Retrieve bookings |
| `mpesa_callback.php` | `/backend/api/callback` | POST | M-Pesa webhook |
| `demo.php` | `/backend/api/demo` | GET | Demo testing UI |

## How It Works

The `.htaccess` file in `/backend/` automatically rewrites clean URLs to the appropriate PHP files:

```apache
POST /api/bookings → process_booking.php
GET /api/bookings → get_bookings.php
POST /api/callback → mpesa_callback.php
GET /api/demo → demo.php
```

**Benefits:**
- ✅ Cleaner, more professional API endpoints
- ✅ Better code organization
- ✅ Easier to add authentication/middleware later
- ✅ Industry standard RESTful structure
- ✅ Backward compatible (direct file access still works)

## Files Updated

1. **`.htaccess`** - Created with rewrite rules
2. **`script.js`** - Updated booking form to use `/api/bookings`
3. **`demo.php`** - Updated internal fetch calls to use `/api/` URLs
4. **`API_ROUTING.md`** - Complete routing documentation

## Installation

No additional configuration needed! The `.htaccess` file handles everything.

### Requirements:
- Apache with `mod_rewrite` enabled
- `.htaccess` in `/BeautyShop/backend/` folder

### Verify Setup:
```bash
# Should work:
curl -X POST https://yourdomain.com/BeautyShop/backend/api/bookings

# Should return JSON bookings:
curl https://yourdomain.com/BeautyShop/backend/api/bookings?limit=10

# Demo UI should load:
https://yourdomain.com/BeautyShop/backend/api/demo
```

## Testing

All three methods work:

1. **Direct Files** (still works)
   ```javascript
   fetch('backend/process_booking.php', { method: 'POST' })
   ```

2. **Clean URLs** (recommended)
   ```javascript
   fetch('backend/api/bookings', { method: 'POST' })
   ```

3. **Demo Tool**
   Visit: `backend/api/demo` to test interactively

## Next Steps

Optional enhancements:
- Add CORS headers if using different domains
- Add request authentication (API keys, tokens)
- Add rate limiting middleware
- Add request logging/analytics
- Implement versioning (`/api/v1/bookings`)

See [API_ROUTING.md](API_ROUTING.md) for complete documentation.
