# LUMINÉ BEAUTY SHOP - Payment & Booking System Setup Guide

## 📋 Overview

This is a complete booking and payment system with M-Pesa STK Push integration for Safaricom's Daraja API. Users can:
1. Fill in a booking form
2. Complete payment via M-Pesa STK Push (no app needed)
3. Receive WhatsApp confirmation with booking details
4. Skip tedious payment form entrance - just enter password

---

## 🚀 Quick Start (5 Steps)

### 1. **Get M-Pesa Credentials** (15 minutes)
Go to: https://developer.safaricom.co.ke/

- Sign up as a developer
- Create an app in the portal
- Get these credentials:
  - **Consumer Key**
  - **Consumer Secret**
  - **Business Shortcode** (7-digit code)
  - **Passkey** (will be provided or use: `bfb279f9aa9bdbcf158e97dd71a467cd` for sandbox)

### 2. **Update Backend Configuration**

Edit **`backend/config.php`** and replace:

```php
define('MPESA_CONSUMER_KEY', 'YOUR_CONSUMER_KEY_HERE');
define('MPESA_CONSUMER_SECRET', 'YOUR_CONSUMER_SECRET_HERE');
define('MPESA_BUSINESS_SHORTCODE', 'YOUR_SHORTCODE_HERE');
define('MPESA_PASSKEY', 'YOUR_PASSKEY_HERE');
define('MPESA_ENVIRONMENT', 'sandbox'); // 'sandbox' for testing, 'production' for live
define('MPESA_CALLBACK_URL', 'https://yoursite.com/backend/mpesa_callback.php');
```

> **Security Note:** In production, never hardcode credentials. Use environment variables.

### 3. **Setup MySQL Database**

Open your MySQL client and run:

```bash
# Linux/Mac
mysql -u root -p < backend/database.sql

# Or manually:
# 1. Open MySQL Workbench or phpMyAdmin
# 2. Create new database: luminebeauty
# 3. Import the SQL file from backend/database.sql
```

**What it creates:**
- `bookings` table - stores booking details
- `payments` table - tracks M-Pesa transactions
- `services` table - service pricing reference
- `activity_log` table - system audit trail
- `admins` table - admin user management

**Default Admin User:**
- Email: `admin@luminebeauty.ke`
- Password: `Admin@123`

### 4. **Configure PHP Server**

Ensure you have:
- PHP 7.4+ with `curl` extension
- MySQL/MariaDB 5.7+
- HTTPS (required by Safaricom for callbacks)

Test PHP:
```bash
php -r "echo phpversion();"
php -r "echo extension_loaded('curl') ? 'curl: OK' : 'curl: MISSING';"
php -r "echo extension_loaded('mysqli') ? 'mysqli: OK' : 'mysqli: MISSING';"
```

### 5. **Set Callback URL at Safaricom**

In your [Safaricom Developer Portal](https://developer.safaricom.co.ke/):

1. Go to **My Apps**
2. Select your app
3. Under **Test Credentials**:
   - Set **Confirmation URL**: `https://yoursite.com/backend/mpesa_callback.php`
   - Set **Validation URL**: `https://yoursite.com/backend/mpesa_callback.php`
   - Both should be HTTPS and publicly accessible

---

## 📱 How It Works (User Flow)

### 1. **Booking Form**
```
User clicks "Book Now" 
  ↓
Booking modal opens
  ↓
User fills: Name, Phone, Email, Service, Date, Time
  ↓
Clicks "Continue to Payment"
```

### 2. **Payment Modal**
```
Payment form shows service summary
  ↓
User enters M-Pesa phone number (currency format)
  ↓
User creates a password for this booking
  ↓
User checks "I agree to terms"
  ↓
Clicks "Pay with M-Pesa"
```

### 3. **M-Pesa STK Push**
```
Server sends STK to phone (no app needed)
  ↓
User sees payment prompt on phone
  ↓
User enters M-Pesa PIN
  ↓
Payment confirmed
```

### 4. **Success & WhatsApp**
```
Payment confirmed
  ↓
Success modal shows booking ID, service, amount
  ↓
User clicks "Open WhatsApp"
  ↓
Prefilled message pops up with booking details
  ↓
User clicks "Send" (optional)
```

---

## 🔧 File Structure

```
BeautyShop/
├── index.html              # Frontend with booking modal
├── style.css               # Styles for modals & forms
├── script.js               # JavaScript for form handling
│
└── backend/
    ├── config.php          # Database & M-Pesa configuration
    ├── process_booking.php # Main booking handler
    ├── mpesa_api.php       # M-Pesa API wrapper
    ├── mpesa_callback.php  # Webhook receiver from Safaricom
    ├── database.sql        # MySQL schema
    └── logs/               # (auto-created) Activity logs
```

---

## 🔑 Key Features Implemented

### Frontend
✅ **Booking Modal** - Multi-step form with validation  
✅ **Payment Modal** - Service summary + M-Pesa phone + password  
✅ **Success Modal** - Booking confirmation with details  
✅ **WhatsApp Integration** - Prefilled message with booking info  
✅ **Responsive Design** - Works perfectly on mobile (where it matters most)  
✅ **Form Validation** - Client-side + server-side validation  

### Backend
✅ **M-Pesa STK Push** - Initiates payment on user's phone  
✅ **Transaction Logging** - All actions logged for auditing  
✅ **Callback Handling** - Securely processes payment confirmation  
✅ **Database Transactions** - Atomic booking + payment creation  
✅ **Password Protection** - SHA256 hashed passwords  
✅ **Phone Format Validation** - Kenyan format (254...)  

### Database
✅ **Bookings Table** - Full booking records with status tracking  
✅ **Payments Table** - M-Pesa transaction details  
✅ **Services Table** - Pricing reference  
✅ **Activity Logs** - Complete audit trail  
✅ **Relationships** - Foreign keys maintain data integrity  

---

## 🧪 Testing (Sandbox Mode)

### Test M-Pesa Credentials (Safaricom Sandbox)

```
Consumer Key:    3eCzqG5j7dNvFsN3n5v4g7h8k1m3p5q7
Consumer Secret: YOUR_SECRET
Shortcode:       174379
Passkey:         bfb279f9aa9bdbcf158e97dd71a467cd
```

### Test Phone Numbers (Sandbox)

```
254708374149 - Valid formatted account
254700000000 - Will fail in production
```

### Test Amounts

```
Less than 10 KES - Will be rejected
10-150,000 KES - Will work in sandbox
Over 150,000 KES - May be rejected by M-Pesa
```

### Sandbox Testing Checklist

1. **Test Valid Payment:**
   ```
   Phone: 254708374149
   Amount: 1500
   Expected: STK prompt, then success
   ```

2. **Test Invalid Phone:**
   ```
   Phone: 123
   Expected: Validation error
   ```

3. **Test Missing Fields:**
   ```
   Leave phone blank
   Expected: Required field error
   ```

4. **Check Logs:**
   ```
   tail -f backend/logs/activity.log
   tail -f backend/logs/mpesa_callbacks.log
   ```

---

## 🛠️ Admin Panel (Optional Enhancement)

Create a simple admin dashboard to view bookings:

**File:** `backend/admin.php`

Features to add:
- View all bookings with filtering
- Search by phone/name
- Export to CSV
- Mark completed/cancelled
- View payment history
- Revenue reports

---

## 🔐 Security Best Practices

### 1. **Environment Variables (Production)**

Instead of hardcoding credentials in `config.php`:

```php
// Use this in production:
define('MPESA_CONSUMER_KEY', getenv('MPESA_CONSUMER_KEY'));
define('MPESA_CONSUMER_SECRET', getenv('MPESA_CONSUMER_SECRET'));

// In .env file:
MPESA_CONSUMER_KEY=your_key_here
MPESA_CONSUMER_SECRET=your_secret_here
```

### 2. **HTTPS Required**

All M-Pesa callbacks MUST be served over HTTPS. Set up SSL:

```bash
# Using Let's Encrypt (free)
sudo apt install certbot python3-certbot-apache
sudo certbot certonly --apache -d yoursite.com
```

### 3. **Rate Limiting**

Prevent brute force attacks:

```php
// Add to process_booking.php:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = $_SERVER['REMOTE_ADDR'];
    // Implement rate limiting (max 5 requests per minute per IP)
}
```

### 4. **Input Validation**

All inputs are sanitized using `mysqli::real_escape_string`. Consider adding:
- CSRF tokens on forms
- SQL prepared statements (already implemented)
- IP whitelisting for admin


### 5. **Database Backups**

```bash
# Daily backup
mysqldump -u root -p luminebeauty > backup_$(date +%Y%m%d).sql

# Automated backup (cron)
0 2 * * * mysqldump -u root -p luminebeauty > /backup/luminebeauty_$(date +\%Y\%m\%d).sql
```

---

## 🚨 Troubleshooting

### Problem: "Database connection failed"
**Solution:**
```php
// Check database credentials in config.php
// Verify MySQL service is running:
systemctl status mysql
// Create database if missing:
mysql -u root -p < backend/database.sql
```

### Problem: "STK push not received"
**Solution:**
- Check `MPESA_CONSUMER_KEY` and `MPESA_CONSUMER_SECRET` are correct
- Verify `MPESA_BUSINESS_SHORTCODE` is correct (7 digits)
- Ensure you're using Kenyan phone format: `254XXXXXXXXX`
- Check logs: `backend/logs/activity.log`
- Test with provided test accounts in sandbox

### Problem: "Callback not being received"
**Solution:**
- Callback URL must be HTTPS (not HTTP)
- URL must be publicly accessible (test with: `curl https://yoursite.com/backend/mpesa_callback.php`)
- Check firewall/router isn't blocking incoming requests
- Verify callback URL in Safaricom Developer Portal matches exactly

### Problem: "Payment completed but booking not confirmed"
**Solution:**
- Check MySQL database has correct tables (run `database.sql`)
- Check database credentials in `config.php`
- Look in `backend/logs/mpesa_callbacks.log` for callback details
- Verify `mpesa_callback.php` returned status 200

### Problem: "WhatsApp link not opening"
**Solution:**
- Ensure phone number is valid Kenyan format: `254XXXXXXXXX`
- Test link directly in browser: `https://wa.me/254712000003`
- Check WhatsApp is installed on device
- Some browsers block `target="_blank"` - use `window.open()`

---

## 📊 Key Database Queries

### View All Pending Bookings
```sql
SELECT * FROM bookings WHERE status = 'pending' ORDER BY created_at DESC;
```

### View Today's Confirmed Bookings
```sql
SELECT * FROM bookings WHERE preferred_date = CURDATE() AND status = 'confirmed' ORDER BY preferred_time ASC;
```

### Calculate Daily Revenue
```sql
SELECT preferred_date, SUM(amount) as daily_revenue FROM bookings WHERE status = 'confirmed' GROUP BY preferred_date;
```

### View Failed Payments
```sql
SELECT b.booking_id, b.client_name, p.amount, p.status 
FROM bookings b JOIN payments p ON b.id = p.booking_id 
WHERE p.status IN ('failed', 'reversed') 
ORDER BY p.created_at DESC;
```

### Export Bookings to CSV
```sql
SELECT * FROM bookings WHERE preferred_date BETWEEN '2025-03-01' AND '2025-03-31' 
INTO OUTFILE '/tmp/bookings.csv' 
FIELDS TERMINATED BY ',' ENCLOSED BY '"' 
LINES TERMINATED BY '\n';
```

---

## 🎯 Next Steps

1. ✅ **Get M-Pesa credentials** from Safaricom
2. ✅ **Update `backend/config.php`** with credentials
3. ✅ **Create MySQL database** using `backend/database.sql`
4. ✅ **Set callback URL** in Safaricom portal
5. ✅ **Test in sandbox mode** with test accounts
6. ✅ **Move to production** after testing
7. ✅ (Optional) **Build admin dashboard** for managing bookings
8. ✅ (Optional) **Add SMS/Email notifications** for confirmations

---

## 📞 Support Resources

- **Safaricom Daraja API Docs:** https://developer.safaricom.co.ke/
- **M-Pesa API Status:** https://developer.safaricom.co.ke/status
- **PHP cURL Documentation:** https://www.php.net/manual/en/book.curl.php
- **MySQL Documentation:** https://dev.mysql.com/doc/
- **WhatsApp API:** https://www.whatsapp.com/business/api

---

## 📝 Version History

- **v1.0** (March 2025) - Initial release with M-Pesa STK Push integration
  - Booking form with service selection
  - M-Pesa STK Push payment processing
  - WhatsApp confirmation messages
  - Complete database schema
  - Callback webhook handling

---

**Created:** March 2025  
**System:** Luminé Beauty Shop Booking & Payment Platform  
**Technology:** PHP 7.4+, MySQL 5.7+, Vanilla JavaScript, Safaricom Daraja API
