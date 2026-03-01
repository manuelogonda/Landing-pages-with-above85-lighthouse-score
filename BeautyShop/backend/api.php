<?php
/**
 * LUMINÉ BEAUTY SHOP - Central API Router
 * All requests route through this file
 * /api/bookings, /api/callback, /api/demo handled here
 */

require_once 'config.php';
require_once 'mpesa_api.php';

// Get request method and path
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Extract endpoint from path (e.g., /BeautyShop/backend/api/bookings -> bookings)
$parts = array_filter(explode('/', $path));
$endpoint = end($parts) ?? '';

// Handle OPTIONS requests (CORS preflight)
if ($method === 'OPTIONS') {
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(200);
    exit;
}

// Set standard headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');

// ==========================================
// ENDPOINT: /api/bookings
// ==========================================
if ($endpoint === 'bookings') {
    if ($method === 'POST') {
        // Create booking
        handleCreateBooking();
    } elseif ($method === 'GET') {
        // Get all bookings
        handleGetBookings();
    } else {
        sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    exit;
}

// ==========================================
// ENDPOINT: /api/callback
// ==========================================
if ($endpoint === 'callback') {
    if ($method === 'POST') {
        // M-Pesa payment callback
        handleMpesaCallback();
    } else {
        sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    exit;
}

// ==========================================
// ENDPOINT: /api/demo
// ==========================================
if ($endpoint === 'demo') {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';
        if ($action === 'create_demo_booking') {
            handleCreateDemoBooking();
        } else {
            // Return demo dashboard HTML
            handleDemoDashboard();
        }
    } elseif ($method === 'POST') {
        $action = $_GET['action'] ?? '';
        if ($action === 'confirm_payment') {
            handleConfirmDemoPayment();
        } else {
            sendJSON(['success' => false, 'message' => 'Invalid action'], 400);
        }
    } else {
        sendJSON(['success' => false, 'message' => 'Method not allowed'], 405);
    }
    exit;
}

// ==========================================
// UNKNOWN ENDPOINT
// ==========================================
sendJSON([
    'success' => false,
    'message' => 'Endpoint not found',
    'available_endpoints' => [
        'POST /api/bookings' => 'Create booking',
        'GET /api/bookings' => 'Get bookings',
        'POST /api/callback' => 'M-Pesa callback',
        'GET /api/demo' => 'Demo dashboard'
    ]
], 404);

// ==========================================
// HANDLERS
// ==========================================

function handleCreateBooking() {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);

    // Validate input
    if (!$input || empty($input['client_name']) || empty($input['client_phone'])) {
        sendJSON(['success' => false, 'message' => 'Missing required fields'], 400);
    }

    // Sanitize inputs
    $client_name = sanitize($input['client_name']);
    $client_phone = sanitize($input['client_phone']);
    $client_email = sanitize($input['client_email'] ?? '');
    $service = sanitize($input['service'] ?? '');
    $preferred_date = sanitize($input['preferred_date'] ?? '');
    $preferred_time = sanitize($input['preferred_time'] ?? '');
    $special_requests = sanitize($input['special_requests'] ?? '');
    $amount = (int)($input['amount'] ?? 0);
    $mpesa_phone = sanitize($input['mpesa_phone'] ?? '');
    $password = sanitize($input['password'] ?? '');

    // Validate phone format
    if (!validatePhone($client_phone)) {
        sendJSON(['success' => false, 'message' => 'Invalid phone format'], 400);
    }

    // Validate email
    if (!empty($client_email) && !validateEmail($client_email)) {
        sendJSON(['success' => false, 'message' => 'Invalid email format'], 400);
    }

    // Validate date
    if (!empty($preferred_date) && !validateDate($preferred_date)) {
        sendJSON(['success' => false, 'message' => 'Invalid date format'], 400);
    }

    try {
        $db = getDB();
        if (!$db) {
            throw new Exception('Database connection failed');
        }

        $db->beginTransaction();

        // Generate booking ID and hash password
        $booking_id = generateBookingID();
        $password_hash = hashPassword($password);
        $status = 'pending';
        $created_at = date('Y-m-d H:i:s');

        // Map service name
        $service_names = [
            'hair' => 'Hair Styling',
            'makeup' => 'Makeup & Styling',
            'nails' => 'Nail Design',
            'massage' => 'Relaxation Massage',
            'facial' => 'Facial Treatment',
            'waxing' => 'Waxing Service'
        ];
        $service_name = $service_names[$service] ?? ucfirst($service);

        // Insert booking
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

        $booking_db_id = $db->lastInsertId();

        // Initiate M-Pesa STK Push
        try {
            $mpesa = new MpesaAPI();
            $stk_result = $mpesa->initiateStkPush(
                $mpesa_phone,
                $amount,
                $booking_id
            );

            if (!$stk_result['success']) {
                throw new Exception('M-Pesa STK push failed: ' . $stk_result['message']);
            }

            // Record payment initiation
            $stmt = $db->prepare("
                INSERT INTO payments (
                    booking_id, mpesa_phone, amount, status, initiated_at
                ) VALUES (?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $booking_db_id,
                $mpesa_phone,
                $amount,
                'initiated',
                date('Y-m-d H:i:s')
            ]);

            $db->commit();

            logAction('BOOKING_CREATED', 'Booking created with M-Pesa payment initiated', [
                'booking_id' => $booking_id,
                'client' => $client_name,
                'amount' => $amount
            ]);

            sendJSON([
                'success' => true,
                'message' => 'Booking created. Check your phone for M-Pesa prompt.',
                'booking_id' => $booking_id,
                'booking_db_id' => $booking_db_id
            ]);

        } catch (Exception $e) {
            $db->rollBack();
            logAction('BOOKING_ERROR', 'M-Pesa push failed: ' . $e->getMessage());
            sendJSON(['success' => false, 'message' => 'Payment initiation failed: ' . $e->getMessage()], 500);
        }

    } catch (PDOException $e) {
        logAction('DATABASE_ERROR', 'Booking creation failed: ' . $e->getMessage());
        sendJSON(['success' => false, 'message' => 'Database error: ' . $e->getMessage()], 500);

    } catch (Exception $e) {
        logAction('ERROR', 'Booking creation error: ' . $e->getMessage());
        sendJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
    }
}

function handleGetBookings() {
    // Only allow in debug mode
    if (!APP_DEBUG) {
        sendJSON(['success' => false, 'message' => 'Not available in production'], 403);
    }

    try {
        $db = getDB();
        if (!$db) {
            throw new Exception('Database connection failed');
        }

        $limit = (int)($_GET['limit'] ?? 10);
        $limit = min($limit, 100);

        $stmt = $db->prepare("
            SELECT 
                b.id,
                b.booking_id,
                b.client_name,
                b.client_phone,
                b.service_name,
                b.preferred_date,
                b.preferred_time,
                b.amount,
                b.status,
                b.created_at,
                p.mpesa_receipt,
                p.status as payment_status
            FROM bookings b
            LEFT JOIN payments p ON b.id = p.booking_id
            ORDER BY b.created_at DESC
            LIMIT ?
        ");

        $stmt->execute([$limit]);

        $bookings = [];
        while ($row = $stmt->fetch()) {
            $bookings[] = $row;
        }

        sendJSON([
            'success' => true,
            'count' => count($bookings),
            'bookings' => $bookings
        ]);

    } catch (Exception $e) {
        logAction('API_ERROR', 'Failed to get bookings', ['error' => $e->getMessage()]);
        sendJSON(['success' => false, 'message' => $e->getMessage()], 500);
    }
}

function handleMpesaCallback() {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$data) {
        logAction('CALLBACK_ERROR', 'Invalid callback data format');
        http_response_code(200);
        exit;
    }

    try {
        $paymentData = MpesaAPI::processCallback($data);

        if (!$paymentData) {
            logAction('CALLBACK_FAILED', 'Payment failed', $data['Body']['stkCallback'] ?? []);
            http_response_code(200);
            exit;
        }

        $db = getDB();
        if (!$db) {
            throw new Exception('Database connection failed');
        }

        $accountRef = $data['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'] ?? '';

        $stmt = $db->prepare("
            SELECT id, client_name, client_phone, client_email, service_name, 
                   preferred_date, preferred_time, amount
            FROM bookings 
            WHERE booking_id = ? AND status = 'pending'
        ");

        $stmt->execute([$accountRef]);
        $booking = $stmt->fetch();

        if (!$booking) {
            logAction('CALLBACK_NO_BOOKING', "No pending booking found for reference: $accountRef");
            http_response_code(200);
            exit;
        }

        $stmt = $db->prepare("
            UPDATE bookings 
            SET status = ?, confirmed_at = ?
            WHERE id = ?
        ");

        $status = 'confirmed';
        $confirmed_at = date('Y-m-d H:i:s');
        $booking_id = $booking['id'];

        $stmt->execute([$status, $confirmed_at, $booking_id]);

        $stmt = $db->prepare("
            UPDATE payments 
            SET status = ?, mpesa_receipt = ?, transaction_date = ?, 
                confirmed_amount = ?
            WHERE booking_id = ?
        ");

        $payment_status = 'completed';
        $mpesa_receipt = $paymentData['mpesa_receipt'];
        $transaction_date = $paymentData['transaction_date'];
        $confirmed_amount = $paymentData['amount'];

        $stmt->execute([$payment_status, $mpesa_receipt, $transaction_date, $confirmed_amount, $booking_id]);

        logAction('PAYMENT_CONFIRMED', "Payment confirmed for booking", [
            'booking_id' => $accountRef,
            'amount' => $confirmed_amount,
            'receipt' => $mpesa_receipt
        ]);

        http_response_code(200);
        echo json_encode(['ResultCode' => 0]);

    } catch (PDOException $e) {
        logAction('CALLBACK_ERROR', 'Error processing callback: ' . $e->getMessage());
        http_response_code(200);
        echo json_encode(['ResultCode' => 0]);

    } catch (Exception $e) {
        logAction('CALLBACK_ERROR', 'Error processing callback: ' . $e->getMessage());
        http_response_code(200);
        echo json_encode(['ResultCode' => 0]);
    }
}

function handleCreateDemoBooking() {
    if (!APP_DEBUG || MPESA_ENVIRONMENT !== 'sandbox') {
        sendJSON(['success' => false, 'message' => 'Demo mode only in sandbox'], 403);
    }

    try {
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

        sendJSON([
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
        sendJSON(['success' => false, 'message' => 'Failed to create demo booking', 'error' => $e->getMessage()], 500);
    }
}

function handleConfirmDemoPayment() {
    if (!APP_DEBUG || MPESA_ENVIRONMENT !== 'sandbox') {
        sendJSON(['success' => false, 'message' => 'Demo mode only in sandbox'], 403);
    }

    $booking_id = $_POST['booking_id'] ?? '';

    if (empty($booking_id)) {
        sendJSON(['success' => false, 'message' => 'Missing booking_id'], 400);
    }

    try {
        $db = getDB();

        $stmt = $db->prepare("SELECT id, amount FROM bookings WHERE booking_id = ? AND status = 'pending'");
        $stmt->execute([$booking_id]);
        $booking = $stmt->fetch();

        if (!$booking) {
            sendJSON(['success' => false, 'message' => 'Booking not found'], 404);
        }

        $stmt = $db->prepare("
            UPDATE bookings 
            SET status = ?, confirmed_at = ?
            WHERE id = ?
        ");

        $stmt->execute(['confirmed', date('Y-m-d H:i:s'), $booking['id']]);

        $stmt = $db->prepare("
            INSERT INTO payments (booking_id, mpesa_phone, amount, mpesa_receipt, transaction_date, status, confirmed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $booking['id'],
            '254712000003',
            $booking['amount'],
            'DEMO' . date('YmdHis'),
            date('YmdHis'),
            'completed',
            date('Y-m-d H:i:s')
        ]);

        sendJSON([
            'success' => true,
            'message' => 'Payment confirmed (demo)',
            'booking_id' => $booking_id
        ]);

    } catch (PDOException $e) {
        sendJSON(['success' => false, 'message' => 'Failed to confirm payment', 'error' => $e->getMessage()], 500);
    }
}

function handleDemoDashboard() {
    if (!APP_DEBUG || MPESA_ENVIRONMENT !== 'sandbox') {
        http_response_code(403);
        echo 'Demo mode is only available in sandbox debug mode.';
        exit;
    }

    header('Content-Type: text/html; charset=utf-8');
    echo <<<'HTML'
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

    <script>
        function createDemoBooking() {
            fetch('api/demo?action=create_demo_booking')
                .then(r => r.json())
                .then(data => {
                    const result = document.getElementById('create-result');
                    result.style.display = 'block';
                    result.innerHTML = '<span class="success">✓ Success:</span>\n' + JSON.stringify(data, null, 2);
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
                    result.innerHTML = '<span class="error">✗ Error:</span>\nFailed to load bookings.';
                });
        }
    </script>
</body>
</html>
HTML;
    exit;
}
?>
