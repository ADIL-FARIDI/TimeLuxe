<?php
header('Content-Type: application/json');
// Corrected Path to DB connection
include("php/db.php");

$auction_id = filter_input(INPUT_GET, 'auction_id', FILTER_VALIDATE_INT);

if (!$auction_id) {
    echo json_encode(['error' => 'Invalid auction ID.']);
    exit();
}

// Fetch highest bid details
$highest_bid_query = "SELECT u.username, b.bid_amount 
                      FROM bids b 
                      JOIN users u ON b.user_id = u.id 
                      WHERE b.auction_id = ? 
                      ORDER BY b.bid_amount DESC 
                      LIMIT 1";
$stmt = $conn->prepare($highest_bid_query);
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$highest_bid_data = $stmt->get_result()->fetch_assoc();

if ($highest_bid_data) {
    $highest_bid = $highest_bid_data['bid_amount'];
    $highest_bidder = $highest_bid_data['username'];
} else {
    // If no bids, get the starting price
    $start_price_query = "SELECT start_price FROM auctions WHERE id = ?";
    $stmt = $conn->prepare($start_price_query);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $auction_data = $stmt->get_result()->fetch_assoc();

    $highest_bid = $auction_data['start_price'] ?? 0;
    $highest_bidder = 'No bids yet';
}

echo json_encode([
    'highest_bid' => $highest_bid,
    'highest_bidder' => $highest_bidder
]);

$stmt->close();
$conn->close();
