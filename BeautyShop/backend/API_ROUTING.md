# BeautyShop Backend API Routing

## Clean URL Endpoints

After the `.htaccess` routing configuration, use these clean URLs instead of direct file calls:

### 1. Create Booking
**Endpoint:** `POST /api/bookings`  
**Function:** Create a new booking and initiate M-Pesa payment  
**Request:** Form data or JSON  
```javascript
fetch('/BeautyShop/backend/api/bookings', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({
        client_name: 'John Doe',
        client_phone: '254712123456',
        client_email: 'john@example.com',
        service: 'makeup',
        preferred_date: '2026-03-15',
        preferred_time: '14:00'
    })
})
```

### 2. Get Bookings
**Endpoint:** `GET /api/bookings`  
**Function:** Retrieve all bookings (demo/admin mode only)  
**Parameters:**
- `limit` (optional): Max bookings to return (default: 10, max: 100)

```javascript
fetch('/BeautyShop/backend/api/bookings?limit=20')
    .then(r => r.json())
    .then(data => console.log(data.bookings))
```

### 3. M-Pesa Callback
**Endpoint:** `POST /api/callback`  
**Function:** Webhook receiver for M-Pesa payment confirmations  
**Called by:** Safaricom M-Pesa API (automatic)  
**Headers:** M-Pesa will send JSON payload in request body  

```php
// This is called automatically by Safaricom
// POST https://yourdomain.com/BeautyShop/backend/api/callback
```

### 4. Demo/Testing
**Endpoint:** `GET /api/demo` (HTML UI)  
**Function:** Interactive testing tool (sandbox mode only)  
**Requires:** `APP_DEBUG=true` and `MPESA_ENVIRONMENT=sandbox`  

```javascript
// Opens demo dashboard at:
// https://yourdomain.com/BeautyShop/backend/api/demo
```

---

## Updated Frontend Integration

Update `script.js` to use these clean URLs:

```javascript
// Old: '/process_booking.php'
// New:
try {
    const response = await fetch('/BeautyShop/backend/api/bookings', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: formData
    });
    const data = await response.json();
    // ... handle response
}
```

---

## Direct File Access (Still Available)

You can still access files directly if needed:
- `POST process_booking.php` → Create booking
- `GET get_bookings.php` → Get bookings
- `POST api/callback` → M-Pesa callback
- `GET demo.php` → Demo UI

But clean API URLs are preferred for:
- Better maintainability
- Standardized endpoint structure
- Easier front-end API management
- Security (easier to add auth middleware)

---

## Security Notes

### Protected Files
The `.htaccess` prevents direct access to:
- `config.php` - Database credentials
- `database.sql` - Schema file

### CORS Headers (if needed)
If accessing from different domain, add to `.htaccess`:
```apache
<IfModule mod_headers.c>
    Header set Access-Control-Allow-Origin "*"
    Header set Access-Control-Allow-Methods "GET, POST, OPTIONS"
</IfModule>
```

### HTTPS Recommendation
Force HTTPS in production:
```apache
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
```

---

## Testing Routing

### Via cURL
```bash
# Create booking
curl -X POST https://yourdomain.com/BeautyShop/backend/api/bookings \
     -d "client_name=Test&client_phone=254712123456&..."

# Get bookings
curl https://yourdomain.com/BeautyShop/backend/api/bookings?limit=5

# Demo UI
curl https://yourdomain.com/BeautyShop/backend/api/demo
```

### Via Browser
- Demo UI: Visit `https://yourdomain.com/BeautyShop/backend/api/demo`
- Test booking POST from form on main site

---

## Troubleshooting

### URLs return 404
1. Check if `mod_rewrite` is enabled: `a2enmod rewrite` (Linux)
2. Verify `.htaccess` is in `/backend/` folder
3. Check `RewriteBase` matches your installation path

### Direct file access still works but clean URLs don't
- Verify Apache config allows `.htaccess` overrides: `AllowOverride All`
- Check file permissions: `chmod 644 .htaccess`

### Forms still posting to old file paths
Update `script.js` to use new endpoints (see above)
