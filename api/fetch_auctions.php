<?php
include("php/db.php");

$query = "SELECT *, TIMESTAMPDIFF(SECOND, NOW(), end_time) AS time_left 
          FROM auctions 
          WHERE end_time > NOW() AND status != 'expired' 
          ORDER BY start_time ASC";
$result = $conn->query($query);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "<div class='auction-item'>
                <h3>{$row['title']}</h3>
                <p>{$row['description']}</p>
                <img src='uploads/{$row['image']}' width='150' alt='Auction Image'>
                <p>Starting Price: ₹{$row['start_price']}</p>
                <p>Ends on: {$row['end_time']}</p>
                <p><b>Remaining Time:</b> <span class='timer' data-time='{$row['time_left']}'></span></p>
                <a href='bid.php?auction_id={$row['id']}' class='btn'>Place a Bid</a>
              </div>";
    }
} else {
    echo "<p>No active auctions at the moment.</p>";
}
