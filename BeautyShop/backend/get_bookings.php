<?php
/**
 * LUMINÉ BEAUTY SHOP - Get Bookings API
 * Retrieve booking records (for demo/admin purposes)
 */

require_once 'config.php';

header('Content-Type: application/json; charset=utf-8');

// Only allow in sandbox/debug mode
if (!APP_DEBUG) {
    sendJSON(['success' => false, 'message' => 'Not available in production'], 403);
}

try {
    $db = getDB();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Get bookings with limit
    $limit = (int)($_GET['limit'] ?? 10);
    $limit = min($limit, 100); // Max 100
    
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

?>
