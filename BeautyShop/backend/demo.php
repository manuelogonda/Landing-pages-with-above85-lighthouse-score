<?php
/**
 * LUMINÉ BEAUTY SHOP - Demo Mode Tester
 * For testing booking form without actual M-Pesa payment
 * Remove this file in production!
 */

require_once 'config.php';

// Only allow in debug/sandbox mode
if (!APP_DEBUG || MPESA_ENVIRONMENT !== 'sandbox') {
    die('Demo mode is only available in sandbox debug mode.');
}

// Check for action parameter
$action = $_GET['action'] ?? '';

// ==========================================
// DEMO: Create Booking
// ==========================================
if ($action === 'create_demo_booking') {
    $booking_id = generateBookingID();
    $password = 'demo123';
    $password_hash = hashPassword($password);
    
    $db = getDB();
    
    $client_name = 'Test User';
    $client_phone = '254712000003';
    $client_email = 'test@example.com';
    $service = 'makeup';
    $service_name = 'Makeup & Styling';
    $preferred_date = date('Y-m-d', strtotime('+1 day'));
    $preferred_time = '14:00';
    $special_requests = '';
    $amount = 1500;
    $status = 'pending';
    $created_at = date('Y-m-d H:i:s');
    
    try {
        $stmt = $db->prepare("
            INSERT INTO bookings (
                booking_id, client_name, client_phone, client_email,
                service, service_name, preferred_date, preferred_time,
                special_requests, amount, status, password_hash, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $booking_id, $client_name, $client_phone, $client_email,
            $service, $service_name, $preferred_date, $preferred_time,
            $special_requests, $amount, $status, $password_hash, $created_at
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Demo booking created',
            'booking_id' => $booking_id,
            'password' => $password,
            'data' => [
                'client_name' => $client_name,
                'client_phone' => $client_phone,
                'service' => $service_name,
                'preferred_date' => $preferred_date,
                'preferred_time' => $preferred_time,
                'amount' => $amount
            ]
        ]);
    } catch (PDOException $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create demo booking',
            'error' => $e->getMessage()
        ]);
    }
    exit;
}

// ==========================================
// DEMO: Confirm Payment
// ==========================================
if ($action === 'confirm_payment') {
    $booking_id = $_POST['booking_id'] ?? '';
    
    if (empty($booking_id)) {
        sendJSON(['success' => false, 'message' => 'Missing booking_id'], 400);
    }
    
    $db = getDB();
    
    try {
        // Find booking
        $stmt = $db->prepare("SELECT id, amount FROM bookings WHERE booking_id = ? AND status = 'pending'");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();
        
        if (!$booking) {
            sendJSON(['success' => false, 'message' => 'Booking not found'], 404);
        }
        
        // Update booking status
        $status = 'confirmed';
        $confirmed_at = date('Y-m-d H:i:s');
        $booking_db_id = $booking['id'];
        
        $stmt = $db->prepare("
            UPDATE bookings 
            SET status = ?, confirmed_at = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$status, $confirmed_at, $booking_db_id]);
        
        // Create demo payment record
        $amount = $booking['amount'];
        $mpesa_phone = '254712000003';
        $mpesa_receipt = 'DEMO' . date('YmdHis');
        $payment_status = 'completed';
        $transaction_date = date('YmdHis');
        
        $stmt = $db->prepare("
            INSERT INTO payments (booking_id, mpesa_phone, amount, mpesa_receipt, transaction_date, status, confirmed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([$booking_db_id, $mpesa_phone, $amount, $mpesa_receipt, $transaction_date, $payment_status, $confirmed_at]);
        
        sendJSON([
            'success' => true,
            'message' => 'Payment confirmed (demo)',
            'booking_id' => $booking_id
        ]);
        
    } catch (PDOException $e) {
        sendJSON(['success' => false, 'message' => 'Failed to confirm payment', 'error' => $e->getMessage()], 500);
    }
}

// ==========================================
// DEMO: View Dashboard
// ==========================================
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Luminé Beauty Shop - Demo Tester</title>
    <style>
        body {
            font-family: -apple-system, system-ui, sans-serif;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #333; }
        .card {
            background: white;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        button {
            background: #C9A84C;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 14px;
            margin: 5px 5px 5px 0;
        }
        button:hover { background: #b39340; }
        .result {
            background: #f0f0f0;
            padding: 10px;
            border-radius: 4px;
            margin-top: 10px;
            font-family: monospace;
            white-space: pre-wrap;
            max-height: 200px;
            overflow-y: auto;
        }
        .success { color: green; }
        .error { color: red; }
        input {
            width: 100%;
            padding: 8px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
    </style>
</head>
<body>
    <h1>🧪 Luminé Beauty Shop - Demo Tester</h1>
    <p style="color: #666;">Test booking system without actual M-Pesa payment</p>
    
    <div class="card">
        <h2>1. Create Demo Booking</h2>
        <p>Creates a test booking with predefined data</p>
        <button onclick="createDemoBooking()">Create Demo Booking</button>
        <div id="create-result" class="result" style="display: none;"></div>
    </div>
    
    <div class="card">
        <h2>2. Confirm Payment</h2>
        <p>Simulates M-Pesa payment confirmation</p>
        <input type="text" id="booking-id-input" placeholder="Enter Booking ID (from step 1)">
        <button onclick="confirmPayment()">Confirm Payment</button>
        <div id="confirm-result" class="result" style="display: none;"></div>
    </div>
    
    <div class="card">
        <h2>3. View Bookings</h2>
        <button onclick="viewBookings()">Show All Bookings</button>
        <div id="bookings-result" class="result" style="display: none;"></div>
    </div>

    <div class="card">
        <h2>4. Booking Form Test</h2>
        <p><a href="/" target="_blank">Open main website</a> and test the booking form:</p>
        <ol>
            <li>Click "Book an Appointment"</li>
            <li>Fill in the form</li>
            <li>Proceed to payment</li>
            <li>Use M-Pesa phone: <strong>254712000003</strong></li>
            <li>Set password to: <strong>demo123</strong></li>
            <li>Click "Pay with M-Pesa"</li>
        </ol>
        <p><em>In sandbox mode, you'll need actual M-Pesa test credentials to complete payment.</em></p>
    </div>

    <script>
        function createDemoBooking() {
            fetch('api/demo?action=create_demo_booking')
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('create-result');
                    result.style.display = 'block';
                    result.innerHTML = '<span class="success">✓ Success:</span>\n' + JSON.stringify(data, null, 2);
                    
                    // Auto-fill booking ID for next step
                    if (data.booking_id) {
                        document.getElementById('booking-id-input').value = data.booking_id;
                    }
                })
                .catch(e => {
                    const result = document.getElementById('create-result');
                    result.style.display = 'block';
                    result.innerHTML = '<span class="error">✗ Error:</span>\n' + e.message;
                });
        }

        function confirmPayment() {
            const bookingId = document.getElementById('booking-id-input').value;
            if (!bookingId) {
                alert('Please enter a booking ID');
                return;
            }

            const formData = new FormData();
            formData.append('booking_id', bookingId);

            fetch('api/demo?action=confirm_payment', {
                method: 'POST',
                body: formData
            })
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('confirm-result');
                    result.style.display = 'block';
                    if (data.success) {
                        result.innerHTML = '<span class="success">✓ Payment Confirmed:</span>\n' + JSON.stringify(data, null, 2);
                    } else {
                        result.innerHTML = '<span class="error">✗ Error:</span>\n' + JSON.stringify(data, null, 2);
                    }
                })
                .catch(e => {
                    const result = document.getElementById('confirm-result');
                    result.style.display = 'block';
                    result.innerHTML = '<span class="error">✗ Error:</span>\n' + e.message;
                });
        }

        function viewBookings() {
            fetch('api/bookings')
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('bookings-result');
                    result.style.display = 'block';
                    result.innerHTML = '<span class="success">✓ Bookings:</span>\n' + JSON.stringify(data, null, 2);
                })
                .catch(e => {
                    const result = document.getElementById('bookings-result');
                    result.style.display = 'block';
                    result.innerHTML = '<span class="error">✗ Error:</span>\nFailed to load bookings. Make sure API routing is configured.';
                });
        }
    </script>
</body>
</html>
