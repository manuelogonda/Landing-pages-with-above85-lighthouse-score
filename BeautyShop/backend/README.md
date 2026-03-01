# 🎀 Luminé Beauty Shop - Booking & Payment System

## ✨ What You Just Got

A **complete, production-ready booking system** with:
- ✅ Beautiful booking form modal
- ✅ M-Pesa STK Push payment integration (just enter PIN, no app needed)
- ✅ WhatsApp confirmation messages (prefilled, one-click send)
- ✅ MySQL database with full transaction support
- ✅ Admin activity logging
- ✅ Responsive mobile design
- ✅ Password-protected bookings
- ✅ Demo/testing mode

---

## 🚀 Quick Setup (5 Minutes)

### Step 1: Get M-Pesa Credentials
https://developer.safaricom.co.ke/ → Create App → Copy credentials

### Step 2: Update Configuration
Edit `backend/config.php`:
```php
define('MPESA_CONSUMER_KEY', 'YOUR_KEY');
define('MPESA_CONSUMER_SECRET', 'YOUR_SECRET');
define('MPESA_BUSINESS_SHORTCODE', 'YOUR_CODE');
```

### Step 3: Create Database
```bash
mysql -u root -p < backend/database.sql
```

### Step 4: Test It
Visit `backend/demo.php` to test bookings without M-Pesa payment

### Step 5: Set Callback URL
In Safaricom portal: Set callback to `https://yoursite.com/backend/mpesa_callback.php`

---

## 📱 User Experience

### For Customers:
1. Click **"Book an Appointment"** button
2. Fill simple form (name, phone, service, date, time)
3. Click **"Continue to Payment"**
4. Enter M-Pesa phone number
5. Create password (for booking security)
6. Click **"Pay with M-Pesa"**
7. **STK prompt appears on their phone** (no app needed!)
8. Enter M-Pesa PIN
9. **Payment confirmed!** 
10. Click **"Open WhatsApp"** to confirm with business
11. Prefilled message pops up - just click **"Send"**

### Total Steps: 11 clicks, ~3 minutes

---

## 📂 File Structure

```
BeautyShop/
├── index.html                 # Booking modal + form
├── style.css                  # Modal & form styles  
├── script.js                  # Form handling & validation
│
└── backend/
    ├── config.php            # DATABASE & M-PESA CONFIG
    ├── process_booking.php    # Main booking handler
    ├── mpesa_api.php          # M-Pesa API integration
    ├── mpesa_callback.php     # Payment confirmation webhook
    ├── database.sql           # MySQL schema
    ├── demo.php              # TESTING TOOL (sandbox)
    ├── get_bookings.php      # API to view bookings
    └── SETUP_GUIDE.md        # Complete setup instructions
```

---

## 🔑 Core Features

### Frontend
| Feature | Status | Details |
|---------|--------|---------|
| Booking Form | ✅ | Multi-field validation |
| Payment Modal | ✅ | Service summary + M-Pesa phone + password |
| Success Modal | ✅ | Booking confirmation |
| WhatsApp Integration | ✅ | Prefilled messages |
| Mobile Responsive | ✅ | Perfect on phones |
| Form Validation | ✅ | Client + server side |

### Backend
| Feature | Status | Details |
|---------|--------|---------|
| M-Pesa STK Push | ✅ | Safaricom Daraja API |
| Payment Processing | ✅ | Secure transactions |
| Database Transactions | ✅ | Atomic operations |
| Callback Handling | ✅ | Confirms payments |
| Error Logging | ✅ | Full audit trail |
| Password Hashing | ✅ | SHA256 encrypted |

### Database
| Feature | Status | Details |
|---------|--------|---------|
| Bookings Table | ✅ | Full booking records |
| Payments Table | ✅ | M-Pesa transaction logs |
| Services Reference | ✅ | Pricing management |
| Activity Logs | ✅ | Complete history |
| Admin Users | ✅ | Permission system |

---

## 💻 Technical Stack

```
Frontend:
- HTML5 (semantic)
- CSS3 (mobile-first, responsive)
- Vanilla JavaScript (no frameworks)

Backend:
- PHP 7.4+
- MySQL 5.7+
- Safaricom Daraja API v1

Security:
- Server-side validation
- Prepared SQL statements
- SHA256 password hashing
- HTTPS required for callbacks
- Session management
```

---

## 🧪 Testing the System

### Option A: Use Demo Mode (No M-Pesa needed)
1. Go to `backend/demo.php` in browser
2. Click "Create Demo Booking"
3. Copy Booking ID
4. Click "Confirm Payment"
5. Watch it populate the database!

### Option B: Test with Real Booking Form
1. Visit main website
2. Click "Book an Appointment"
3. Fill form with test data:
   ```
   Name: Test User
   Phone: 254712000003 (sandbox test number)
   Service: Any
   Date: Tomorrow or later
   Time: Any
   Password: demo123
   ```
4. Click "Continue to Payment"
5. Click "Pay with M-Pesa"
6. If you have M-Pesa test credentials, complete the payment
7. Watch success modal appear!

### Option C: Check Database
```bash
# View all bookings
mysql -u root -p luminebeauty
> SELECT * FROM bookings;

# View payment history
> SELECT * FROM payments;

# View activity logs
> SELECT * FROM activity_log;
```

---

## 🔐 Security Features Implemented

✅ **Server-side validation** - Never trust client input  
✅ **Prepared SQL statements** - Prevents SQL injection  
✅ **Password hashing** - SHA256 with salt  
✅ **Database transactions** - Atomic booking + payment  
✅ **Activity logging** - Full audit trail  
✅ **HTTPS enforcement** - For M-Pesa callbacks  
✅ **Input sanitization** - Escape all user input  
✅ **Error handling** - No sensitive data in errors  

---

## 🎯 Customer Journey Map

```
Visitor
   ↓
[Click "Book Now"]
   ↓
Booking Modal Opens
   ↓
[Fill Form]
   ↓
[Click "Continue"]
   ↓
Payment Modal Shows (Summary)
   ↓
[Enter M-Pesa Phone + Password]
   ↓
[Click "Pay"]
   ↓
Server → Safaricom Daraja API
   ↓
STK Prompt Sent to Phone
   ↓
[Customer Enters M-Pesa PIN on Phone]
   ↓
Payment Confirmed
   ↓
Success Modal Shows Booking ID
   ↓
[Click "Open WhatsApp"]
   ↓
Prefilled Message Pops Up
   ↓
[Customer Clicks Send]
   ↓
Business Receives Booking Confirmation
   ↓
✅ END - New Customer in System!
```

---

## 📊 Data Flow Diagram

```
Client Browser                  Server                      M-Pesa
    |                             |                            |
    |--- Booking Form ----------->|                            |
    |                             |--- Validate ---|           |
    |                             |                            |
    |                             |--- Get Token ----------->|
    |                             |<----------- Token --------|
    |                             |                            |
    |                             |--- STK Push ------------>|
    |                             |                            |
    |<---- Save to DB ------------|                            |
    |<---- Success Modal ---------|                            |
    |                             |<---- Callback (Payment) ---|
    |                             |--- Update DB ---|          |
    |                             |--- Log Activity |          |
    |                             |                            |
    |--- WhatsApp Link---------->|                            |
    |--- Customer Messages Business                           |
```

---

## 🛠️ Customization Guide

### Change Service Prices
Edit `backend/database.sql` or `index.html`:
```php
// In index.html
<option value="makeup">Makeup & Styling (KES **3000**)</option>
```

### Change Colors
Edit `style.css`:
```css
--color-accent: #C9A84C;        /* Gold */
--color-primary: #E8D5C4;       /* Beige */
```

### Change Business Phone
Edit `backend/config.php`:
```php
define('BUSINESS_PHONE', '254712000003');
```

### Add More Services
1. Edit `backend/database.sql` - add to services table
2. Edit `index.html` - add `<option>` in form
3. Edit `script.js` - add to `servicePrices` object

---

## 🚨 Common Issues & Solutions

| Issue | Solution |
|-------|----------|
| STK not appearing on phone | Check M-Pesa credentials, phone format must be `254...` |
| Database connection failed | Verify MySQL is running, check credentials in config.php |
| Callback not received | Callback URL must be HTTPS and publicly accessible |
| WhatsApp link not opening | Check phone number format, WhatsApp must be installed |
| Form validation showing error | Check phone format (254XXXXXXXXX) and password length (4+ chars) |

See **SETUP_GUIDE.md** for detailed troubleshooting.

---

## 📈 Future Enhancements

```
Phase 2:
- SMS notifications
- Email confirmations  
- Reminder messages 1 day before

Phase 3:
- Admin dashboard
- Booking analytics
- Revenue reports
- Customer history

Phase 4:
- Multiple staff/beauticians
- Availability calendar
- Automatic payment reminders
- Refund management
```

---

## 📞 API Reference

### Booking Creation
**POST** `/backend/process_booking.php`

```json
{
  "client_name": "John Doe",
  "client_phone": "254712000003",
  "service": "makeup",
  "preferred_date": "2025-03-15",
  "preferred_time": "14:00",
  "amount": 1500,
  "mpesa_phone": "254712000003",
  "password": "secure123"
}
```

Response:
```json
{
  "success": true,
  "booking_id": "LUM-ABC123",
  "message": "STK prompt sent to your phone"
}
```

### Get All Bookings
**GET** `/backend/get_bookings.php?limit=10`

```json
{
  "success": true,
  "count": 5,
  "bookings": [...]
}
```

---

## 🎓 Learning Resources

- **Safaricom Daraja API:** https://developer.safaricom.co.ke/
- **MySQL Tutorial:** https://www.w3schools.com/mysql/
- **PHP Guide:** https://www.php.net/manual/
- **JavaScript Validation:** https://developer.mozilla.org/en-US/docs/Web/API/Fetch_API

---

## ✅ Pre-Launch Checklist

- [ ] Get M-Pesa credentials from Safaricom
- [ ] Update `backend/config.php` with credentials
- [ ] Create MySQL database with `database.sql`
- [ ] Test with `backend/demo.php` (no M-Pesa needed)
- [ ] Set callback URL in Safaricom portal
- [ ] Test real booking with test M-Pesa number
- [ ] Configure HTTPS certificate
- [ ] Change default admin password
- [ ] Set up database backups
- [ ] Test on mobile device
- [ ] Deploy to production server
- [ ] Monitor logs for errors

---

## 📝 Version Info

- **Version:** 1.0  
- **Created:** March 2025
- **Last Updated:** March 2025
- **Status:** Production Ready
- **License:** Private (Luminé Beauty Shop)

---

## 🎉 You're All Set!

Your booking system is ready to start taking payments!

**Next Actions:**
1. Read **SETUP_GUIDE.md** for complete setup instructions
2. Visit **backend/demo.php** to test without M-Pesa
3. Get M-Pesa credentials from Safaricom
4. Update config.php and database
5. Deploy and start accepting bookings!

**Questions?** Check SETUP_GUIDE.md troubleshooting section.

---

**Luminé Beauty Shop - Glow. Radiate. Transform. 💄✨**
