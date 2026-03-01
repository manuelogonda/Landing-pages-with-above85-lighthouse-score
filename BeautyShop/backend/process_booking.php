<?php
/**
 * LUMINÉ BEAUTY SHOP - Booking & M-Pesa Payment Processing
 * Handles STK Push for M-Pesa payments
 */

header('Content-Type: application/json; charset=utf-8');
require_once 'config.php';
require_once 'mpesa_api.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendJSON(['success' => false, 'message' => 'Invalid request method'], 405);
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    sendJSON(['success' => false, 'message' => 'Invalid JSON input'], 400);
}

// Validate required fields
$required = ['client_name', 'client_phone', 'service', 'preferred_date', 'preferred_time', 'amount', 'mpesa_phone', 'password'];
foreach ($required as $field) {
    if (empty($input[$field])) {
        sendJSON(['success' => false, 'message' => "Missing required field: $field"], 400);
    }
}

// Sanitize inputs
$client_name = sanitize($input['client_name']);
$client_phone = sanitize($input['client_phone']);
$client_email = sanitize($input['client_email'] ?? '');
$service = sanitize($input['service']);
$service_name = sanitize($input['service_name'] ?? '');
$preferred_date = sanitize($input['preferred_date']);
$preferred_time = sanitize($input['preferred_time']);
$special_requests = sanitize($input['special_requests'] ?? '');
$amount = (int)$input['amount'];
$mpesa_phone = sanitize($input['mpesa_phone']);
$password = $input['password'];

// Validation
$errors = [];

// Validate phone numbers
if (!validatePhone($client_phone)) {
    $errors[] = 'Invalid client phone number (use format 254...)';
}
if (!validatePhone($mpesa_phone)) {
    $errors[] = 'Invalid M-Pesa phone number (use format 254...)';
}

// Validate email if provided
if (!empty($client_email) && !validateEmail($client_email)) {
    $errors[] = 'Invalid email address';
}

// Validate date
if (!validateDate($preferred_date)) {
    $errors[] = 'Invalid date format';
}

// Check date is not in the past
$booking_date = strtotime($preferred_date);
$today = strtotime('today');
if ($booking_date < $today) {
    $errors[] = 'Booking date cannot be in the past';
}

// Validate amount
if ($amount < 100 || $amount > 999999) {
    $errors[] = 'Invalid amount';
}

// Validate password
if (strlen($password) < 4) {
    $errors[] = 'Password must be at least 4 characters';
}

// Return validation errors
if (!empty($errors)) {
    sendJSON(['success' => false, 'message' => implode(', ', $errors)], 400);
}

try {
    // Get database connection
    $db = getDB();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Start transaction
    $db->beginTransaction();

    // Generate booking ID
    $booking_id = generateBookingID();
    $password_hash = hashPassword($password);
    $created_at = date('Y-m-d H:i:s');
    $status = 'pending';

    // Insert booking into database
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

    logAction('BOOKING_CREATED', "Booking $booking_id created", [
        'client' => $client_name,
        'phone' => $client_phone,
        'service' => $service,
        'amount' => $amount
    ]);

    // ==========================================
    // INITIATE M-PESA STK PUSH
    // ==========================================
    $mpesa = new MpesaAPI();
    $stkPushResult = $mpesa->initiateStkPush(
        $mpesa_phone,
        $amount,
        $booking_id,
        "Beauty Service - $service_name"
    );

    if (!$stkPushResult || !isset($stkPushResult['ResponseCode']) || $stkPushResult['ResponseCode'] !== '0') {
        // STK push failed, but booking is created
        // In production, you might want to rollback and delete the booking
        logAction('STK_PUSH_FAILED', "STK push failed for booking $booking_id", $stkPushResult);
        
        $db->rollBack();
        sendJSON([
            'success' => false,
            'message' => 'Failed to initiate payment. Please try again.',
            'error' => $stkPushResult['ResponseDescription'] ?? 'Unknown error'
        ], 500);
    }

    // Store M-Pesa request details
    $request_id = $stkPushResult['RequestID'] ?? '';
    $stmt = $db->prepare("
        INSERT INTO payments (booking_id, mpesa_phone, amount, request_id, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $payment_status = 'pending';
    $stmt->execute([
        $booking_db_id, $mpesa_phone, $amount, $request_id, $payment_status, $created_at
    ]);

    // Commit transaction
    $db->commit();

    logAction('STK_PUSH_INITIATED', "STK push initiated for booking $booking_id", [
        'phone' => $mpesa_phone,
        'amount' => $amount,
        'request_id' => $request_id
    ]);

    // Return success response
    sendJSON([
        'success' => true,
        'message' => 'STK prompt sent to your phone. Enter your M-Pesa PIN to complete payment.',
        'booking_id' => $booking_id,
        'amount' => $amount,
        'service' => $service_name,
        'phone' => $mpesa_phone
    ], 200);

} catch (PDOException $e) {
    // Rollback transaction on error
    if (isset($db) && $db) {
        try {
            $db->rollBack();
        } catch (Exception $rollbackException) {
            // Ignore if rollback fails
        }
    }

    logAction('BOOKING_ERROR', 'Booking processing failed', ['error' => $e->getMessage()]);

    sendJSON([
        'success' => false,
        'message' => APP_DEBUG ? $e->getMessage() : 'An error occurred while processing your booking.'
    ], 500);

} catch (Exception $e) {
    // Rollback transaction on error
    if (isset($db) && $db) {
        try {
            $db->rollBack();
        } catch (Exception $rollbackException) {
            // Ignore if rollback fails
        }
    }

    logAction('BOOKING_ERROR', 'Booking processing failed', ['error' => $e->getMessage()]);

    sendJSON([
        'success' => false,
        'message' => APP_DEBUG ? $e->getMessage() : 'An error occurred while processing your booking.'
    ], 500);
}

?>
