<?php
include("php/db.php");

if (!isset($_GET['auction_id'])) {
    echo json_encode(["error" => "Invalid auction ID"]);
    exit();
}

$auction_id = $_GET['auction_id'];

// Fetch highest bid
$highest_bid_query = "
    SELECT users.username, bids.bid_amount 
    FROM bids 
    JOIN users ON bids.user_id = users.id 
    WHERE auction_id = ? 
    ORDER BY bid_amount DESC 
    LIMIT 1";
$stmt = $conn->prepare($highest_bid_query);
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$highest_bid_result = $stmt->get_result();
$highest_bid_data = $highest_bid_result->fetch_assoc();
$highest_bid = $highest_bid_data['bid_amount'] ?? 0;
$highest_bidder = $highest_bid_data['username'] ?? "No bids yet";

// Fetch bid history
$bid_history_query = "
    SELECT users.username, bids.bid_amount, bids.bid_time 
    FROM bids 
    JOIN users ON bids.user_id = users.id 
    WHERE auction_id = ? 
    ORDER BY bids.bid_time DESC";
$stmt = $conn->prepare($bid_history_query);
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$history_result = $stmt->get_result();

$bid_history = [];
while ($bid = $history_result->fetch_assoc()) {
    $bid_history[] = $bid;
}

// Return data as JSON
echo json_encode([
    "highest_bid" => $highest_bid,
    "highest_bidder" => $highest_bidder,
    "bid_history" => $bid_history
]);
