<?php
/**
 * LUMINÉ BEAUTY SHOP - M-Pesa Callback Handler
 * Handles payment confirmation from Safaricom
 * This URL is called by M-Pesa when user completes payment
 */

require_once 'config.php';
require_once 'mpesa_api.php';

// Log all callback requests for debugging
file_put_contents(
    __DIR__ . '/logs/mpesa_callbacks.log',
    date('Y-m-d H:i:s') . " - Callback received\n" .
    json_encode($_POST, JSON_PRETTY_PRINT) . "\n" .
    str_repeat("=", 50) . "\n",
    FILE_APPEND
);

// Get raw callback data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    logAction('CALLBACK_ERROR', 'Invalid callback data format');
    http_response_code(400);
    exit;
}

try {
    // Process the callback
    $paymentData = MpesaAPI::processCallback($data);

    if (!$paymentData) {
        logAction('CALLBACK_FAILED', 'Payment failed', $data['Body']['stkCallback'] ?? []);
        http_response_code(200); // Still return 200 to acknowledge receipt
        exit;
    }

    // Get database connection
    $db = getDB();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Extract booking ID from CallbackMetadata (AccountReference was our booking_id)
    $accountRef = $data['Body']['stkCallback']['CallbackMetadata']['Item'][0]['Value'] ?? '';

    // Find booking by account reference
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

    // Update booking status to confirmed
    $stmt = $db->prepare("
        UPDATE bookings 
        SET status = ?, confirmed_at = ?
        WHERE id = ?
    ");

    $status = 'confirmed';
    $confirmed_at = date('Y-m-d H:i:s');
    $booking_id = $booking['id'];

    $stmt->execute([$status, $confirmed_at, $booking_id]);

    // Update payment record
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

    // Send confirmation to customer via WhatsApp (optional)
    // sendWhatsAppConfirmation($booking, $paymentData);

    // Return success response to M-Pesa
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

?>
