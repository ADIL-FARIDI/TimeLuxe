<?php
include("php/db.php");

function markExpiredAuctions($conn)
{
    $query = "UPDATE auctions SET status='expired' WHERE end_time <= NOW()";
    if ($conn->query($query)) {
        echo "<script>console.log('Expired auctions marked successfully.');</script>";
    } else {
        echo "<script>console.log('Error marking expired auctions: " . addslashes($conn->error) . "');</script>";
    }
}

// Call the function without closing the connection
markExpiredAuctions($conn);
