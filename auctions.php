<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include("php/db.php");
// include("delete_expired_auctions.php"); // This logic should be run by a cron job, not on page load. For now, we assume it's handled.

// Get the logged-in user's name
$user_id = $_SESSION['user_id'];
$query = "SELECT username FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$username = $user['username'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Auctions | TimeLuxe Auctions</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
    <style>
        /*
            DESIGNER'S NOTES - The Auction Gallery (Layout Revision)

            Per the user's request, the layout is being updated to ensure items are always centered.

            1.  CENTERED LAYOUT: The `.auction-grid` has been changed from `display: grid` to `display: flex` with `justify-content: center`. This ensures that if there are fewer items than can fill a row (e.g., 1 or 2 cards), they will be horizontally centered in the container, creating a more balanced and professional presentation.
            2.  ADMIN LINK: Added a conditional "Admin Panel" button in the header, visible only to users with an 'admin' role, for seamless navigation.
            3.  PRICE FORMATTING: Applied `number_format()` to display prices with commas for improved readability.
        */

        :root {
            --bg-color: #121212;
            --card-bg: #1A1A1A;
            --primary-gold: #c0a060;
            --text-color: #EAEAEA;
            --text-light: #999;
            --font-serif: 'Playfair Display', serif;
            --font-sans: 'Poppins', sans-serif;
        }

        body {
            margin: 0;
            font-family: var(--font-sans);
            background-color: var(--bg-color);
            color: var(--text-color);
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
            z-index: 100;
        }

        .header-logo {
            height: 40px;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .user-info span {
            font-size: 0.9rem;
        }

        .admin-btn {
            font-family: var(--font-sans);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            color: var(--primary-gold);
            padding: 8px 16px;
            border: 1px solid var(--primary-gold);
            border-radius: 20px;
            transition: color 0.3s ease, background-color 0.3s ease, box-shadow 0.3s ease;
        }

        .admin-btn:hover {
            background-color: var(--primary-gold);
            color: var(--bg-color);
            box-shadow: 0 0 10px rgba(192, 160, 96, 0.5);
        }

        .logout-btn {
            font-family: var(--font-sans);
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            text-decoration: none;
            color: var(--text-light);
            padding: 8px 16px;
            border: 1px solid #333;
            border-radius: 20px;
            transition: color 0.3s ease, background-color 0.3s ease, border-color 0.3s ease;
        }

        .logout-btn:hover {
            background-color: var(--primary-gold);
            border-color: var(--primary-gold);
            color: var(--bg-color);
        }

        .gallery-container {
            max-width: 1400px;
            margin: 40px auto;
            padding: 0 40px;
        }

        .gallery-title {
            font-family: var(--font-serif);
            font-size: 3rem;
            text-align: center;
            margin-bottom: 40px;
        }

        .auction-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            gap: 30px;
            perspective: 1500px;
        }

        .auction-card-link {
            text-decoration: none;
            color: inherit;
        }

        .auction-card {
            position: relative;
            width: 320px;
            background: var(--card-bg);
            border-radius: 8px;
            border: 1px solid #2a2a2a;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1), box-shadow 0.6s cubic-bezier(0.23, 1, 0.32, 1);
            transform-style: preserve-3d;
        }

        .auction-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.8), 0 0 20px -5px var(--primary-gold);
        }

        .card-image-wrapper {
            background-color: #000;
            padding: 20px 0;
        }

        .watch-image {
            width: 80%;
            height: 200px;
            display: block;
            margin: 0 auto;
            object-fit: contain;
            filter: drop-shadow(0 10px 10px rgba(0, 0, 0, 0.7));
            transition: transform 0.6s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .auction-card:hover .watch-image {
            transform: scale(1.1);
        }

        .card-content {
            padding: 20px;
        }

        .watch-model {
            font-family: var(--font-serif);
            font-size: 1.6rem;
            color: var(--text-color);
            margin: 0 0 10px 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .watch-description {
            font-size: 0.9rem;
            color: var(--text-light);
            height: 40px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .auction-details {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #2a2a2a;
            padding-top: 15px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-item span:first-child {
            font-size: 0.8rem;
            color: var(--text-light);
            text-transform: uppercase;
        }

        .detail-item span:last-child {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-gold);
        }
    </style>
</head>

<body>

    <header class="gallery-header">
        <a href="auctions.php"><img src="./assets/logo-no-bg-W.png" alt="TimeLuxe Monogram" class="header-logo"></a>
        <div class="user-info">
            <span>Welcome, <?php echo htmlspecialchars($username); ?></span>
            <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <a href="php/admin.php" class="admin-btn">Admin Panel</a>
            <?php endif; ?>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </header>

    <main class="gallery-container">
        <h1 class="gallery-title">Live Auctions</h1>
        <div class="auction-grid">
            <?php
            $query = "SELECT *, TIMESTAMPDIFF(SECOND, NOW(), end_time) AS time_left 
                      FROM auctions 
                      WHERE end_time > NOW() AND status != 'expired' 
                      ORDER BY start_time ASC";
            $result = $conn->query($query);

            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $formatted_price = number_format($row['start_price']);
                    echo "
                    <a href='bid.php?auction_id={$row['id']}' class='auction-card-link'>
                        <div class='auction-card'>
                            <div class='card-image-wrapper'>
                                <img src='uploads/{$row['image']}' alt='{$row['title']}' class='watch-image'>
                            </div>
                            <div class='card-content'>
                                <h3 class='watch-model'>{$row['title']}</h3>
                                <p class='watch-description'>{$row['description']}</p>
                                <div class='auction-details'>
                                    <div class='detail-item'>
                                        <span>Starting Price</span>
                                        <span>₹{$formatted_price}</span>
                                    </div>
                                    <div class='detail-item'>
                                        <span>Time Left</span>
                                        <span class='timer' data-time='{$row['time_left']}'></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                    ";
                }
            } else {
                echo "<p style='text-align: center; width: 100%;'>No active auctions at the moment.</p>";
            }
            ?>
        </div>
    </main>

    <script>
        // A simple, elegant timer to bring the cards to life.
        document.addEventListener('DOMContentLoaded', () => {
            const timers = document.querySelectorAll('.timer');

            const updateTimer = (timerElement) => {
                let secondsLeft = parseInt(timerElement.dataset.time, 10);

                const intervalId = setInterval(() => {
                    if (secondsLeft <= 0) {
                        clearInterval(intervalId);
                        timerElement.textContent = "Auction Ended";
                        return;
                    }

                    secondsLeft--;
                    timerElement.dataset.time = secondsLeft;

                    const days = Math.floor(secondsLeft / (24 * 60 * 60));
                    const hours = Math.floor((secondsLeft % (24 * 60 * 60)) / (60 * 60));
                    const minutes = Math.floor((secondsLeft % (60 * 60)) / 60);
                    const seconds = Math.floor(secondsLeft % 60);

                    if (days > 0) {
                        timerElement.textContent = `${days}d ${hours}h ${minutes}m`;
                    } else {
                        timerElement.textContent = `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
                    }
                }, 1000);
            };

            timers.forEach(updateTimer);
        });
    </script>
</body>

</html>