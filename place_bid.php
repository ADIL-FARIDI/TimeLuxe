<?php
session_start();
header('Content-Type: application/json');

// DESIGNER'S NOTE: This is a simplified AJAX endpoint.
// A production-ready version requires SIGNIFICANT security hardening.

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Authentication required. Please log in again.']);
    exit();
}

// Corrected Path to DB connection
include("php/db.php");

$auction_id = filter_input(INPUT_POST, 'auction_id', FILTER_VALIDATE_INT);
$bid_amount = filter_input(INPUT_POST, 'bid_amount', FILTER_VALIDATE_FLOAT);
$user_id = $_SESSION['user_id'];

if (!$auction_id || !$bid_amount) {
    echo json_encode(['success' => false, 'error' => 'Invalid data provided.']);
    exit();
}

// --- CRITICAL SECURITY NOTE ---
// This transaction lock is essential to prevent two users from bidding simultaneously
// and causing a data race condition.
mysqli_begin_transaction($conn);

try {
    // Lock the auction row to ensure data integrity during the transaction
    $auction_check_query = "SELECT end_time FROM auctions WHERE id = ? FOR UPDATE";
    $stmt = $conn->prepare($auction_check_query);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $auction_time = $stmt->get_result()->fetch_assoc();

    if (!$auction_time || strtotime($auction_time['end_time']) < time()) {
        throw new Exception('This auction has ended.');
    }

    $highest_bid_query = "SELECT MAX(bid_amount) as max_bid FROM bids WHERE auction_id = ?";
    $stmt = $conn->prepare($highest_bid_query);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $highest_bid = $stmt->get_result()->fetch_assoc()['max_bid'];

    if ($highest_bid === null) {
        $start_price_query = "SELECT start_price FROM auctions WHERE id = ?";
        $stmt = $conn->prepare($start_price_query);
        $stmt->bind_param("i", $auction_id);
        $stmt->execute();
        $highest_bid = $stmt->get_result()->fetch_assoc()['start_price'];
    }

    if ($bid_amount <= $highest_bid) {
        throw new Exception('Your bid must be higher than the current highest bid.');
    }

    $insert_bid_query = "INSERT INTO bids (user_id, auction_id, bid_amount) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($insert_bid_query);
    $stmt->bind_param("iid", $user_id, $auction_id, $bid_amount);

    if (!$stmt->execute()) {
        throw new Exception('Database error. Could not place your bid.');
    }

    mysqli_commit($conn);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

$stmt->close();
$conn->close();
