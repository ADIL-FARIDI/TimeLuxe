<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("php/db.php");

if (!isset($_GET['auction_id'])) {
    header("Location: auctions.php");
    exit();
}

$auction_id = $_GET['auction_id'];
$user_id = $_SESSION['user_id'];

// --- Handle Bid Submission ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $bid_amount = filter_input(INPUT_POST, 'bid_amount', FILTER_VALIDATE_FLOAT);
    $current_highest_bid_query = "SELECT MAX(bid_amount) as max_bid FROM bids WHERE auction_id = ?";
    $stmt = $conn->prepare($current_highest_bid_query);
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $highest_bid = $result['max_bid'] ?? 0;

    // Also need starting price if no bids yet
    if ($highest_bid == 0) {
        $start_price_query = "SELECT start_price FROM auctions WHERE id = ?";
        $stmt = $conn->prepare($start_price_query);
        $stmt->bind_param("i", $auction_id);
        $stmt->execute();
        $highest_bid = $stmt->get_result()->fetch_assoc()['start_price'];
    }

    if ($bid_amount && $bid_amount > $highest_bid) {
        $insert_bid_query = "INSERT INTO bids (user_id, auction_id, bid_amount) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($insert_bid_query);
        $stmt->bind_param("iid", $user_id, $auction_id, $bid_amount);
        if ($stmt->execute()) {
            header("Location: bid.php?auction_id={$auction_id}&feedback=success");
            exit();
        } else {
            header("Location: bid.php?auction_id={$auction_id}&error=dberror");
            exit();
        }
    } else {
        header("Location: bid.php?auction_id={$auction_id}&error=lowbid");
        exit();
    }
}

// --- Fetch All Page Data ---
// Fetch auction details
$auction_query = "SELECT *, TIMESTAMPDIFF(SECOND, NOW(), end_time) AS time_left FROM auctions WHERE id = ?";
$stmt = $conn->prepare($auction_query);
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$auction_result = $stmt->get_result();
$auction = $auction_result->fetch_assoc();

if (!$auction) {
    header("Location: auctions.php");
    exit();
}
$auction_ended = $auction['time_left'] <= 0;

// Fetch highest bid for display
$highest_bid_query = "SELECT u.username, b.bid_amount FROM bids b JOIN users u ON b.user_id = u.id WHERE b.auction_id = ? ORDER BY b.bid_amount DESC LIMIT 1";
$stmt = $conn->prepare($highest_bid_query);
$stmt->bind_param("i", $auction_id);
$stmt->execute();
$highest_bid_data = $stmt->get_result()->fetch_assoc();
$current_bid = $highest_bid_data['bid_amount'] ?? $auction['start_price'];
$highest_bidder = $highest_bid_data['username'] ?? "No bids yet";
$min_next_bid = floor($current_bid) + 100; // Define minimum next bid

// Get user's name for header
$user_query = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($user_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$username = $stmt->get_result()->fetch_assoc()['username'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bidding Salon: <?= htmlspecialchars($auction['title']) ?> | TimeLuxe</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-color: #121212;
            --card-bg: #1A1A1A;
            --primary-gold: #c0a060;
            --text-color: #EAEAEA;
            --text-light: #999;
            --error-red: #B71C1C;
            --success-green: #43A047;
            --font-serif: 'Cormorant Garamond', serif;
            --font-sans: 'Inter', sans-serif;
        }

        body {
            margin: 0;
            font-family: var(--font-sans);
            background-color: var(--bg-color);
            color: var(--text-color);
            background-image: radial-gradient(circle, rgba(18, 18, 18, 0.7) 0%, rgba(18, 18, 18, 1) 80%), url('./assets/web/bg.png');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .gallery-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background-color: rgba(10, 10, 10, 0.7);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #2a2a2a;
            position: sticky;
            top: 0;
            z-index: 1000;
        }

        .header-logo {
            height: 40px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .logout-btn {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            color: var(--text-light);
            padding: 8px 16px;
            border: 1px solid #333;
            border-radius: 20px;
            transition: all 0.3s ease;
        }

        .logout-btn:hover {
            background-color: var(--primary-gold);
            border-color: var(--primary-gold);
            color: var(--bg-color);
        }

        .bidding-salon {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            max-width: 1200px;
            margin: 50px auto;
            padding: 0 40px;
        }

        .watch-showcase,
        .bidding-panel {
            background: rgba(10, 10, 10, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid #2a2a2a;
            border-radius: 8px;
            padding: 40px;
        }

        .watch-image {
            width: 100%;
            max-width: 400px;
            display: block;
            margin: 0 auto;
            filter: drop-shadow(0 15px 15px rgba(0, 0, 0, 0.5));
        }

        .watch-title {
            font-family: var(--font-serif);
            font-size: 2.8rem;
            margin: 20px 0 10px;
            color: var(--text-color);
        }

        .watch-description {
            font-size: 1rem;
            color: var(--text-light);
            line-height: 1.6;
        }

        .bidding-panel h2 {
            font-family: var(--font-serif);
            font-size: 1.8rem;
            color: var(--primary-gold);
            margin-top: 0;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }

        .info-item h3 {
            font-size: 0.9rem;
            color: var(--text-light);
            text-transform: uppercase;
            margin: 0 0 5px;
        }

        .info-item p {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 0;
        }

        .info-item .timer {
            color: var(--primary-gold);
        }

        .bid-form {
            margin-top: 20px;
        }

        .form-input {
            width: 100%;
            padding: 15px;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #333;
            border-radius: 4px;
            color: var(--text-color);
            font-family: var(--font-sans);
            font-size: 1.2rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            margin-bottom: 15px;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-gold);
            box-shadow: 0 0 15px -5px var(--primary-gold);
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 15px 25px;
            font-size: 1rem;
            font-weight: 600;
            text-decoration: none;
            border-radius: 5px;
            transition: all 0.3s ease;
            cursor: pointer;
            border: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            width: 100%;
        }

        .btn-primary {
            background-color: var(--primary-gold);
            color: var(--bg-color);
        }

        .btn-primary:hover {
            background-color: #d4b57a;
        }

        .btn-disabled {
            background-color: #333;
            color: var(--text-light);
            cursor: not-allowed;
        }

        .feedback-message {
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
            font-size: 0.9rem;
        }

        .feedback-success {
            background-color: rgba(67, 160, 71, 0.3);
            border: 1px solid var(--success-green);
        }

        .feedback-error {
            background-color: rgba(183, 28, 28, 0.3);
            border: 1px solid var(--error-red);
        }

        @media (max-width: 900px) {
            .bidding-salon {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <header class="gallery-header">
        <a href="auctions.php"><img src="./assets/logo-no-bg-W.png" alt="TimeLuxe Monogram" class="header-logo"></a>
        <div class="user-info">
            <span>Welcome, <?= htmlspecialchars($username) ?></span>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main class="bidding-salon">
        <div class="watch-showcase">
            <img src="uploads/<?= htmlspecialchars($auction['image']) ?>" alt="<?= htmlspecialchars($auction['title']) ?>" class="watch-image">
            <h1 class="watch-title"><?= htmlspecialchars($auction['title']) ?></h1>
            <p class="watch-description"><?= htmlspecialchars($auction['description']) ?></p>
        </div>
        <div class="bidding-panel">
            <h2>Bidding Details</h2>
            <div class="info-grid">
                <div class="info-item">
                    <h3>Current Bid</h3>
                    <p id="current-bid-amount">₹<?= number_format($current_bid, 2) ?></p>
                </div>
                <div class="info-item">
                    <h3>Next Minimum Bid</h3>
                    <p>₹<?= number_format($min_next_bid, 2) ?></p>
                </div>
                <div class="info-item">
                    <h3>Highest Bidder</h3>
                    <p id="highest-bidder-name"><?= htmlspecialchars($highest_bidder) ?></p>
                </div>
                <div class="info-item">
                    <h3>Time Left</h3>
                    <p class="timer" data-time="<?= $auction['time_left'] ?>"></p>
                </div>
            </div>

            <?php if (isset($_GET['feedback']) && $_GET['feedback'] == 'success'): ?>
                <div class="feedback-message feedback-success">Your bid was successfully placed.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <div class="feedback-message feedback-error">
                    <?php
                    if ($_GET['error'] == 'lowbid') echo 'Your bid must be higher than the current bid.';
                    else echo 'An error occurred while placing your bid.';
                    ?>
                </div>
            <?php endif; ?>

            <?php if (!$auction_ended): ?>
                <form class="bid-form" method="POST">
                    <label for="bid_amount" style="display:none;">Bid Amount</label>
                    <input type="number" id="bid_amount" name="bid_amount" class="form-input" min="<?= $min_next_bid ?>" step="100" placeholder="Enter bid ≥ ₹<?= number_format($min_next_bid) ?>" required>
                    <button type="submit" class="btn btn-primary">Place Your Bid</button>
                </form>
            <?php else: ?>
                <button class="btn btn-disabled" disabled>Auction Has Ended</button>
            <?php endif; ?>
        </div>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const timerElement = document.querySelector('.timer');
            if (timerElement) {
                let secondsLeft = parseInt(timerElement.dataset.time, 10);
                const intervalId = setInterval(() => {
                    if (secondsLeft <= 0) {
                        clearInterval(intervalId);
                        timerElement.textContent = "Auction Ended";
                        document.querySelector('.bid-form')?.remove();
                        if (!document.querySelector('.btn-disabled')) {
                            const panel = document.querySelector('.bidding-panel');
                            const endedButton = document.createElement('button');
                            endedButton.className = 'btn btn-disabled';
                            endedButton.textContent = 'Auction Has Ended';
                            endedButton.disabled = true;
                            panel.appendChild(endedButton);
                        }
                        return;
                    }
                    secondsLeft--;
                    const days = Math.floor(secondsLeft / 86400);
                    const hours = Math.floor((secondsLeft % 86400) / 3600);
                    const minutes = Math.floor((secondsLeft % 3600) / 60);
                    const seconds = secondsLeft % 60;
                    if (days > 0) timerElement.textContent = `${days}d ${hours}h ${minutes}m`;
                    else timerElement.textContent = `${hours}h ${minutes}m ${seconds}s`;
                }, 1000);
            }
        });
    </script>
</body>

</html>