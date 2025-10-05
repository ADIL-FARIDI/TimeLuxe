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

// --- Fetch All Initial Page Data ---
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
            --bg-color: #0e0e0e;
            --card-bg: #1A1A1A;
            --primary-gold: #c0a060;
            --text-color: #EAEAEA;
            --text-light: #999;
            --error-red: #E53935;
            --success-green: #43A047;
            --font-serif: 'Cormorant Garamond', serif;
            --font-sans: 'Inter', sans-serif;
            --border-dark: #2a2a2a;
        }

        body {
            margin: 0;
            font-family: var(--font-sans);
            background-color: var(--bg-color);
            color: var(--text-color);
            background-image: radial-gradient(circle, rgba(18, 18, 18, 0.8) 0%, rgba(18, 18, 18, 1) 75%), url('https://images.unsplash.com/photo-1610603114859-c7003b743758?ixlib=rb-4.0.3&ixid=MnwxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8&auto=format&fit=crop&w=1770&q=80');
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
            border-bottom: 1px solid var(--border-dark);
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
            grid-template-columns: 1.2fr 1fr;
            gap: 50px;
            max-width: 1400px;
            margin: 50px auto;
            padding: 0 40px;
        }

        .watch-showcase,
        .bidding-panel {
            background: rgba(10, 10, 10, 0.6);
            backdrop-filter: blur(10px);
            border: 1px solid var(--border-dark);
            border-radius: 8px;
            padding: 40px;
            animation: fadeIn 0.8s ease-out;
        }

        .watch-image {
            width: 100%;
            max-width: 450px;
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
            gap: 25px;
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
            position: relative;
        }

        .info-item .timer {
            color: var(--primary-gold);
            font-size: 2rem;
        }

        #current-bid-container {
            position: relative;
            min-height: 30px;
        }

        #current-bid-amount {
            transition: color 0.3s ease;
        }

        .form-container {
            display: flex;
            flex-direction: column;
            gap: 15px;
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
            box-sizing: border-box;
        }

        .btn-primary {
            background-color: var(--primary-gold);
            color: var(--bg-color);
        }

        .btn-primary:hover {
            background-color: #d4b57a;
            box-shadow: 0 0 15px rgba(192, 160, 96, 0.6);
            transform: translateY(-2px);
        }

        .btn-secondary {
            background-color: transparent;
            color: var(--text-light);
            border: 1px solid #444;
        }

        .btn-secondary:hover {
            background-color: #222;
            color: white;
            transform: translateY(-2px);
        }

        .btn-disabled {
            background-color: #333;
            color: var(--text-light);
            cursor: not-allowed;
        }

        #feedback-container {
            min-height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .feedback-message {
            padding: 12px;
            border-radius: 4px;
            font-size: 0.9rem;
            text-align: center;
            width: 100%;
            animation: fadeIn 0.3s ease;
        }

        .feedback-success {
            background-color: rgba(67, 160, 71, 0.3);
            border: 1px solid var(--success-green);
            color: var(--success-green);
        }

        .feedback-error {
            background-color: rgba(183, 28, 28, 0.3);
            border: 1px solid var(--error-red);
            color: var(--error-red);
        }

        @media (max-width: 900px) {
            .bidding-salon {
                grid-template-columns: 1fr;
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
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
                    <div id="current-bid-container">
                        <p id="current-bid-amount">₹--</p>
                    </div>
                </div>
                <div class="info-item">
                    <h3>Next Minimum Bid</h3>
                    <p id="min-next-bid">₹--</p>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                    <h3>Highest Bidder</h3>
                    <p id="highest-bidder-name">--</p>
                </div>
                <div class="info-item" style="grid-column: span 2;">
                    <h3>Time Left</h3>
                    <p class="timer" data-time="<?= $auction['time_left'] ?>"></p>
                </div>
            </div>

            <div id="feedback-container"></div>

            <div id="form-container" class="form-container">
                <form class="bid-form" id="bid-form" style="margin:0;">
                    <label for="bid_amount" style="display:none;">Bid Amount</label>
                    <input type="number" id="bid_amount" name="bid_amount" class="form-input" style="margin-bottom:0;" step="100" required>
                    <input type="hidden" name="auction_id" value="<?= $auction_id ?>">
                    <br>
                    <br>
                    <button type="submit" class="btn btn-primary">Place Your Bid</button>
                </form>
                <a href="auctions.php" class="btn btn-secondary">Back to Gallery</a>
            </div>
        </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.10.4/gsap.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const auctionId = <?= $auction_id ?>;
            const bidForm = document.getElementById('bid-form');
            const feedbackContainer = document.getElementById('feedback-container');

            const bidContainer = document.getElementById('current-bid-container');
            let currentBidEl = document.getElementById('current-bid-amount');
            const minNextBidEl = document.getElementById('min-next-bid');
            const highestBidderEl = document.getElementById('highest-bidder-name');
            const bidAmountInput = document.getElementById('bid_amount');
            const timerElement = document.querySelector('.timer');
            let pollingInterval;
            let lastKnownBid = -1;

            function playHeartbeatAnimation(newBidString) {
                const tl = gsap.timeline();
                const newBidEl = document.createElement('p');
                newBidEl.id = 'current-bid-amount';
                newBidEl.textContent = newBidString;
                newBidEl.style.position = 'absolute';
                newBidEl.style.top = '0';
                newBidEl.style.left = '0';
                newBidEl.style.width = '100%';
                newBidEl.style.textAlign = 'left';

                tl.to(currentBidEl, {
                        duration: 0.2,
                        scale: 0.8,
                        opacity: 0,
                        ease: 'power2.in'
                    })
                    .add(() => {
                        currentBidEl.remove();
                        bidContainer.appendChild(newBidEl);
                        currentBidEl = newBidEl;
                    })
                    .from(newBidEl, {
                        duration: 0.4,
                        scale: 1.2,
                        opacity: 0,
                        color: 'var(--primary-gold)',
                        textShadow: '0 0 15px var(--primary-gold)',
                        ease: 'power2.out'
                    })
                    .to(newBidEl, {
                        duration: 0.5,
                        color: 'var(--text-color)',
                        textShadow: '0 0 0px rgba(0,0,0,0)',
                        ease: 'sine.out'
                    });
            }

            function updateUI(data) {
                const newBid = parseFloat(data.highest_bid);

                if (lastKnownBid !== -1 && newBid > lastKnownBid) {
                    playHeartbeatAnimation(`₹${newBid.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`);
                } else {
                    currentBidEl.textContent = `₹${newBid.toLocaleString('en-IN', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;
                }
                lastKnownBid = newBid;

                const minNextBid = Math.floor(newBid) + 100;
                minNextBidEl.textContent = `₹${minNextBid.toLocaleString('en-IN')}`;
                highestBidderEl.textContent = data.highest_bidder;

                if (bidAmountInput) {
                    bidAmountInput.min = minNextBid;
                    bidAmountInput.placeholder = `Enter bid ≥ ₹${minNextBid.toLocaleString('en-IN')}`;
                }

                if (data.highest_bidder === "<?= htmlspecialchars($username) ?>") {
                    highestBidderEl.style.color = 'var(--success-green)';
                } else {
                    highestBidderEl.style.color = 'var(--text-color)';
                }
            }

            async function fetchBids() {
                try {
                    const response = await fetch(`fetch_bids.php?auction_id=${auctionId}`);
                    if (!response.ok) throw new Error(`Network response error: ${response.statusText}`);
                    const data = await response.json();
                    if (data.error) throw new Error(data.error);
                    updateUI(data);
                } catch (error) {
                    console.error("Error fetching bids:", error);
                    showFeedback('Error fetching latest bid data.', 'error', 5000);
                    if (pollingInterval) clearInterval(pollingInterval);
                }
            }

            function showFeedback(message, type = 'success', duration = 3000) {
                feedbackContainer.innerHTML = `<div class="feedback-message feedback-${type}">${message}</div>`;
                setTimeout(() => {
                    feedbackContainer.innerHTML = '';
                }, duration);
            }

            if (bidForm) {
                bidForm.addEventListener('submit', async (e) => {
                    e.preventDefault();
                    const submitBtn = bidForm.querySelector('button[type="submit"]');
                    submitBtn.textContent = 'Placing...';
                    submitBtn.disabled = true;

                    const formData = new FormData(bidForm);

                    try {
                        const response = await fetch('place_bid.php', {
                            method: 'POST',
                            body: formData
                        });
                        const result = await response.json();

                        if (result.success) {
                            showFeedback('Bid placed successfully!', 'success');
                            bidAmountInput.value = '';
                            await fetchBids();
                        } else {
                            throw new Error(result.error || 'An unknown error occurred.');
                        }
                    } catch (error) {
                        showFeedback(error.message, 'error');
                    } finally {
                        submitBtn.textContent = 'Place Your Bid';
                        submitBtn.disabled = false;
                    }
                });
            }

            let secondsLeft = parseInt(timerElement.dataset.time, 10);
            const timerInterval = setInterval(() => {
                if (secondsLeft <= 0) {
                    clearInterval(timerInterval);
                    if (pollingInterval) clearInterval(pollingInterval);
                    timerElement.textContent = "Auction Ended";
                    const formContainer = document.getElementById('form-container');
                    if (formContainer) {
                        formContainer.innerHTML = `<button class="btn btn-disabled" disabled>Auction Has Ended</button>
                    <a href="auctions.php" class="btn btn-secondary">Back to Gallery</a>`;
                    }
                    return;
                }
                secondsLeft--;
                const days = Math.floor(secondsLeft / 86400);
                const hours = Math.floor((secondsLeft % 86400) / 3600);
                const minutes = Math.floor((secondsLeft % 3600) / 60);
                const seconds = secondsLeft % 60;
                if (days > 0) timerElement.textContent = `${days}d ${hours}h ${minutes}m`;
                else timerElement.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
            }, 1000);

            fetchBids();
            pollingInterval = setInterval(fetchBids, 5000);
        });
    </script>
</body>

</html>